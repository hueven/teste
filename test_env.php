<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function sanitize($name) {
    if (!$name) return 'default';
    $sanitized = mb_strtolower($name, 'UTF-8');
    echo "1. mb_strtolower: $sanitized\n";
    $sanitized = preg_replace('/[áàãâä]/u', 'a', $sanitized);
    echo "2. á -> a: $sanitized\n";
    $sanitized = preg_replace('/[éèêë]/u', 'e', $sanitized);
    $sanitized = preg_replace('/[íìîï]/u', 'i', $sanitized);
    $sanitized = preg_replace('/[óòõôö]/u', 'o', $sanitized);
    $sanitized = preg_replace('/[úùûü]/u', 'u', $sanitized);
    $sanitized = preg_replace('/[ç]/u', 'c', $sanitized);
    $sanitized = preg_replace('/[^a-z0-9]/', '-', $sanitized);
    echo "3. strip special: $sanitized\n";
    $sanitized = preg_replace('/-+/', '-', $sanitized);
    return trim($sanitized, '-');
}

$test = "Preá";
echo "Testing with: $test\n";
$res = sanitize($test);
echo "Final Result: $res\n";

echo "\nPHP Version: " . phpversion() . "\n";
echo "MB extension: " . (extension_loaded('mbstring') ? 'YES' : 'NO') . "\n";
?>
