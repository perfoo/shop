<?php
declare(strict_types=1);

// This endpoint is reserved for future asynchronous uploads.
http_response_code(405);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'error', 'message' => 'Ova ruta nije dostupna.']);
