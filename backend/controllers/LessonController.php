<?php
/**
 * Lesson Controller for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Lesson.php';

class LessonController {
    private $db;
    private $lesson;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->lesson = new Lesson($this->db);
    }
    
    /**
     * Get lesson details
     */
    public function getLesson() {
        $lessonId = $_GET['id'] ?? null;
        if (!$lessonId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID lekcji jest wymagane'
            ], 400);
        }
        
        $lessonData = $this->lesson->getById($lessonId);
        if (!$lessonData) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Lekcja nie została znaleziona'
            ], 404);
        }
        
        // Check if user can access lesson
        if ($this->isAuthenticated()) {
            $canAccess = $this->lesson->canUserAccess($lessonId, $_SESSION['user_id']);
            if (!$canAccess && !$lessonData['is_preview']) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Brak dostępu do tej lekcji'
                ], 403);
            }
            
            // Get user progress for this lesson
            $progress = $this->lesson->getUserProgress($lessonId, $_SESSION['user_id']);
            $lessonData['user_progress'] = $progress;
        } else {
            // For non-authenticated users, only allow preview lessons
            if (!$lessonData['is_preview']) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Musisz być zalogowany aby uzyskać dostęp do tej lekcji'
                ], 401);
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'lesson' => $lessonData
        ]);
    }
    
    /**
     * Get course lessons
     */
    public function getCourseLessons() {
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $lessons = $this->lesson->getByCourse($courseId);
        
        // If user is authenticated, add progress information
        if ($this->isAuthenticated()) {
            foreach ($lessons as &$lesson) {
                $progress = $this->lesson->getUserProgress($lesson['id'], $_SESSION['user_id']);
                $lesson['user_progress'] = $progress;
                
                // Check access permissions
                $lesson['can_access'] = $this->lesson->canUserAccess($lesson['id'], $_SESSION['user_id']);
            }
        } else {
            // For non-authenticated users, mark access based on preview status
            foreach ($lessons as &$lesson) {
                $lesson['can_access'] = $lesson['is_preview'] == 1;
                $lesson['user_progress'] = null;
            }
        }
        
        sendJsonResponse([
            'success' => true,
            'lessons' => $lessons
        ]);
    }
    
    /**
     * Update lesson progress
     */
    public function updateProgress() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        $lessonId = $data['lesson_id'] ?? null;
        $watchTime = $data['watch_time'] ?? 0;
        $completed = $data['completed'] ?? false;
        
        if (!$lessonId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID lekcji jest wymagane'
            ], 400);
        }
        
        // Verify user can access lesson
        if (!$this->lesson->canUserAccess($lessonId, $_SESSION['user_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak dostępu do tej lekcji'
            ], 403);
        }
        
        if ($this->lesson->updateProgress($lessonId, $_SESSION['user_id'], $watchTime, $completed)) {
            $message = $completed ? 'Lekcja została oznaczona jako ukończona' : 'Postęp został zaktualizowany';
            
            sendJsonResponse([
                'success' => true,
                'message' => $message
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji postępu'
            ], 500);
        }
    }
    
    /**
     * Mark lesson as completed
     */
    public function markCompleted() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $lessonId = $data['lesson_id'] ?? null;
        
        if (!$lessonId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID lekcji jest wymagane'
            ], 400);
        }
        
        // Verify user can access lesson
        if (!$this->lesson->canUserAccess($lessonId, $_SESSION['user_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak dostępu do tej lekcji'
            ], 403);
        }
        
        // Get current progress to preserve watch time
        $currentProgress = $this->lesson->getUserProgress($lessonId, $_SESSION['user_id']);
        $watchTime = $currentProgress ? $currentProgress['watch_time'] : 0;
        
        if ($this->lesson->updateProgress($lessonId, $_SESSION['user_id'], $watchTime, true)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Lekcja została oznaczona jako ukończona'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas oznaczania lekcji jako ukończonej'
            ], 500);
        }
    }
    
    /**
     * Get user's lesson progress for a course
     */
    public function getCourseProgress() {
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
        
        $query = "SELECT 
                    l.id as lesson_id,
                    l.title,
                    l.video_duration,
                    lp.watch_time,
                    lp.is_completed,
                    lp.completed_at,
                    m.title as module_title
                  FROM lessons l
                  LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                  LEFT JOIN course_modules m ON l.module_id = m.id
                  WHERE l.course_id = ? AND l.is_published = 1
                  ORDER BY m.sort_order ASC, l.sort_order ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $courseId]);
        $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall statistics
        $totalLessons = count($progress);
        $completedLessons = array_reduce($progress, function($count, $lesson) {
            return $count + ($lesson['is_completed'] ? 1 : 0);
        }, 0);
        
        $totalWatchTime = array_reduce($progress, function($time, $lesson) {
            return $time + intval($lesson['watch_time'] ?? 0);
        }, 0);
        
        sendJsonResponse([
            'success' => true,
            'progress' => $progress,
            'statistics' => [
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'completion_percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0,
                'total_watch_time' => $totalWatchTime
            ]
        ]);
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}
?>