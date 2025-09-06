<?php
/**
 * Course Model for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

class Course {
    private $conn;
    
    // Course properties
    public $id;
    public $title;
    public $slug;
    public $description;
    public $short_description;
    public $thumbnail;
    public $category_id;
    public $instructor_id;
    public $level;
    public $language;
    public $price;
    public $original_price;
    public $duration_hours;
    public $total_lessons;
    public $is_published;
    public $is_featured;
    public $badge;
    public $requirements;
    public $what_you_learn;
    public $target_audience;
    public $certificate_available;
    public $difficulty_rating;
    public $average_rating;
    public $total_reviews;
    public $total_students;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new course
     */
    public function create() {
        $query = "INSERT INTO courses 
                 SET title = :title,
                     slug = :slug,
                     description = :description,
                     short_description = :short_description,
                     thumbnail = :thumbnail,
                     category_id = :category_id,
                     instructor_id = :instructor_id,
                     level = :level,
                     language = :language,
                     price = :price,
                     original_price = :original_price,
                     duration_hours = :duration_hours,
                     total_lessons = :total_lessons,
                     badge = :badge,
                     requirements = :requirements,
                     what_you_learn = :what_you_learn,
                     target_audience = :target_audience,
                     certificate_available = :certificate_available,
                     difficulty_rating = :difficulty_rating";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->title = sanitizeInput($this->title);
        $this->slug = $this->generateSlug($this->title);
        $this->description = sanitizeInput($this->description);
        $this->short_description = sanitizeInput($this->short_description);
        
        // Bind parameters
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':short_description', $this->short_description);
        $stmt->bindParam(':thumbnail', $this->thumbnail);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':instructor_id', $this->instructor_id);
        $stmt->bindParam(':level', $this->level);
        $stmt->bindParam(':language', $this->language);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':duration_hours', $this->duration_hours);
        $stmt->bindParam(':total_lessons', $this->total_lessons);
        $stmt->bindParam(':badge', $this->badge);
        $stmt->bindParam(':requirements', $this->requirements);
        $stmt->bindParam(':what_you_learn', $this->what_you_learn);
        $stmt->bindParam(':target_audience', $this->target_audience);
        $stmt->bindParam(':certificate_available', $this->certificate_available);
        $stmt->bindParam(':difficulty_rating', $this->difficulty_rating);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            logActivity($this->instructor_id, 'course_created', 'course', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get course by ID
     */
    public function getById($id) {
        $query = "SELECT c.*, cat.name as category_name, cat.slug as category_slug,
                        u.first_name as instructor_first_name, u.last_name as instructor_last_name
                 FROM courses c
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE c.id = ? LIMIT 1";
        
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
     * Get course by slug
     */
    public function getBySlug($slug) {
        $query = "SELECT c.*, cat.name as category_name, cat.slug as category_slug,
                        u.first_name as instructor_first_name, u.last_name as instructor_last_name
                 FROM courses c
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE c.slug = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $slug);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return $row;
        }
        
        return false;
    }
    
    /**
     * Get all published courses with filters
     */
    public function getAll($filters = []) {
        $where_conditions = ['c.is_published = 1'];
        $params = [];
        
        // Category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = 'cat.slug = ?';
            $params[] = $filters['category'];
        }
        
        // Level filter
        if (!empty($filters['level'])) {
            $where_conditions[] = 'c.level = ?';
            $params[] = $filters['level'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $where_conditions[] = '(c.title LIKE ? OR c.description LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Price filter
        if (!empty($filters['price_min'])) {
            $where_conditions[] = 'c.price >= ?';
            $params[] = $filters['price_min'];
        }
        
        if (!empty($filters['price_max'])) {
            $where_conditions[] = 'c.price <= ?';
            $params[] = $filters['price_max'];
        }
        
        // Rating filter
        if (!empty($filters['min_rating'])) {
            $where_conditions[] = 'c.average_rating >= ?';
            $params[] = $filters['min_rating'];
        }
        
        // Build WHERE clause
        $where_clause = implode(' AND ', $where_conditions);
        
        // Order by
        $order_by = 'c.created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popularity':
                    $order_by = 'c.total_students DESC';
                    break;
                case 'rating':
                    $order_by = 'c.average_rating DESC';
                    break;
                case 'price_low':
                    $order_by = 'c.price ASC';
                    break;
                case 'price_high':
                    $order_by = 'c.price DESC';
                    break;
                case 'title':
                    $order_by = 'c.title ASC';
                    break;
            }
        }
        
        // Pagination
        $page = intval($filters['page'] ?? 1);
        $limit = intval($filters['limit'] ?? DEFAULT_PAGE_SIZE);
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT c.*, cat.name as category_name, cat.slug as category_slug,
                        u.first_name as instructor_first_name, u.last_name as instructor_last_name
                 FROM courses c
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE {$where_clause}
                 ORDER BY {$order_by}
                 LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total 
                      FROM courses c
                      LEFT JOIN categories cat ON c.category_id = cat.id
                      WHERE {$where_clause}";
        
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'courses' => $courses,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ];
    }
    
    /**
     * Get featured courses
     */
    public function getFeatured($limit = 6) {
        $query = "SELECT c.*, cat.name as category_name, cat.slug as category_slug,
                        u.first_name as instructor_first_name, u.last_name as instructor_last_name
                 FROM courses c
                 LEFT JOIN categories cat ON c.category_id = cat.id
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE c.is_published = 1 AND c.is_featured = 1
                 ORDER BY c.total_students DESC, c.average_rating DESC
                 LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get course modules with lessons
     */
    public function getModulesWithLessons($courseId) {
        $query = "SELECT m.*, 
                        COUNT(l.id) as lesson_count,
                        SUM(l.video_duration) as total_duration
                 FROM course_modules m
                 LEFT JOIN lessons l ON m.id = l.module_id AND l.is_published = 1
                 WHERE m.course_id = ? AND m.is_published = 1
                 GROUP BY m.id
                 ORDER BY m.sort_order ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $courseId);
        $stmt->execute();
        
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get lessons for each module
        foreach ($modules as &$module) {
            $lessonQuery = "SELECT * FROM lessons 
                           WHERE module_id = ? AND is_published = 1 
                           ORDER BY sort_order ASC";
            $lessonStmt = $this->conn->prepare($lessonQuery);
            $lessonStmt->bindParam(1, $module['id']);
            $lessonStmt->execute();
            
            $module['lessons'] = $lessonStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $modules;
    }
    
    /**
     * Get course reviews
     */
    public function getReviews($courseId, $limit = 10, $offset = 0) {
        $query = "SELECT cr.*, u.first_name, u.last_name, u.profile_image
                 FROM course_reviews cr
                 JOIN users u ON cr.user_id = u.id
                 WHERE cr.course_id = ? AND cr.is_approved = 1
                 ORDER BY cr.created_at DESC
                 LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $courseId);
        $stmt->bindParam(2, $limit);
        $stmt->bindParam(3, $offset);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update course
     */
    public function update() {
        $query = "UPDATE courses 
                 SET title = :title,
                     slug = :slug,
                     description = :description,
                     short_description = :short_description,
                     thumbnail = :thumbnail,
                     category_id = :category_id,
                     level = :level,
                     language = :language,
                     price = :price,
                     original_price = :original_price,
                     duration_hours = :duration_hours,
                     badge = :badge,
                     requirements = :requirements,
                     what_you_learn = :what_you_learn,
                     target_audience = :target_audience,
                     certificate_available = :certificate_available,
                     difficulty_rating = :difficulty_rating,
                     is_published = :is_published,
                     is_featured = :is_featured,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->title = sanitizeInput($this->title);
        $this->slug = $this->generateSlug($this->title);
        $this->description = sanitizeInput($this->description);
        $this->short_description = sanitizeInput($this->short_description);
        
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':short_description', $this->short_description);
        $stmt->bindParam(':thumbnail', $this->thumbnail);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':level', $this->level);
        $stmt->bindParam(':language', $this->language);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':original_price', $this->original_price);
        $stmt->bindParam(':duration_hours', $this->duration_hours);
        $stmt->bindParam(':badge', $this->badge);
        $stmt->bindParam(':requirements', $this->requirements);
        $stmt->bindParam(':what_you_learn', $this->what_you_learn);
        $stmt->bindParam(':target_audience', $this->target_audience);
        $stmt->bindParam(':certificate_available', $this->certificate_available);
        $stmt->bindParam(':difficulty_rating', $this->difficulty_rating);
        $stmt->bindParam(':is_published', $this->is_published);
        $stmt->bindParam(':is_featured', $this->is_featured);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            logActivity($this->instructor_id, 'course_updated', 'course', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete course
     */
    public function delete() {
        $query = "DELETE FROM courses WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            logActivity($this->instructor_id, 'course_deleted', 'course', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update course statistics
     */
    public function updateStatistics($courseId) {
        // Update total students
        $query = "UPDATE courses 
                 SET total_students = (
                     SELECT COUNT(*) FROM course_enrollments 
                     WHERE course_id = ? AND is_active = 1
                 ) WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$courseId, $courseId]);
        
        // Update average rating and total reviews
        $query = "UPDATE courses 
                 SET average_rating = (
                     SELECT COALESCE(AVG(rating), 0) FROM course_reviews 
                     WHERE course_id = ? AND is_approved = 1
                 ),
                 total_reviews = (
                     SELECT COUNT(*) FROM course_reviews 
                     WHERE course_id = ? AND is_approved = 1
                 ) WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$courseId, $courseId, $courseId]);
        
        // Update total lessons count
        $query = "UPDATE courses 
                 SET total_lessons = (
                     SELECT COUNT(*) FROM lessons 
                     WHERE course_id = ? AND is_published = 1
                 ) WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$courseId, $courseId]);
    }
    
    /**
     * Generate slug from title
     */
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists($slug) {
        $query = "SELECT id FROM courses WHERE slug = ? AND id != ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$slug, $this->id ?? 0]);
        
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
    public function toArray() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'thumbnail' => $this->thumbnail,
            'category_id' => $this->category_id,
            'instructor_id' => $this->instructor_id,
            'level' => $this->level,
            'language' => $this->language,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'duration_hours' => $this->duration_hours,
            'total_lessons' => $this->total_lessons,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'badge' => $this->badge,
            'requirements' => $this->requirements,
            'what_you_learn' => $this->what_you_learn,
            'target_audience' => $this->target_audience,
            'certificate_available' => $this->certificate_available,
            'difficulty_rating' => $this->difficulty_rating,
            'average_rating' => $this->average_rating,
            'total_reviews' => $this->total_reviews,
            'total_students' => $this->total_students,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
?>