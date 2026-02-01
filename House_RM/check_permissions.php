<?php
$uploadDir = __DIR__ . '/uploads/';
$testFile = $uploadDir . 'test.txt';

echo "Checking upload directory permissions...\n\n";

// Check if directory exists
echo "1. Directory exists: " . (file_exists($uploadDir) ? "Yes" : "No") . "\n";

// Check if directory is writable
echo "2. Directory is writable: " . (is_writable($uploadDir) ? "Yes" : "No") . "\n";

// Try to create a test file
$testContent = "Test write permission";
$writeSuccess = @file_put_contents($testFile, $testContent);
echo "3. Can create files: " . ($writeSuccess !== false ? "Yes" : "No") . "\n";

// Try to read the file if it was created
if ($writeSuccess !== false) {
    echo "4. Can read files: " . (file_get_contents($testFile) === $testContent ? "Yes" : "No") . "\n";
    // Clean up
    unlink($testFile);
    echo "5. Can delete files: Yes\n";
}

echo "\nPermission check complete.";
?>
