<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Prosty system plików do przechowywania danych o sesjach online
$sessionsFile = __DIR__ . '/online_sessions.json';
$timeout = 30; // 30 sekund timeout

// Funkcja do czyszczenia starych sesji
function cleanExpiredSessions($sessions, $timeout) {
    $currentTime = time();
    return array_filter($sessions, function($session) use ($currentTime, $timeout) {
        return ($currentTime - $session['lastSeen']) < $timeout;
    });
}

// Funkcja do zapisywania sesji
function saveSessions($sessions, $file) {
    file_put_contents($file, json_encode($sessions, JSON_PRETTY_PRINT));
}

// Funkcja do wczytywania sesji
function loadSessions($file) {
    if (!file_exists($file)) {
        return [];
    }
    $data = file_get_contents($file);
    return json_decode($data, true) ?: [];
}

// Główna logika
try {
    $sessions = loadSessions($sessionsFile);
    $sessions = cleanExpiredSessions($sessions, $timeout);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Heartbeat - aktualizuj sesję
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['sessionId'] ?? null;
        
        if ($sessionId) {
            $sessions[$sessionId] = [
                'lastSeen' => time(),
                'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];
            saveSessions($sessions, $sessionsFile);
        }
    }
    
    // Zwróć aktualną liczbę osób online
    $onlineCount = count($sessions);
    
    echo json_encode([
        'success' => true,
        'onlineCount' => $onlineCount,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'onlineCount' => 0
    ]);
}
?>