<?php
/**
 * Database Initialization Script
 */

require_once __DIR__ . '/../config/config.php';

try {
    // Connect to MySQL without database selection
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4",
        "root",  // Change in production
        "",      // Change in production
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "✓ Connected to MySQL server\n";
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/../../database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: {$schemaFile}");
    }
    
    $schema = file_get_contents($schemaFile);
    
    // Split by statements and execute each one
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($statement) {
            return !empty($statement) && !str_starts_with($statement, '--');
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Database schema created successfully\n";
    
    // Check if database and tables were created
    $database = new Database();
    $db = $database->getConnection();
    
    // Test data
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "✓ Admin user found in database\n";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "✓ Categories inserted successfully\n";
    }
    
    echo "\n=== Database Setup Complete ===\n";
    echo "Default login credentials:\n";
    echo "Administrator: admin@r-sec.pl / admin123\n";
    echo "Employee: employee@r-sec.pl / employee123\n";
    echo "External User: external@example.com / external123\n";
    echo "\nIMPORTANT: Change these passwords in production!\n";
    
} catch (Exception $e) {
    echo "✗ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>