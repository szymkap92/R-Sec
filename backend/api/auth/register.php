<?php
/**
 * Registration API Endpoint
 */

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $authController = new AuthController();
    $authController->register();
    
} catch (Exception $e) {
    error_log("Registration API Error: " . $e->getMessage());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Wystąpił błąd podczas rejestracji'
    ], 500);
}
?>