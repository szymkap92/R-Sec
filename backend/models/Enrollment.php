<?php
/**
 * Enrollment Model for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

class Enrollment {
    private $conn;
    
    // Enrollment properties
    public $id;
    public $user_id;
    public $course_id;
    public $enrollment_type;
    public $price_paid;
    public $payment_method;
    public $payment_status;
    public $progress_percentage;
    public $last_lesson_id;
    public $total_watch_time;
    public $completed_at;
    public $certificate_issued;
    public $is_active;
    public $enrolled_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new enrollment
     */
    public function create() {
        $query = "INSERT INTO course_enrollments 
                 SET user_id = :user_id,
                     course_id = :course_id,
                     enrollment_type = :enrollment_type,
                     price_paid = :price_paid,
                     payment_method = :payment_method,
                     payment_status = :payment_status";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':course_id', $this->course_id);
        $stmt->bindParam(':enrollment_type', $this->enrollment_type);
        $stmt->bindParam(':price_paid', $this->price_paid);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':payment_status', $this->payment_status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            logActivity($this->user_id, 'course_enrolled', 'enrollment', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get enrollment by user and course
     */
    public function getByUserAndCourse($userId, $courseId) {
        $query = "SELECT e.*, c.title as course_title, c.slug as course_slug,
                        c.thumbnail as course_thumbnail, c.level as course_level
                 FROM course_enrollments e
                 JOIN courses c ON e.course_id = c.id
                 WHERE e.user_id = ? AND e.course_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId);
        $stmt->bindParam(2, $courseId);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return $row;
        }
        
        return false;
    }
    
    /**
     * Get user enrollments
     */
    public function getUserEnrollments($userId, $status = 'active') {
        $whereClause = "e.user_id = ?";
        $params = [$userId];
        
        if ($status === 'active') {
            $whereClause .= " AND e.is_active = 1";
        } elseif ($status === 'completed') {
            $whereClause .= " AND e.completed_at IS NOT NULL";
        } elseif ($status === 'in_progress') {
            $whereClause .= " AND e.is_active = 1 AND e.completed_at IS NULL AND e.progress_percentage > 0";
        }
        
        $query = "SELECT e.*, c.title, c.slug, c.thumbnail, c.level, c.duration_hours,
                        c.total_lessons, c.certificate_available
                 FROM course_enrollments e
                 JOIN courses c ON e.course_id = c.id
                 WHERE {$whereClause}
                 ORDER BY e.enrolled_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update enrollment progress
     */
    public function updateProgress($enrollmentId, $progressPercentage, $lastLessonId = null) {
        $query = "UPDATE course_enrollments 
                 SET progress_percentage = :progress_percentage,
                     last_lesson_id = :last_lesson_id,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':progress_percentage', $progressPercentage);
        $stmt->bindParam(':last_lesson_id', $lastLessonId);
        $stmt->bindParam(':id', $enrollmentId);
        
        if($stmt->execute()) {
            // Check if course is completed (100% progress)
            if ($progressPercentage >= 100) {
                $this->markAsCompleted($enrollmentId);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark enrollment as completed
     */
    public function markAsCompleted($enrollmentId) {
        $query = "UPDATE course_enrollments 
                 SET completed_at = CURRENT_TIMESTAMP,
                     progress_percentage = 100
                 WHERE id = ? AND completed_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $enrollmentId);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            // Get enrollment details for logging
            $enrollmentQuery = "SELECT user_id, course_id FROM course_enrollments WHERE id = ?";
            $enrollmentStmt = $this->conn->prepare($enrollmentQuery);
            $enrollmentStmt->bindParam(1, $enrollmentId);
            $enrollmentStmt->execute();
            $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);
            
            logActivity($enrollment['user_id'], 'course_completed', 'enrollment', $enrollmentId);
            
            // Generate certificate if available
            $this->generateCertificate($enrollmentId);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate certificate for completed course
     */
    private function generateCertificate($enrollmentId) {
        // Get enrollment and course details
        $query = "SELECT e.user_id, e.course_id, c.title, c.certificate_available
                 FROM course_enrollments e
                 JOIN courses c ON e.course_id = c.id
                 WHERE e.id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $enrollmentId);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data && $data['certificate_available']) {
            $certQuery = "INSERT INTO certificates 
                         SET user_id = :user_id,
                             course_id = :course_id,
                             certificate_number = :cert_number,
                             certificate_hash = :cert_hash";
            
            $certStmt = $this->conn->prepare($certQuery);
            
            $certNumber = 'RSEC-' . date('Y') . '-' . str_pad($data['course_id'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($data['user_id'], 6, '0', STR_PAD_LEFT);
            $certHash = hash('sha256', $certNumber . $data['user_id'] . $data['course_id'] . time());
            
            $certStmt->bindParam(':user_id', $data['user_id']);
            $certStmt->bindParam(':course_id', $data['course_id']);
            $certStmt->bindParam(':cert_number', $certNumber);
            $certStmt->bindParam(':cert_hash', $certHash);
            
            if ($certStmt->execute()) {
                // Update enrollment to mark certificate as issued
                $updateQuery = "UPDATE course_enrollments SET certificate_issued = 1 WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(1, $enrollmentId);
                $updateStmt->execute();
                
                logActivity($data['user_id'], 'certificate_issued', 'certificate', $this->conn->lastInsertId());
            }
        }
    }
    
    /**
     * Check if user is enrolled in course
     */
    public function isUserEnrolled($userId, $courseId) {
        $query = "SELECT id FROM course_enrollments 
                 WHERE user_id = ? AND course_id = ? AND is_active = 1 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $userId);
        $stmt->bindParam(2, $courseId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get enrollment statistics
     */
    public function getStatistics($courseId = null) {
        $stats = [];
        
        if ($courseId) {
            // Course-specific statistics
            $query = "SELECT 
                        COUNT(*) as total_enrollments,
                        COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_enrollments,
                        AVG(progress_percentage) as avg_progress,
                        SUM(price_paid) as total_revenue
                     FROM course_enrollments 
                     WHERE course_id = ? AND is_active = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $courseId);
        } else {
            // Global statistics
            $query = "SELECT 
                        COUNT(*) as total_enrollments,
                        COUNT(CASE WHEN completed_at IS NOT NULL THEN 1 END) as completed_enrollments,
                        AVG(progress_percentage) as avg_progress,
                        SUM(price_paid) as total_revenue
                     FROM course_enrollments 
                     WHERE is_active = 1";
            
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cancel enrollment
     */
    public function cancel($enrollmentId, $reason = null) {
        $query = "UPDATE course_enrollments 
                 SET is_active = 0,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $enrollmentId);
        
        if($stmt->execute()) {
            // Get user_id for logging
            $userQuery = "SELECT user_id FROM course_enrollments WHERE id = ?";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(1, $enrollmentId);
            $userStmt->execute();
            $userId = $userStmt->fetch(PDO::FETCH_ASSOC)['user_id'];
            
            logActivity($userId, 'enrollment_cancelled', 'enrollment', $enrollmentId, ['reason' => $reason]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update watch time
     */
    public function updateWatchTime($enrollmentId, $additionalMinutes) {
        $query = "UPDATE course_enrollments 
                 SET total_watch_time = COALESCE(total_watch_time, 0) + ?
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $additionalMinutes);
        $stmt->bindParam(2, $enrollmentId);
        
        return $stmt->execute();
    }
    
    /**
     * Set object properties from array
     */
    private function setProperties($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'course_id' => $this->course_id,
            'enrollment_type' => $this->enrollment_type,
            'price_paid' => $this->price_paid,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'progress_percentage' => $this->progress_percentage,
            'last_lesson_id' => $this->last_lesson_id,
            'total_watch_time' => $this->total_watch_time,
            'completed_at' => $this->completed_at,
            'certificate_issued' => $this->certificate_issued,
            'is_active' => $this->is_active,
            'enrolled_at' => $this->enrolled_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>