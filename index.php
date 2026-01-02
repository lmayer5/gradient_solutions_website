<?php
// Fallback loader if Apache doesn't pick up index.html automatically
if (file_exists('index.html')) {
    readfile('index.html');
} else {
    echo "Error: index.html not found.";
}
?>
