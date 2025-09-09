<?php
echo "<h2>🔧 Fix Header DB Issue</h2>";

$files_to_fix = [
    'blog.php',
    'pages.php', 
    'search.php',
    'shop.php'
];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if already has session_start and require_once db.php
        if (strpos($content, 'session_start()') === false || strpos($content, "require_once 'includes/db.php'") === false) {
            
            // Find the position of <!DOCTYPE html>
            $doctype_pos = strpos($content, '<!DOCTYPE html>');
            
            if ($doctype_pos !== false) {
                // Insert PHP code before DOCTYPE
                $php_code = "<?php\nsession_start();\nrequire_once 'includes/db.php';\n?>\n";
                $new_content = substr($content, 0, $doctype_pos) . $php_code . substr($content, $doctype_pos);
                
                // Write back to file
                if (file_put_contents($file, $new_content)) {
                    echo "<p style='color: green;'>✅ Fixed: {$file}</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to fix: {$file}</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No DOCTYPE found in: {$file}</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Already fixed: {$file}</p>";
        }
    } else {
        echo "<p style='color: gray;'>📁 File not found: {$file}</p>";
    }
}

echo "<h3>🧪 Test Results:</h3>";
echo "<p><a href='about.php'>Test about.php</a></p>";
echo "<p><a href='contact.php'>Test contact.php</a></p>";
echo "<p><a href='services.php'>Test services.php</a></p>";
echo "<p><a href='blog.php'>Test blog.php</a></p>";
echo "<p><a href='shop.php'>Test shop.php</a></p>";

?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style> 