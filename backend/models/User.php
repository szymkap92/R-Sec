<?php
/**
 * User Model for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

class User {
    private $conn;
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $company;
    public $role;
    public $profile_image;
    public $is_active;
    public $email_verified;
    public $created_at;
    public $updated_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new user
     */
    public function create() {
        $query = "INSERT INTO users 
                 SET username = :username,
                     email = :email,
                     password_hash = :password_hash,
                     first_name = :first_name,
                     last_name = :last_name,
                     phone = :phone,
                     company = :company,
                     role = :role,
                     email_verification_token = :email_verification_token";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->username = sanitizeInput($this->username);
        $this->email = sanitizeInput($this->email);
        $this->first_name = sanitizeInput($this->first_name);
        $this->last_name = sanitizeInput($this->last_name);
        $this->phone = sanitizeInput($this->phone);
        $this->company = sanitizeInput($this->company);
        
        // Generate verification token
        $verification_token = generateRandomString(64);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':company', $this->company);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':email_verification_token', $verification_token);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            logActivity($this->id, 'user_registered', 'user', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $query = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $query = "SELECT * FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->setProperties($row);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update user
     */
    public function update() {
        $query = "UPDATE users 
                 SET first_name = :first_name,
                     last_name = :last_name,
                     phone = :phone,
                     company = :company,
                     profile_image = :profile_image,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize data
        $this->first_name = sanitizeInput($this->first_name);
        $this->last_name = sanitizeInput($this->last_name);
        $this->phone = sanitizeInput($this->phone);
        $this->company = sanitizeInput($this->company);
        
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':company', $this->company);
        $stmt->bindParam(':profile_image', $this->profile_image);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            logActivity($this->id, 'user_updated', 'user', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update password
     */
    public function updatePassword($newPassword) {
        $query = "UPDATE users SET password_hash = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $hashedPassword = hashPassword($newPassword);
        $stmt->bindParam(1, $hashedPassword);
        $stmt->bindParam(2, $this->id);
        
        if($stmt->execute()) {
            logActivity($this->id, 'password_changed', 'user', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($token) {
        $query = "UPDATE users 
                 SET email_verified = TRUE, 
                     email_verification_token = NULL 
                 WHERE email_verification_token = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            logActivity($this->id, 'email_verified', 'user', $this->id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Set password reset token
     */
    public function setPasswordResetToken() {
        $token = generateRandomString(64);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "UPDATE users 
                 SET password_reset_token = ?, 
                     password_reset_expires = ? 
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->bindParam(2, $expires);
        $stmt->bindParam(3, $this->id);
        
        if($stmt->execute()) {
            return $token;
        }
        
        return false;
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        $query = "UPDATE users 
                 SET password_hash = ?, 
                     password_reset_token = NULL, 
                     password_reset_expires = NULL 
                 WHERE password_reset_token = ? 
                 AND password_reset_expires > NOW()";
        
        $stmt = $this->conn->prepare($query);
        $hashedPassword = hashPassword($newPassword);
        
        $stmt->bindParam(1, $hashedPassword);
        $stmt->bindParam(2, $token);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            logActivity(null, 'password_reset', 'user', null, ['token' => $token]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin() {
        $query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        
        return $stmt->execute();
    }
    
    /**
     * Get user enrollments
     */
    public function getEnrollments() {
        $query = "SELECT ce.*, c.title, c.thumbnail, c.slug, c.level 
                 FROM course_enrollments ce
                 JOIN courses c ON ce.course_id = c.id
                 WHERE ce.user_id = ? AND ce.is_active = TRUE
                 ORDER BY ce.enrolled_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user certificates
     */
    public function getCertificates() {
        $query = "SELECT cert.*, c.title 
                 FROM certificates cert
                 JOIN courses c ON cert.course_id = c.id
                 WHERE cert.user_id = ? AND cert.is_valid = TRUE
                 ORDER BY cert.issued_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $query = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get user statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total enrolled courses
        $query = "SELECT COUNT(*) as total_courses FROM course_enrollments WHERE user_id = ? AND is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $stats['total_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_courses'];
        
        // Completed courses
        $query = "SELECT COUNT(*) as completed_courses FROM course_enrollments WHERE user_id = ? AND completed_at IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $stats['completed_courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['completed_courses'];
        
        // Total certificates
        $query = "SELECT COUNT(*) as total_certificates FROM certificates WHERE user_id = ? AND is_valid = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $stats['total_certificates'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_certificates'];
        
        // Total study time (in minutes)
        $query = "SELECT SUM(watch_time) as total_watch_time FROM lesson_progress WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $stats['total_watch_time'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['total_watch_time'] ?? 0);
        
        return $stats;
    }
    
    /**
     * Set object properties from array
     */
    private function setProperties($data) {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->email = $data['email'];
        $this->password_hash = $data['password_hash'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
        $this->phone = $data['phone'];
        $this->company = $data['company'];
        $this->role = $data['role'];
        $this->profile_image = $data['profile_image'];
        $this->is_active = $data['is_active'];
        $this->email_verified = $data['email_verified'];
        $this->created_at = $data['created_at'];
        $this->updated_at = $data['updated_at'];
    }
    
    /**
     * Convert to array (exclude sensitive data)
     */
    public function toArray($includeSensitive = false) {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'company' => $this->company,
            'role' => $this->role,
            'profile_image' => $this->profile_image,
            'is_active' => $this->is_active,
            'email_verified' => $this->email_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
        
        if ($includeSensitive) {
            $data['password_hash'] = $this->password_hash;
        }
        
        return $data;
    }
}
?>