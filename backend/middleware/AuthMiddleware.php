<?php
/**
 * Authentication Middleware
 */

class AuthMiddleware {
    
    /**
     * Check if user is authenticated
     */
    public static function requireAuth() {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Wymagane zalogowanie',
                'redirect' => 'login.html'
            ], 401);
        }
    }
    
    /**
     * Check if user has specific role
     */
    public static function requireRole($role) {
        self::requireAuth();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak wymaganych uprawnień'
            ], 403);
        }
    }
    
    /**
     * Check if user is admin
     */
    public static function requireAdmin() {
        self::requireRole('admin');
    }
    
    /**
     * Check if user is employee or admin
     */
    public static function requireStaff() {
        self::requireAuth();
        
        $allowedRoles = ['admin', 'employee'];
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień pracownika'
            ], 403);
        }
    }
    
    /**
     * Check if user can access specific resource
     */
    public static function canAccess($resource, $action = 'read') {
        self::requireAuth();
        
        $userRole = $_SESSION['user_role'];
        $userId = $_SESSION['user_id'];
        
        // Define permissions matrix
        $permissions = [
            'admin' => [
                'users' => ['read', 'create', 'update', 'delete'],
                'courses' => ['read', 'create', 'update', 'delete'],
                'enrollments' => ['read', 'create', 'update', 'delete'],
                'payments' => ['read', 'create', 'update', 'delete'],
                'reports' => ['read', 'create'],
                'settings' => ['read', 'update'],
                'logs' => ['read']
            ],
            'employee' => [
                'users' => ['read', 'update'], // Can view and moderate users
                'courses' => ['read', 'update'], // Can view and moderate courses
                'enrollments' => ['read'],
                'payments' => ['read'],
                'reports' => ['read', 'create'],
                'settings' => ['read']
            ],
            'external' => [
                'courses' => ['read'], // Can only view enrolled courses
                'enrollments' => ['read'], // Can view own enrollments
                'profile' => ['read', 'update'], // Can manage own profile
                'progress' => ['read', 'update'] // Can view and update own progress
            ]
        ];
        
        // Check if user role has permission for this resource and action
        if (!isset($permissions[$userRole][$resource])) {
            return false;
        }
        
        return in_array($action, $permissions[$userRole][$resource]);
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($resource, $action = 'read') {
        if (!self::canAccess($resource, $action)) {
            sendJsonResponse([
                'success' => false,
                'message' => "Brak uprawnień do wykonania akcji '{$action}' na zasobie '{$resource}'"
            ], 403);
        }
    }
    
    /**
     * Check if user can access own resource or has staff privileges
     */
    public static function requireOwnershipOrStaff($resourceUserId) {
        self::requireAuth();
        
        $currentUserId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        // Admin and employee can access any resource
        if (in_array($userRole, ['admin', 'employee'])) {
            return true;
        }
        
        // External users can only access their own resources
        if ($currentUserId != $resourceUserId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Możesz uzyskać dostęp tylko do własnych zasobów'
            ], 403);
        }
        
        return true;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole() {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if session is valid and refresh if needed
     */
    public static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (optional - implement if needed)
        $sessionTimeout = 24 * 60 * 60; // 24 hours
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $sessionTimeout) {
            
            // Session expired
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = null) {
        $userId = self::getCurrentUserId();
        $userRole = self::getCurrentUserRole();
        
        $additionalData = [
            'event' => $event,
            'user_role' => $userRole,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'details' => $details
        ];
        
        if ($userId) {
            logActivity($userId, 'security_event', 'security', null, $additionalData);
        } else {
            error_log("Security Event: " . json_encode($additionalData));
        }
    }
}
?>