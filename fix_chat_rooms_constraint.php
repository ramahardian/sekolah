<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>Fix Chat Rooms Foreign Key Constraint</h1>";

try {
    // Check current constraint
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'chat_rooms'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current Constraints:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
    
    foreach ($constraints as $constraint) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "." . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
        echo "</tr>";
        
        // Drop the constraint if it references 'classes'
        if ($constraint['REFERENCED_TABLE_NAME'] === 'classes') {
            echo "<tr><td colspan='3' style='color: red;'>";
            echo "Dropping constraint: " . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "<br>";
            
            $dropSql = "ALTER TABLE chat_rooms DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME'];
            echo "SQL: " . htmlspecialchars($dropSql) . "<br>";
            
            $pdo->exec($dropSql);
            echo "✓ Constraint dropped successfully<br>";
            echo "</td></tr>";
        }
    }
    echo "</table>";
    
    // Add correct constraint if not exists
    echo "<h2>Adding Correct Constraint:</h2>";
    $addSql = "ALTER TABLE chat_rooms ADD CONSTRAINT fk_chat_rooms_kelas FOREIGN KEY (class_id) REFERENCES kelas(id) ON DELETE CASCADE";
    echo "SQL: " . htmlspecialchars($addSql) . "<br>";
    
    try {
        $pdo->exec($addSql);
        echo "✓ Correct constraint added successfully<br>";
    } catch (Exception $e) {
        echo "⚠ Constraint might already exist: " . $e->getMessage() . "<br>";
    }
    
    // Verify final state
    echo "<h2>Final Constraints:</h2>";
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'chat_rooms'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $finalConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Constraint</th><th>Column</th><th>References</th></tr>";
    
    foreach ($finalConstraints as $constraint) {
        $color = $constraint['REFERENCED_TABLE_NAME'] === 'kelas' ? 'green' : 'red';
        echo "<tr style='color: $color;'>";
        echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . "." . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Test Room Creation:</h2>";
    
    // Test creating a room
    $testClassId = 1;
    $testUserId = 1;
    
    // First check if test class exists
    $stmt = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE id = ?");
    $stmt->execute([$testClassId]);
    $testClass = $stmt->fetch();
    
    if ($testClass) {
        echo "✓ Test class found: " . htmlspecialchars($testClass['nama_kelas']) . "<br>";
        
        // Clean up any existing test room
        $pdo->prepare("DELETE FROM chat_rooms WHERE class_id = ? AND room_name LIKE 'TEST%'")->execute([$testClassId]);
        
        // Try to create a test room
        $roomCode = 'TEST_' . time();
        $roomName = 'TEST Kelas ' . $testClass['nama_kelas'];
        
        $stmt = $pdo->prepare("
            INSERT INTO chat_rooms (class_id, room_name, room_code, created_by) 
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([$testClassId, $roomName, $roomCode, $testUserId]);
        
        if ($result) {
            $roomId = $pdo->lastInsertId();
            echo "✓ Test room created successfully! Room ID: $roomId<br>";
            
            // Clean up test room
            $pdo->prepare("DELETE FROM chat_rooms WHERE id = ?")->execute([$roomId]);
            echo "✓ Test room cleaned up<br>";
        } else {
            echo "✗ Failed to create test room<br>";
            print_r($stmt->errorInfo());
        }
    } else {
        echo "⚠ No test class found with ID $testClassId. Please create a class first.<br>";
    }
    
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<p>You can now try creating rooms from the video chat interface.</p>";
    echo "<p><a href='index.php?page=video-classes'>Go to Video Classes</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
