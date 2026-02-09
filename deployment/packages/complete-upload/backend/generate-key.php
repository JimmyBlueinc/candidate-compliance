<?php
/**
 * Generate APP_KEY for Laravel
 * Access this file via browser after upload to generate your APP_KEY
 * Then copy the output and paste it into .env as APP_KEY=...
 */

header('Content-Type: text/plain');

$key = 'base64:' . base64_encode(random_bytes(32));

echo "========================================\n";
echo "Laravel APP_KEY Generator\n";
echo "========================================\n\n";
echo "Copy this key and add it to your .env file:\n\n";
echo "APP_KEY=" . $key . "\n\n";
echo "========================================\n";
echo "Instructions:\n";
echo "1. Copy the APP_KEY line above\n";
echo "2. Open backend/.env in your file manager\n";
echo "3. Find the line: APP_KEY=base64:CHANGE_THIS_KEY_AFTER_UPLOAD\n";
echo "4. Replace it with the copied line\n";
echo "5. Save the file\n";
echo "========================================\n";




