<?php
$envPath = __DIR__ . '/../.env';
if (is_file($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        list($name, $value) = explode('=', $trimmed, 2);
        $name = trim($name);
        $value = trim($value);

        if ($value !== '' && ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || (substr($value, 0, 1) === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'db_surat_akreditasi';
$timezone = getenv('APP_TIMEZONE') ?: 'Asia/Makassar';

$config = new mysqli($host, $username, $password, $database);

if ($config->connect_error) {
    die('Koneksi gagal: ' . $config->connect_error);
}

date_default_timezone_set($timezone);
?>
