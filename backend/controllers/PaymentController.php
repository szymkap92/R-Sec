<?php
/**
 * Payment Controller for R-SEC Academy
 * Basic Stripe integration for course payments
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Enrollment.php';

class PaymentController {
    private $db;
    private $course;
    private $enrollment;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->course = new Course($this->db);
        $this->enrollment = new Enrollment($this->db);
    }
    
    /**
     * Create payment intent for course purchase
     */
    public function createPaymentIntent() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany aby dokonać płatności'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $courseId = $data['course_id'] ?? null;
        
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        // Get course details
        if (!$this->course->getById($courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        // Check if user is already enrolled
        if ($this->enrollment->isUserEnrolled($_SESSION['user_id'], $courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Jesteś już zapisany na ten kurs'
            ], 400);
        }
        
        $amount = $this->course->price * 100; // Convert to cents
        
        try {
            // In production, you would use actual Stripe SDK here
            // For now, we'll simulate payment intent creation
            $paymentIntent = [
                'id' => 'pi_' . generateRandomString(16),
                'client_secret' => 'pi_' . generateRandomString(16) . '_secret_' . generateRandomString(16),
                'amount' => $amount,
                'currency' => strtolower(DEFAULT_COURSE_CURRENCY),
                'status' => 'requires_payment_method'
            ];
            
            // Store payment intent in database
            $query = "INSERT INTO payment_intents 
                     SET intent_id = ?, user_id = ?, course_id = ?, amount = ?, currency = ?, status = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $paymentIntent['id'],
                $_SESSION['user_id'],
                $courseId,
                $amount,
                $paymentIntent['currency'],
                $paymentIntent['status']
            ]);
            
            sendJsonResponse([
                'success' => true,
                'payment_intent' => $paymentIntent,
                'course' => $this->course->toArray()
            ]);
            
        } catch (Exception $e) {
            error_log('Payment Intent Error: ' . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas tworzenia płatności'
            ], 500);
        }
    }
    
    /**
     * Confirm payment and create enrollment
     */
    public function confirmPayment() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $paymentIntentId = $data['payment_intent_id'] ?? null;
        
        if (!$paymentIntentId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID płatności jest wymagane'
            ], 400);
        }
        
        try {
            // Get payment intent from database
            $query = "SELECT * FROM payment_intents WHERE intent_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$paymentIntentId, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Płatność nie została znaleziona'
                ], 404);
            }
            
            $paymentIntent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // In production, you would verify payment with Stripe API here
            // For demonstration, we'll simulate successful payment
            $paymentSuccess = true;
            
            if ($paymentSuccess) {
                // Update payment status
                $updateQuery = "UPDATE payment_intents SET status = 'succeeded', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->execute([$paymentIntent['id']]);
                
                // Create enrollment
                $this->enrollment->user_id = $_SESSION['user_id'];
                $this->enrollment->course_id = $paymentIntent['course_id'];
                $this->enrollment->enrollment_type = 'paid';
                $this->enrollment->price_paid = $paymentIntent['amount'] / 100; // Convert back from cents
                $this->enrollment->payment_method = 'stripe';
                $this->enrollment->payment_status = 'completed';
                
                if ($this->enrollment->create()) {
                    // Update course statistics
                    $this->course->updateStatistics($paymentIntent['course_id']);
                    
                    // Get course details for response
                    $this->course->getById($paymentIntent['course_id']);
                    
                    sendJsonResponse([
                        'success' => true,
                        'message' => 'Płatność została zrealizowana pomyślnie. Zostałeś zapisany na kurs!',
                        'enrollment' => $this->enrollment->toArray(),
                        'course' => $this->course->toArray()
                    ]);
                } else {
                    sendJsonResponse([
                        'success' => false,
                        'message' => 'Błąd podczas zapisywania na kurs'
                    ], 500);
                }
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Płatność nie powiodła się'
                ], 400);
            }
            
        } catch (Exception $e) {
            error_log('Payment Confirmation Error: ' . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas potwierdzania płatności'
            ], 500);
        }
    }
    
    /**
     * Handle free course enrollment
     */
    public function enrollFree() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany aby zapisać się na kurs'
            ], 401);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $courseId = $data['course_id'] ?? null;
        
        if (!$courseId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID kursu jest wymagane'
            ], 400);
        }
        
        // Get course details
        if (!$this->course->getById($courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Kurs nie został znaleziony'
            ], 404);
        }
        
        // Check if course is free
        if ($this->course->price > 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Ten kurs wymaga płatności'
            ], 400);
        }
        
        // Check if user is already enrolled
        if ($this->enrollment->isUserEnrolled($_SESSION['user_id'], $courseId)) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Jesteś już zapisany na ten kurs'
            ], 400);
        }
        
        // Create free enrollment
        $this->enrollment->user_id = $_SESSION['user_id'];
        $this->enrollment->course_id = $courseId;
        $this->enrollment->enrollment_type = 'free';
        $this->enrollment->price_paid = 0;
        $this->enrollment->payment_method = 'free';
        $this->enrollment->payment_status = 'completed';
        
        if ($this->enrollment->create()) {
            // Update course statistics
            $this->course->updateStatistics($courseId);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Zostałeś zapisany na darmowy kurs!',
                'enrollment' => $this->enrollment->toArray(),
                'course' => $this->course->toArray()
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas zapisywania na kurs'
            ], 500);
        }
    }
    
    /**
     * Get user's payment history
     */
    public function getPaymentHistory() {
        if (!$this->isAuthenticated()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Musisz być zalogowany'
            ], 401);
        }
        
        $query = "SELECT pi.*, c.title as course_title, c.thumbnail as course_thumbnail
                 FROM payment_intents pi
                 JOIN courses c ON pi.course_id = c.id
                 WHERE pi.user_id = ? AND pi.status = 'succeeded'
                 ORDER BY pi.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'payments' => $payments
        ]);
    }
    
    /**
     * Get payment statistics (Admin only)
     */
    public function getPaymentStats() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $stats = [];
        
        // Total revenue
        $revenueQuery = "SELECT 
                            SUM(CASE WHEN status = 'succeeded' THEN amount/100 ELSE 0 END) as total_revenue,
                            COUNT(CASE WHEN status = 'succeeded' THEN 1 END) as successful_payments,
                            COUNT(*) as total_payment_attempts
                        FROM payment_intents";
        $revenueStmt = $this->db->prepare($revenueQuery);
        $revenueStmt->execute();
        $stats = $revenueStmt->fetch(PDO::FETCH_ASSOC);
        
        // Monthly revenue
        $monthlyQuery = "SELECT 
                            YEAR(created_at) as year,
                            MONTH(created_at) as month,
                            SUM(amount/100) as revenue,
                            COUNT(*) as payments
                        FROM payment_intents 
                        WHERE status = 'succeeded' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY YEAR(created_at), MONTH(created_at)
                        ORDER BY year ASC, month ASC";
        $monthlyStmt = $this->db->prepare($monthlyQuery);
        $monthlyStmt->execute();
        $stats['monthly_data'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'statistics' => $stats
        ]);
    }
    
    /**
     * Process refund (Admin only)
     */
    public function processRefund() {
        if (!$this->isAdmin()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Brak uprawnień administratora'
            ], 403);
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $enrollmentId = $data['enrollment_id'] ?? null;
        $reason = $data['reason'] ?? '';
        
        if (!$enrollmentId) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID zapisania jest wymagane'
            ], 400);
        }
        
        try {
            // Get enrollment details
            $enrollmentQuery = "SELECT * FROM course_enrollments WHERE id = ?";
            $enrollmentStmt = $this->db->prepare($enrollmentQuery);
            $enrollmentStmt->execute([$enrollmentId]);
            
            if ($enrollmentStmt->rowCount() === 0) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Zapis nie został znaleziony'
                ], 404);
            }
            
            $enrollment = $enrollmentStmt->fetch(PDO::FETCH_ASSOC);
            
            // In production, you would process actual refund with Stripe here
            
            // Cancel enrollment
            $this->enrollment->cancel($enrollmentId, $reason);
            
            // Log refund
            logActivity($_SESSION['user_id'], 'refund_processed', 'enrollment', $enrollmentId, [
                'amount' => $enrollment['price_paid'],
                'reason' => $reason
            ]);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Zwrot został przetworzony pomyślnie'
            ]);
            
        } catch (Exception $e) {
            error_log('Refund Error: ' . $e->getMessage());
            sendJsonResponse([
                'success' => false,
                'message' => 'Błąd podczas przetwarzania zwrotu'
            ], 500);
        }
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
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
?>