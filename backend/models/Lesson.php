<?php
/**
 * Lesson Model for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

class Lesson {
    private $conn;
    
    // Lesson properties
    public $id;
    public $course_id;
    public $module_id;
    public $title;
    public $slug;
    public $description;
    public $video_url;
    public $video_duration;
    public $video_provider;
    public $content;
    public $attachments;
    public $sort_order;
    public $is_preview;
    public $is_published;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get lesson by ID
     */
    public function getById($id) {
        $query = "SELECT l.*, m.title as module_title, c.title as course_title
                 FROM lessons l
                 LEFT JOIN course_modules m ON l.module_id = m.id
                 LEFT JOIN courses c ON l.course_id = c.id
                 WHERE l.id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return $row;
        }
        
        return false;
    }
    
    /**
     * Get lessons by course ID
     */
    public function getByCourse($courseId) {
        $query = "SELECT l.*, m.title as module_title, m.sort_order as module_sort_order
                 FROM lessons l
                 LEFT JOIN course_modules m ON l.module_id = m.id
                 WHERE l.course_id = ? AND l.is_published = 1
                 ORDER BY m.sort_order ASC, l.sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $courseId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get lesson progress for user
     */
    public function getUserProgress($lessonId, $userId) {
        $query = "SELECT * FROM lesson_progress 
                 WHERE lesson_id = ? AND user_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $lessonId);
        $stmt->bindParam(2, $userId);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    /**
     * Update lesson progress for user
     */
    public function updateProgress($lessonId, $userId, $watchTime, $completed = false) {
        // Check if progress record exists
        $existsQuery = "SELECT id FROM lesson_progress WHERE lesson_id = ? AND user_id = ?";
        $existsStmt = $this->conn->prepare($existsQuery);
        $existsStmt->execute([$lessonId, $userId]);
        
        if ($existsStmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE lesson_progress 
                     SET watch_time = ?, 
                         is_completed = ?,
                         completed_at = " . ($completed ? "CURRENT_TIMESTAMP" : "completed_at") . ",
                         updated_at = CURRENT_TIMESTAMP
                     WHERE lesson_id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$watchTime, $completed ? 1 : 0, $lessonId, $userId]);
        } else {
            // Create new record
            $query = "INSERT INTO lesson_progress 
                     SET lesson_id = ?, 
                         user_id = ?, 
                         watch_time = ?, 
                         is_completed = ?,
                         completed_at = " . ($completed ? "CURRENT_TIMESTAMP" : "NULL");
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$lessonId, $userId, $watchTime, $completed ? 1 : 0]);
        }
        
        if ($stmt->rowCount() > 0) {
            // Update overall course progress
            $this->updateCourseProgress($lessonId, $userId);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update overall course progress based on lesson completion
     */
    private function updateCourseProgress($lessonId, $userId) {
        // Get course ID for this lesson
        $courseQuery = "SELECT course_id FROM lessons WHERE id = ?";
        $courseStmt = $this->conn->prepare($courseQuery);
        $courseStmt->execute([$lessonId]);
        $courseId = $courseStmt->fetch(PDO::FETCH_ASSOC)['course_id'];
        
        // Calculate completion percentage
        $statsQuery = "SELECT 
                          COUNT(*) as total_lessons,
                          COUNT(CASE WHEN lp.is_completed = 1 THEN 1 END) as completed_lessons
                       FROM lessons l
                       LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
                       WHERE l.course_id = ? AND l.is_published = 1";
        
        $statsStmt = $this->conn->prepare($statsQuery);
        $statsStmt->execute([$userId, $courseId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $progressPercentage = $stats['total_lessons'] > 0 
            ? ($stats['completed_lessons'] / $stats['total_lessons']) * 100 
            : 0;
        
        // Update enrollment progress
        require_once __DIR__ . '/Enrollment.php';
        $enrollment = new Enrollment($this->conn);
        
        // Get enrollment ID
        $enrollmentQuery = "SELECT id FROM course_enrollments 
                           WHERE user_id = ? AND course_id = ? AND is_active = 1";
        $enrollmentStmt = $this->conn->prepare($enrollmentQuery);
        $enrollmentStmt->execute([$userId, $courseId]);
        
        if ($enrollmentStmt->rowCount() > 0) {
            $enrollmentId = $enrollmentStmt->fetch(PDO::FETCH_ASSOC)['id'];
            $enrollment->updateProgress($enrollmentId, round($progressPercentage, 2), $lessonId);
        }
    }
    
    /**
     * Check if user can access lesson
     */
    public function canUserAccess($lessonId, $userId) {
        // Check if lesson is preview (always accessible)
        if ($this->is_preview) {
            return true;
        }
        
        // Check if user is enrolled in the course
        $query = "SELECT ce.id 
                 FROM lessons l
                 JOIN course_enrollments ce ON l.course_id = ce.course_id
                 WHERE l.id = ? AND ce.user_id = ? AND ce.is_active = 1
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$lessonId, $userId]);
        
        return $stmt->rowCount() > 0;
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
    public function toArray($includeContent = true) {
        $data = [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'module_id' => $this->module_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'video_duration' => $this->video_duration,
            'video_provider' => $this->video_provider,
            'sort_order' => $this->sort_order,
            'is_preview' => $this->is_preview,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
        
        if ($includeContent) {
            $data['content'] = $this->content;
            $data['attachments'] = $this->attachments;
        }
        
        return $data;
    }
}
?>