<?php
/**
 * Category Model for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

class Category {
    private $conn;
    
    // Category properties
    public $id;
    public $name;
    public $slug;
    public $description;
    public $icon;
    public $color;
    public $sort_order;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get all active categories
     */
    public function getAll() {
        $query = "SELECT *, 
                        (SELECT COUNT(*) FROM courses WHERE category_id = categories.id AND is_published = 1) as course_count
                 FROM categories 
                 WHERE is_active = 1 
                 ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get category by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM categories WHERE id = ? LIMIT 1";
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
     * Get category by slug
     */
    public function getBySlug($slug) {
        $query = "SELECT * FROM categories WHERE slug = ? LIMIT 1";
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
     * Set object properties from array
     */
    private function setProperties($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
?>