<?php
// GET /api/health.php  -> quick sanity check after deploy (no secrets exposed)
require_once __DIR__ . '/../lib/db.php';

$result = [
    "success"    => true,
    "php"        => PHP_VERSION,
    "pdo_pgsql"  => extension_loaded('pdo_pgsql'),
    "curl"       => function_exists('curl_init'),
    "env_db"     => (bool) (env('DB_HOST') && env('DB_USER') && env('DB_PASS') !== null),
    "env_storage"=> (bool) (env('SUPABASE_URL') && env('SUPABASE_SERVICE_KEY')),
    "database"   => false,
];

if ($result["pdo_pgsql"] && $result["env_db"]) {
    try {
        getDbConnection()->query("SELECT 1");
        $result["database"] = true;
    } catch (Throwable $e) {
        $result["success"] = false;
    }
} else {
    $result["success"] = false;
}

http_response_code($result["success"] ? 200 : 503);
echo json_encode($result);
