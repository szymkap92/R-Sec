<?php
/**
 * Course Controller for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Course.php';

class CourseController {
    private $db;
    private $course;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->course = new Course($this->db);
    }
    
    /**
     * Get all courses with filtering and pagination
     */
    public function getCourses() {
        $filters = [
            'category' => $_GET['category'] ?? null,
            'level' => $_GET['level'] ?? null,
            'search' => $_GET['search'] ?? null,
            'price_min' => $_GET['price_min'] ?? null,
            'price_max' => $_GET['price_max'] ?? null,
            'min_rating' => $_GET['min_rating'] ?? null,
            'sort' => $_GET['sort'] ?? null,
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? DEFAULT_PAGE_SIZE
        ];
        
        $result = $this->course->getAll($filters);
        
        sendJsonResponse([
            'success' => true,
            'courses' => $result['courses'],
            'pagination' => $result['pagination']
        ]);
    }
    
    /**
     * Get single course by slug or ID
     */
    public function getCourse() {
        $courseId = $_GET['id'] ?? null;
        $courseSlug = $_GET['slug'] ?? null;
        
        if (!$courseId && !$courseSlug) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID lub slug kursu jest wymagany'
            ], 400);
        }
        
        if ($courseId) {
            $courseData = $this->course->getById($courseId);
        } else {
            $courseData = $this->course->getBySlug($courseSlug);
        }
        
        if (!$courseData) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        // Get course modules and lessons
        $modules = $this->course->getModulesWithLessons($courseData['id']);
        
        // Get course reviews
        $reviews = $this->course->getReviews($courseData['id'], 5);
        
        sendJsonResponse([
            'success' => true,
            'course' => $courseData,
            'modules' => $modules,
            'reviews' => $reviews
        ]);
    }
    
    /**
     * Get featured courses
     */
    public function getFeaturedCourses() {
        $limit = $_GET['limit'] ?? 6;
        $courses = $this->course->getFeatured($limit);
        
        sendJsonResponse([
            'success' => true,
            'courses' => $courses
        ]);
    }
    
    /**
     * Create new course (Admin/Instructor only)
     */
    public function createCourse() {
        if (!$this->isAuthorized()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $required_fields = ['title', 'description', 'category_id', 'level', 'price'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                sendJsonResponse([
                    'success' => false,
                    'message' => "Pole {$field} jest wymagane"
                ], 400);
            }
        }
        
        // Set course properties
        $this->course->title = $data['title'];
        $this->course->description = $data['description'];
        $this->course->short_description = $data['short_description'] ?? '';
        $this->course->thumbnail = $data['thumbnail'] ?? '';
        $this->course->category_id = $data['category_id'];
        $this->course->instructor_id = $_SESSION['user_id'];
        $this->course->level = $data['level'];
        $this->course->language = $data['language'] ?? 'pl';
        $this->course->price = $data['price'];
        $this->course->original_price = $data['original_price'] ?? $data['price'];
        $this->course->duration_hours = $data['duration_hours'] ?? 0;
        $this->course->badge = $data['badge'] ?? null;
        $this->course->requirements = $data['requirements'] ?? '';
        $this->course->what_you_learn = $data['what_you_learn'] ?? '';
        $this->course->target_audience = $data['target_audience'] ?? '';
        $this->course->certificate_available = $data['certificate_available'] ?? false;
        $this->course->difficulty_rating = $data['difficulty_rating'] ?? 1;
        
        if ($this->course->create()) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Kurs został utworzony pomyślnie',
                'course' => $this->course->toArray()
            ], 201);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas tworzenia kursu'
            ], 500);
        }
    }
    
    /**
     * Update course (Admin/Instructor only)
     */
    public function updateCourse() {
        if (!$this->isAuthorized()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $courseId = $_GET['id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        // Get course to check ownership
        if (!$this->course->getById($courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        // Check if user owns the course or is admin
        if (!$this->canEditCourse()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień do edycji tego kursu'
            ], 403);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Update course properties
        $this->course->title = $data['title'] ?? $this->course->title;
        $this->course->description = $data['description'] ?? $this->course->description;
        $this->course->short_description = $data['short_description'] ?? $this->course->short_description;
        $this->course->thumbnail = $data['thumbnail'] ?? $this->course->thumbnail;
        $this->course->category_id = $data['category_id'] ?? $this->course->category_id;
        $this->course->level = $data['level'] ?? $this->course->level;
        $this->course->language = $data['language'] ?? $this->course->language;
        $this->course->price = $data['price'] ?? $this->course->price;
        $this->course->original_price = $data['original_price'] ?? $this->course->original_price;
        $this->course->duration_hours = $data['duration_hours'] ?? $this->course->duration_hours;
        $this->course->badge = $data['badge'] ?? $this->course->badge;
        $this->course->requirements = $data['requirements'] ?? $this->course->requirements;
        $this->course->what_you_learn = $data['what_you_learn'] ?? $this->course->what_you_learn;
        $this->course->target_audience = $data['target_audience'] ?? $this->course->target_audience;
        $this->course->certificate_available = $data['certificate_available'] ?? $this->course->certificate_available;
        $this->course->difficulty_rating = $data['difficulty_rating'] ?? $this->course->difficulty_rating;
        $this->course->is_published = $data['is_published'] ?? $this->course->is_published;
        $this->course->is_featured = $data['is_featured'] ?? $this->course->is_featured;
        
        if ($this->course->update()) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Kurs został zaktualizowany pomyślnie',
                'course' => $this->course->toArray()
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji kursu'
            ], 500);
        }
    }
    
    /**
     * Delete course (Admin only)
     */
    public function deleteCourse() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $courseId = $_GET['id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        if (!$this->course->getById($courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        $this->course->id = $courseId;
        
        if ($this->course->delete()) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Kurs został usunięty pomyślnie'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas usuwania kursu'
            ], 500);
        }
    }
    
    /**
     * Get course reviews
     */
    public function getCourseReviews() {
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        
        $reviews = $this->course->getReviews($courseId, $limit, $offset);
        
        sendJsonResponse([
            'success' => true,
            'reviews' => $reviews
        ]);
    }
    
    /**
     * Update course statistics
     */
    public function updateCourseStatistics() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień'
            ], 403);
        }
        
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        $this->course->updateStatistics($courseId);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Statystyki kursu zostały zaktualizowane'
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
    
    /**
     * Check if user is instructor
     */
    private function isInstructor() {
        return $this->isAuthenticated() && 
               ($_SESSION['user_role'] === 'instructor' || $_SESSION['user_role'] === 'admin');
    }
    
    /**
     * Check if user is authorized to create/edit courses
     */
    private function isAuthorized() {
        return $this->isInstructor();
    }
    
    /**
     * Check if user can edit specific course
     */
    private function canEditCourse() {
        return $this->isAdmin() || 
               ($_SESSION['user_id'] == $this->course->instructor_id && $this->isInstructor());
    }
}
?>