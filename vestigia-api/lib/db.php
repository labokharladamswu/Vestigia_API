<?php
// ============================================================
// db.php - Supabase (PostgreSQL) connection via PDO + common headers
// All settings come from environment variables (see .env.example)
// ============================================================
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/storage.php';

date_default_timezone_set('UTC');

// Common headers for every endpoint
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: " . env('CORS_ALLOW_ORIGIN', '*'));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function getDbConnection() {
    $host = env('DB_HOST');
    $port = env('DB_PORT', '6543');
    $name = env('DB_NAME', 'postgres');
    $user = env('DB_USER');
    $pass = env('DB_PASS');
    $ssl  = env('DB_SSLMODE', 'require');

    if (!$host || !$user || $pass === null) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Server is not configured (missing DB_* environment variables)."]);
        exit;
    }

    try {
        $dsn = "pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Required for Supabase's transaction pooler (PgBouncer), which
            // does not support server-side prepared statements.
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("DB connection failed: " . $e->getMessage());
        http_response_code(500);
        $msg = "Database connection failed.";
        if (env('APP_DEBUG') === 'true') {
            $msg .= " " . $e->getMessage();
        }
        echo json_encode(["success" => false, "message" => $msg]);
        exit;
    }
}
