<?php
/**
 * API Router for R-SEC Academy
 */

require_once __DIR__ . '/../config/config.php';

// CORS Headers (already handled in config.php but ensure they're set)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';

// Remove leading slash and split path
$path = trim($path, '/');
$pathParts = explode('/', $path);

// Route the request
try {
    switch ($pathParts[0]) {
        case 'auth':
            require_once __DIR__ . '/../controllers/AuthController.php';
            $controller = new AuthController();
            routeAuth($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'courses':
            require_once __DIR__ . '/../controllers/CourseController.php';
            $controller = new CourseController();
            routeCourse($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'enrollments':
            require_once __DIR__ . '/../controllers/EnrollmentController.php';
            $controller = new EnrollmentController();
            routeEnrollment($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'lessons':
            require_once __DIR__ . '/../controllers/LessonController.php';
            $controller = new LessonController();
            routeLesson($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'categories':
            require_once __DIR__ . '/../models/Category.php';
            routeCategory($method, array_slice($pathParts, 1));
            break;
            
        case 'admin':
            require_once __DIR__ . '/../controllers/AdminController.php';
            $controller = new AdminController();
            routeAdmin($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'payments':
            require_once __DIR__ . '/../controllers/PaymentController.php';
            $controller = new PaymentController();
            routePayment($controller, $method, array_slice($pathParts, 1));
            break;
            
        case 'health':
            sendJsonResponse([
                'success' => true,
                'message' => 'R-SEC Academy API is running',
                'version' => APP_VERSION,
                'timestamp' => date('c')
            ]);
            break;
            
        default:
            sendJsonResponse([
                'success' => false,
                'message' => 'Endpoint not found',
                'available_endpoints' => [
                    'auth' => ['register', 'login', 'logout', 'profile', 'change-password'],
                    'courses' => ['list', 'featured', 'details', 'create', 'update', 'delete'],
                    'enrollments' => ['enroll', 'list', 'progress', 'complete'],
                    'lessons' => ['details', 'progress', 'complete', 'course-lessons'],
                    'categories' => ['list'],
                    'admin' => ['dashboard-stats', 'users', 'courses-management', 'activity-logs'],
                    'payments' => ['create-intent', 'confirm', 'free-enroll', 'history', 'stats', 'refund']
                ]
            ], 404);
    }
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Internal server error'
    ], 500);
}

/**
 * Route authentication requests
 */
function routeAuth($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'register':
                    $controller->register();
                    break;
                case 'login':
                    $controller->login();
                    break;
                case 'logout':
                    $controller->logout();
                    break;
                case 'forgot-password':
                    $controller->forgotPassword();
                    break;
                case 'reset-password':
                    $controller->resetPassword();
                    break;
                case 'change-password':
                    $controller->changePassword();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid auth action'], 400);
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'user':
                case 'profile':
                    $controller->getCurrentUser();
                    break;
                case 'enrollments':
                    $controller->getUserEnrollments();
                    break;
                case 'certificates':
                    $controller->getUserCertificates();
                    break;
                case 'verify-email':
                    $controller->verifyEmail();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid auth action'], 400);
            }
            break;
            
        case 'PUT':
            if ($action === 'profile') {
                $controller->updateProfile();
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid auth action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route course requests
 */
function routeCourse($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case '':
                case 'list':
                    $controller->getCourses();
                    break;
                case 'featured':
                    $controller->getFeaturedCourses();
                    break;
                case 'details':
                    $controller->getCourse();
                    break;
                case 'reviews':
                    $controller->getCourseReviews();
                    break;
                default:
                    // Check if it's a specific course ID
                    if (is_numeric($action)) {
                        $_GET['id'] = $action;
                        $controller->getCourse();
                    } else {
                        sendJsonResponse(['success' => false, 'message' => 'Invalid course action'], 400);
                    }
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'create':
                    $controller->createCourse();
                    break;
                case 'update-stats':
                    $controller->updateCourseStatistics();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid course action'], 400);
            }
            break;
            
        case 'PUT':
            if (is_numeric($action)) {
                $_GET['id'] = $action;
                $controller->updateCourse();
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid course action'], 400);
            }
            break;
            
        case 'DELETE':
            if (is_numeric($action)) {
                $_GET['id'] = $action;
                $controller->deleteCourse();
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid course action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route enrollment requests
 */
function routeEnrollment($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case '':
                case 'list':
                    $controller->getUserEnrollments();
                    break;
                case 'status':
                    $controller->checkEnrollmentStatus();
                    break;
                case 'details':
                    $controller->getEnrollment();
                    break;
                case 'stats':
                case 'statistics':
                    $controller->getStatistics();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid enrollment action'], 400);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'enroll':
                    $controller->enrollInCourse();
                    break;
                case 'complete':
                    $controller->markAsCompleted();
                    break;
                case 'cancel':
                    $controller->cancelEnrollment();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid enrollment action'], 400);
            }
            break;
            
        case 'PUT':
            switch ($action) {
                case 'progress':
                    $controller->updateProgress();
                    break;
                case 'watch-time':
                    $controller->updateWatchTime();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid enrollment action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route lesson requests
 */
function routeLesson($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'details':
                    $controller->getLesson();
                    break;
                case 'course-lessons':
                    $controller->getCourseLessons();
                    break;
                case 'progress':
                    $controller->getCourseProgress();
                    break;
                default:
                    // Check if it's a specific lesson ID
                    if (is_numeric($action)) {
                        $_GET['id'] = $action;
                        $controller->getLesson();
                    } else {
                        sendJsonResponse(['success' => false, 'message' => 'Invalid lesson action'], 400);
                    }
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'complete':
                    $controller->markCompleted();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid lesson action'], 400);
            }
            break;
            
        case 'PUT':
            if ($action === 'progress') {
                $controller->updateProgress();
            } else {
                sendJsonResponse(['success' => false, 'message' => 'Invalid lesson action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route category requests
 */
function routeCategory($method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            $database = new Database();
            $db = $database->getConnection();
            $category = new Category($db);
            
            switch ($action) {
                case '':
                case 'list':
                    $categories = $category->getAll();
                    sendJsonResponse([
                        'success' => true,
                        'categories' => $categories
                    ]);
                    break;
                default:
                    if (is_numeric($action)) {
                        $categoryData = $category->getById($action);
                        if ($categoryData) {
                            sendJsonResponse([
                                'success' => true,
                                'category' => $categoryData
                            ]);
                        } else {
                            sendJsonResponse(['success' => false, 'message' => 'Category not found'], 404);
                        }
                    } else {
                        sendJsonResponse(['success' => false, 'message' => 'Invalid category action'], 400);
                    }
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route admin requests
 */
function routeAdmin($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'dashboard-stats':
                    $controller->getDashboardStats();
                    break;
                case 'users':
                    $controller->getUsers();
                    break;
                case 'courses-management':
                    $controller->getCoursesManagement();
                    break;
                case 'activity-logs':
                    $controller->getActivityLogs();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid admin action'], 400);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'update-user-status':
                    $controller->updateUserStatus();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid admin action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}

/**
 * Route payment requests
 */
function routePayment($controller, $method, $pathParts) {
    $action = isset($pathParts[0]) ? $pathParts[0] : '';
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'history':
                    $controller->getPaymentHistory();
                    break;
                case 'stats':
                    $controller->getPaymentStats();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid payment action'], 400);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'create-intent':
                    $controller->createPaymentIntent();
                    break;
                case 'confirm':
                    $controller->confirmPayment();
                    break;
                case 'free-enroll':
                    $controller->enrollFree();
                    break;
                case 'refund':
                    $controller->processRefund();
                    break;
                default:
                    sendJsonResponse(['success' => false, 'message' => 'Invalid payment action'], 400);
            }
            break;
            
        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
}
?>