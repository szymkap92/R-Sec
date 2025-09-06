<?php
/**
 * Enrollment Controller for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Enrollment.php';
require_once __DIR__ . '/../models/Course.php';

class EnrollmentController {
    private $db;
    private $enrollment;
    private $course;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->enrollment = new Enrollment($this->db);
        $this->course = new Course($this->db);
    }
    
    /**
     * Enroll user in course
     */
    public function enrollInCourse() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany aby zapisać się na kurs'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['course_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $courseId = $data['course_id'];
        $userId = $_SESSION['user_id'];
        
        // Check if course exists
        if (!$this->course->getById($courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        // Check if user is already enrolled
        if ($this->enrollment->isUserEnrolled($userId, $courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Jesteś już zapisany na ten kurs'
            ], 400);
        }
        
        // Set enrollment properties
        $this->enrollment->user_id = $userId;
        $this->enrollment->course_id = $courseId;
        $this->enrollment->enrollment_type = $data['enrollment_type'] ?? 'paid';
        $this->enrollment->price_paid = $data['price_paid'] ?? $this->course->price;
        $this->enrollment->payment_method = $data['payment_method'] ?? 'stripe';
        $this->enrollment->payment_status = $data['payment_status'] ?? 'pending';
        
        if ($this->enrollment->create()) {
            // Update course statistics
            $this->course->updateStatistics($courseId);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Zapisano na kurs pomyślnie',
                'enrollment' => $this->enrollment->toArray()
            ], 201);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas zapisywania na kurs'
            ], 500);
        }
    }
    
    /**
     * Get user enrollments
     */
    public function getUserEnrollments() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $userId = $_SESSION['user_id'];
        $status = $_GET['status'] ?? 'active';
        
        $enrollments = $this->enrollment->getUserEnrollments($userId, $status);
        
        sendJsonResponse([
            'success' => true,
            'enrollments' => $enrollments
        ]);
    }
    
    /**
     * Get specific enrollment
     */
    public function getEnrollment() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $userId = $_SESSION['user_id'];
        $enrollment = $this->enrollment->getByUserAndCourse($userId, $courseId);
        
        if (!$enrollment) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zapisany na ten kurs'
            ], 404);
        }
        
        sendJsonResponse([
            'success' => true,
            'enrollment' => $enrollment
        ]);
    }
    
    /**
     * Update enrollment progress
     */
    public function updateProgress() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        $enrollmentId = $data['enrollment_id'] ?? null;
        $progressPercentage = $data['progress_percentage'] ?? null;
        $lastLessonId = $data['last_lesson_id'] ?? null;
        
        if (!$enrollmentId || $progressPercentage === null) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID zapisania i procent postępu są wymagane'
            ], 400);
        }
        
        // Verify enrollment belongs to user
        $checkQuery = "SELECT id FROM course_enrollments WHERE id = ? AND user_id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$enrollmentId, $_SESSION['user_id']]);
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        if ($this->enrollment->updateProgress($enrollmentId, $progressPercentage, $lastLessonId)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Postęp został zaktualizowany'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji postępu'
            ], 500);
        }
    }
    
    /**
     * Mark course as completed
     */
    public function markAsCompleted() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $enrollmentId = $data['enrollment_id'] ?? null;
        
        if (!$enrollmentId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID zapisania jest wymagane'
            ], 400);
        }
        
        // Verify enrollment belongs to user
        $checkQuery = "SELECT id FROM course_enrollments WHERE id = ? AND user_id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$enrollmentId, $_SESSION['user_id']]);
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        if ($this->enrollment->markAsCompleted($enrollmentId)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Gratulacje! Ukończyłeś kurs'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas oznaczania kursu jako ukończony'
            ], 500);
        }
    }
    
    /**
     * Cancel enrollment
     */
    public function cancelEnrollment() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $enrollmentId = $data['enrollment_id'] ?? null;
        $reason = $data['reason'] ?? null;
        
        if (!$enrollmentId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID zapisania jest wymagane'
            ], 400);
        }
        
        // Verify enrollment belongs to user
        $checkQuery = "SELECT id FROM course_enrollments WHERE id = ? AND user_id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$enrollmentId, $_SESSION['user_id']]);
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        if ($this->enrollment->cancel($enrollmentId, $reason)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Zapis na kurs został anulowany'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas anulowania zapisu'
            ], 500);
        }
    }
    
    /**
     * Update watch time
     */
    public function updateWatchTime() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $enrollmentId = $data['enrollment_id'] ?? null;
        $watchTime = $data['watch_time'] ?? null;
        
        if (!$enrollmentId || !$watchTime) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID zapisania i czas oglądania są wymagane'
            ], 400);
        }
        
        // Verify enrollment belongs to user
        $checkQuery = "SELECT id FROM course_enrollments WHERE id = ? AND user_id = ?";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$enrollmentId, $_SESSION['user_id']]);
        
        if ($checkStmt->rowCount() === 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        if ($this->enrollment->updateWatchTime($enrollmentId, $watchTime)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Czas oglądania został zaktualizowany'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji czasu oglądania'
            ], 500);
        }
    }
    
    /**
     * Get enrollment statistics (Admin only)
     */
    public function getStatistics() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $courseId = $_GET['course_id'] ?? null;
        $stats = $this->enrollment->getStatistics($courseId);
        
        sendJsonResponse([
            'success' => true,
            'statistics' => $stats
        ]);
    }
    
    /**
     * Check enrollment status
     */
    public function checkEnrollmentStatus() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'enrolled' => false,
                'message' => 'Musisz być zalogowany'
            ]);
        }
        
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $userId = $_SESSION['user_id'];
        $isEnrolled = $this->enrollment->isUserEnrolled($userId, $courseId);
        $enrollment = null;
        
        if ($isEnrolled) {
            $enrollment = $this->enrollment->getByUserAndCourse($userId, $courseId);
        }
        
        sendJsonResponse([
            'success' => true,
            'enrolled' => $isEnrolled,
            'enrollment' => $enrollment
        ]);
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin() {
        return $this->isAuthenticated() && $_SESSION['user_role'] === 'admin';
    }
}
?>