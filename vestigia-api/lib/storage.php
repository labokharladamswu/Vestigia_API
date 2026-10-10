<?php
// ============================================================
// storage.php - image uploads to Supabase Storage (replaces local /uploads)
// Vercel's filesystem is read-only/ephemeral, so files must live elsewhere.
// ============================================================
require_once __DIR__ . '/env.php';

const ALLOWED_IMAGE_TYPES = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
];

function storageBucket(): string {
    return env('SUPABASE_BUCKET', 'vestigia-uploads');
}

// Turns a stored object key into a public URL the Android app can load.
// Also handles legacy values like "uploads/abc.jpg" from the old local-disk setup.
function storagePublicUrl(?string $path): ?string {
    if (!$path) {
        return null;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $key  = basename($path);
    $base = rtrim((string) env('SUPABASE_URL', ''), '/');
    return $base . '/storage/v1/object/public/' . rawurlencode(storageBucket()) . '/' . rawurlencode($key);
}

function storageUpload(string $tmpFile, string $key, string $contentType): bool {
    $base = rtrim((string) env('SUPABASE_URL', ''), '/');
    $svc  = env('SUPABASE_SERVICE_KEY');
    if (!$base || !$svc) {
        error_log('Storage not configured: SUPABASE_URL / SUPABASE_SERVICE_KEY missing');
        return false;
    }

    $url = $base . '/storage/v1/object/' . rawurlencode(storageBucket()) . '/' . rawurlencode($key);
    $headers = [
        'apikey: ' . $svc,
        'Content-Type: ' . $contentType,
        'x-upsert: false',
    ];
    // Legacy service_role keys are JWTs (eyJ...); new sb_secret_ keys are sent via apikey only.
    if (strpos($svc, 'eyJ') === 0) {
        $headers[] = 'Authorization: Bearer ' . $svc;
    }
    $body = file_get_contents($tmpFile);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'header' => implode("\r\n", $headers),
            'content' => $body, 'timeout' => 20, 'ignore_errors' => true,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        $code = 0;
        if (!empty($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
            $code = (int) $m[1];
        }
    }

    if ($code < 200 || $code >= 300) {
        error_log("Supabase Storage upload failed (HTTP $code): " . substr((string) $resp, 0, 300));
        return false;
    }
    return true;
}

function uploadError(int $status, string $message): void {
    http_response_code($status);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

// Handles one optional image field from $_FILES.
// Returns the stored object key, or null when no file was sent.
// $silent = true keeps the old behaviour of ignoring bad files instead of returning an error.
function handleImageUpload(string $field, string $prefix, bool $silent = false): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($silent) return null;
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            uploadError(413, "Image is too large (max 4 MB).");
        }
        uploadError(400, "Image upload failed.");
    }

    $maxBytes = (int) env('IMAGE_MAX_BYTES', 4000000);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!isset(ALLOWED_IMAGE_TYPES[$ext]) || ($file['size'] ?? 0) > $maxBytes
        || (function_exists('getimagesize') && @getimagesize($file['tmp_name']) === false)) {
        if ($silent) return null;
        uploadError(400, "Only JPG, PNG, or WEBP images up to 4 MB are allowed.");
    }

    $key = $prefix . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!storageUpload($file['tmp_name'], $key, ALLOWED_IMAGE_TYPES[$ext])) {
        if ($silent) return null;
        uploadError(500, "Failed to save uploaded image.");
    }
    return $key;
}
