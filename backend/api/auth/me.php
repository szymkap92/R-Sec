<?php
/**
 * Current User API Endpoint
 */

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $authController = new AuthController();
    $authController->getCurrentUser();
    
} catch (Exception $e) {
    error_log("Current User API Error: " . $e->getMessage());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'Wystąpił błąd podczas pobierania danych użytkownika'
    ], 500);
}
?>