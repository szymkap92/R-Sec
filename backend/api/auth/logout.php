<?php
/**
 * Logout API Endpoint
 */

// Allow POST and GET requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $authController = new AuthController();
    $authController->logout();
    
} catch (Exception $e) {
    error_log("Logout API Error: " . $e->getMessage());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Wystąpił błąd podczas wylogowywania'
    ], 500);
}
?>