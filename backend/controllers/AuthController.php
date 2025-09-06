<?php
/**
 * Authentication Controller for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }
    
    /**
     * User Registration
     */
    public function register() {
        // Get POST data
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                sendJsonResponse([
                    'success' => false,
                    'message' => "Pole {$field} jest wymagane"
                ], 400);
            }
        }
        
        // Validate email format
        if (!isValidEmail($data['email'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nieprawidłowy format adresu email'
            ], 400);
        }
        
        // Validate password strength
        if (strlen($data['password']) < 8) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Hasło musi mieć co najmniej 8 znaków'
            ], 400);
        }
        
        // Check if email already exists
        if ($this->user->emailExists($data['email'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik z tym adresem email już istnieje'
            ], 400);
        }
        
        // Check if username already exists
        if ($this->user->usernameExists($data['username'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Ta nazwa użytkownika jest już zajęta'
            ], 400);
        }
        
        // Set user properties
        $this->user->username = $data['username'];
        $this->user->email = $data['email'];
        $this->user->password_hash = hashPassword($data['password']);
        $this->user->first_name = $data['first_name'];
        $this->user->last_name = $data['last_name'];
        $this->user->phone = $data['phone'] ?? '';
        $this->user->company = $data['company'] ?? '';
        $this->user->role = 'external';
        
        // Create user
        if ($this->user->create()) {
            // Send verification email (implement later)
            // $this->sendVerificationEmail();
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Konto zostało utworzone pomyślnie',
                'user' => $this->user->toArray()
            ], 201);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas tworzenia konta'
            ], 500);
        }
    }
    
    /**
     * User Login
     */
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Email i hasło są wymagane'
            ], 400);
        }
        
        // Get user by email
        if (!$this->user->getByEmail($data['email'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nieprawidłowe dane logowania'
            ], 401);
        }
        
        // Check if account is active
        if (!$this->user->is_active) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Konto jest nieaktywne'
            ], 401);
        }
        
        // Verify password
        if (!verifyPassword($data['password'], $this->user->password_hash)) {
            logActivity($this->user->id, 'login_failed', 'user', $this->user->id);
            sendJsonResponse([
                'success' => false,
                'message' => 'Nieprawidłowe dane logowania'
            ], 401);
        }
        
        // Update last login
        $this->user->updateLastLogin();
        
        // Create session
        $_SESSION['user_id'] = $this->user->id;
        $_SESSION['user_role'] = $this->user->role;
        $_SESSION['user_email'] = $this->user->email;
        
        // Log successful login
        logActivity($this->user->id, 'login_success', 'user', $this->user->id);
        
        // Generate JWT token (optional, for API usage)
        $token = $this->generateJWTToken($this->user->id, $this->user->role);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Zalogowano pomyślnie',
            'user' => $this->user->toArray(),
            'token' => $token
        ]);
    }
    
    /**
     * User Logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
        }
        
        // Destroy session
        session_destroy();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Wylogowano pomyślnie'
        ]);
    }
    
    /**
     * Get Current User
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zalogowany'
            ], 401);
        }
        
        if ($this->user->getById($_SESSION['user_id'])) {
            $userStats = $this->user->getStatistics();
            $userData = $this->user->toArray();
            $userData['statistics'] = $userStats;
            
            sendJsonResponse([
                'success' => true,
                'user' => $userData
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }
    }
    
    /**
     * Update User Profile
     */
    public function updateProfile() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Get current user
        if (!$this->user->getById($_SESSION['user_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }
        
        // Update allowed fields
        $this->user->first_name = $data['first_name'] ?? $this->user->first_name;
        $this->user->last_name = $data['last_name'] ?? $this->user->last_name;
        $this->user->phone = $data['phone'] ?? $this->user->phone;
        $this->user->company = $data['company'] ?? $this->user->company;
        
        if ($this->user->update()) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Profil został zaktualizowany',
                'user' => $this->user->toArray()
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji profilu'
            ], 500);
        }
    }
    
    /**
     * Change Password
     */
    public function changePassword() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (empty($data['current_password']) || empty($data['new_password'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Aktualne i nowe hasło są wymagane'
            ], 400);
        }
        
        // Get current user
        if (!$this->user->getById($_SESSION['user_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }
        
        // Verify current password
        if (!verifyPassword($data['current_password'], $this->user->password_hash)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Aktualne hasło jest nieprawidłowe'
            ], 400);
        }
        
        // Validate new password
        if (strlen($data['new_password']) < 8) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nowe hasło musi mieć co najmniej 8 znaków'
            ], 400);
        }
        
        // Update password
        if ($this->user->updatePassword($data['new_password'])) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Hasło zostało zmienione pomyślnie'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas zmiany hasła'
            ], 500);
        }
    }
    
    /**
     * Forgot Password
     */
    public function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['email'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Adres email jest wymagany'
            ], 400);
        }
        
        if ($this->user->getByEmail($data['email'])) {
            $resetToken = $this->user->setPasswordResetToken();
            
            if ($resetToken) {
                // Send reset email (implement later)
                // $this->sendPasswordResetEmail($resetToken);
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'Link do resetowania hasła został wysłany na Twój adres email'
                ]);
            }
        }
        
        // Always return success to prevent email enumeration
        sendJsonResponse([
            'success' => true,
            'message' => 'Link do resetowania hasła został wysłany na Twój adres email'
        ]);
    }
    
    /**
     * Reset Password
     */
    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['token']) || empty($data['new_password'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Token i nowe hasło są wymagane'
            ], 400);
        }
        
        if (strlen($data['new_password']) < 8) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Hasło musi mieć co najmniej 8 znaków'
            ], 400);
        }
        
        if ($this->user->resetPassword($data['token'], $data['new_password'])) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Hasło zostało zresetowane pomyślnie'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nieprawidłowy lub wygasły token'
            ], 400);
        }
    }
    
    /**
     * Verify Email
     */
    public function verifyEmail() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Token weryfikacyjny jest wymagany'
            ], 400);
        }
        
        if ($this->user->verifyEmail($token)) {
            sendJsonResponse([
                'success' => true,
                'message' => 'Adres email został zweryfikowany pomyślnie'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nieprawidłowy token weryfikacyjny'
            ], 400);
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role) {
        return $this->isAuthenticated() && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->hasRole('admin');
    }
    
    /**
     * Check if user is employee
     */
    public function isEmployee() {
        return $this->hasRole('employee');
    }
    
    /**
     * Check if user is external
     */
    public function isExternal() {
        return $this->hasRole('external');
    }
    
    /**
     * Check if user has admin or employee privileges
     */
    public function hasStaffAccess() {
        return $this->isAdmin() || $this->isEmployee();
    }
    
    /**
     * Generate JWT Token
     */
    private function generateJWTToken($userId, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET_KEY, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Verify JWT Token
     */
    public function verifyJWTToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $validSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET_KEY, true);
        $validBase64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
        
        if (!hash_equals($signature, $validBase64Signature)) {
            return false;
        }
        
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if ($payloadData['exp'] < time()) {
            return false;
        }
        
        return $payloadData;
    }
    
    /**
     * Get User Enrollments
     */
    public function getUserEnrollments() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zalogowany'
            ], 401);
        }
        
        if ($this->user->getById($_SESSION['user_id'])) {
            $enrollments = $this->user->getEnrollments();
            
            sendJsonResponse([
                'success' => true,
                'enrollments' => $enrollments
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }
    }
    
    /**
     * Get User Certificates
     */
    public function getUserCertificates() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Nie jesteś zalogowany'
            ], 401);
        }
        
        if ($this->user->getById($_SESSION['user_id'])) {
            $certificates = $this->user->getCertificates();
            
            sendJsonResponse([
                'success' => true,
                'certificates' => $certificates
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }
    }
}
?>