<?php
/**
 * Admin Controller for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Enrollment.php';

class AdminController {
    private $db;
    private $user;
    private $course;
    private $enrollment;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
        $this->course = new Course($this->db);
        $this->enrollment = new Enrollment($this->db);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $stats = [];
        
        // Total users
        $userQuery = "SELECT COUNT(*) as total_users FROM users WHERE is_active = 1";
        $userStmt = $this->db->prepare($userQuery);
        $userStmt->execute();
        $stats['total_users'] = $userStmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        // Total courses
        $courseQuery = "SELECT COUNT(*) as total_courses FROM courses WHERE is_published = 1";
        $courseStmt = $this->db->prepare($courseQuery);
        $courseStmt->execute();
        $stats['total_courses'] = $courseStmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
        
        // Total enrollments
        $enrollmentQuery = "SELECT COUNT(*) as total_enrollments FROM course_enrollments WHERE is_active = 1";
        $enrollmentStmt = $this->db->prepare($enrollmentQuery);
        $enrollmentStmt->execute();
        $stats['total_enrollments'] = $enrollmentStmt->fetch(PDO::FETCH_ASSOC)['total_enrollments'];
        
        // Total revenue
        $revenueQuery = "SELECT COALESCE(SUM(price_paid), 0) as total_revenue FROM course_enrollments WHERE payment_status = 'completed'";
        $revenueStmt = $this->db->prepare($revenueQuery);
        $revenueStmt->execute();
        $stats['total_revenue'] = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
        
        // Recent enrollments (last 7 days)
        $recentQuery = "SELECT COUNT(*) as recent_enrollments FROM course_enrollments WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $recentStmt = $this->db->prepare($recentQuery);
        $recentStmt->execute();
        $stats['recent_enrollments'] = $recentStmt->fetch(PDO::FETCH_ASSOC)['recent_enrollments'];
        
        // Course completion rate
        $completionQuery = "SELECT 
                               COUNT(*) as total_active_enrollments,
                               COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_enrollments
                           FROM course_enrollments WHERE is_active = 1";
        $completionStmt = $this->db->prepare($completionQuery);
        $completionStmt->execute();
        $completionData = $completionStmt->fetch(PDO::FETCH_ASSOC);
        
        $stats['completion_rate'] = $completionData['total_active_enrollments'] > 0 
            ? round(($completionData['completed_enrollments'] / $completionData['total_active_enrollments']) * 100, 2)
            : 0;
        
        // Monthly statistics
        $monthlyQuery = "SELECT 
                            MONTH(enrolled_at) as month,
                            YEAR(enrolled_at) as year,
                            COUNT(*) as enrollments,
                            SUM(price_paid) as revenue
                         FROM course_enrollments 
                         WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                         GROUP BY YEAR(enrolled_at), MONTH(enrolled_at)
                         ORDER BY year ASC, month ASC";
        $monthlyStmt = $this->db->prepare($monthlyQuery);
        $monthlyStmt->execute();
        $stats['monthly_data'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'statistics' => $stats
        ]);
    }
    
    /**
     * Get all users with pagination
     */
    public function getUsers() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? DEFAULT_PAGE_SIZE;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        
        $offset = ($page - 1) * $limit;
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR username LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($role)) {
            $whereConditions[] = "role = ?";
            $params[] = $role;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "SELECT id, username, email, first_name, last_name, role, is_active, 
                        email_verified, created_at, last_login,
                        (SELECT COUNT(*) FROM course_enrollments WHERE user_id = users.id AND is_active = 1) as total_enrollments
                 FROM users 
                 WHERE {$whereClause}
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendJsonResponse([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'current_page' => intval($page),
                'per_page' => intval($limit),
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Update user status (activate/deactivate)
     */
    public function updateUserStatus() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $userId = $data['user_id'] ?? null;
        $isActive = $data['is_active'] ?? null;
        
        if (!$userId || $isActive === null) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID użytkownika i status są wymagane'
            ], 400);
        }
        
        $query = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([$isActive, $userId])) {
            $action = $isActive ? 'activated' : 'deactivated';
            logActivity($_SESSION['user_id'], "user_{$action}", 'user', $userId);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Status użytkownika został zaktualizowany'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji statusu użytkownika'
            ], 500);
        }
    }
    
    /**
     * Get recent activity logs
     */
    public function getActivityLogs() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT al.*, u.first_name, u.last_name, u.email
                 FROM activity_logs al
                 LEFT JOIN users u ON al.user_id = u.id
                 ORDER BY al.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM activity_logs";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendJsonResponse([
            'success' => true,
            'logs' => $logs,
            'pagination' => [
                'current_page' => intval($page),
                'per_page' => intval($limit),
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Get course management data
     */
    public function getCoursesManagement() {
        if (!$this->isAdminOrInstructor()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? DEFAULT_PAGE_SIZE;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $offset = ($page - 1) * $limit;
        $whereConditions = ['1=1'];
        $params = [];
        
        // If not admin, only show own courses
        if (!$this->isAdmin()) {
            $whereConditions[] = "c.instructor_id = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        if (!empty($search)) {
            $whereConditions[] = "c.title LIKE ?";
            $params[] = '%' . $search . '%';
        }
        
        if (!empty($status)) {
            if ($status === 'published') {
                $whereConditions[] = "c.is_published = 1";
            } elseif ($status === 'draft') {
                $whereConditions[] = "c.is_published = 0";
            }
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "SELECT c.*, cat.name as category_name, 
                        u.first_name as instructor_first_name, u.last_name as instructor_last_name,
                        (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND is_active = 1) as enrollment_count
                 FROM courses c
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE {$whereClause}
                 ORDER BY c.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM courses c WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        sendJsonResponse([
            'success' => true,
            'courses' => $courses,
            'pagination' => [
                'current_page' => intval($page),
                'per_page' => intval($limit),
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Check if user is admin or instructor
     */
    private function isAdminOrInstructor() {
        return isset($_SESSION['user_role']) && 
               in_array($_SESSION['user_role'], ['admin', 'instructor']);
    }
}
?>