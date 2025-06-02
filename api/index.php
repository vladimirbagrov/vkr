<?php
ini_set('display_errors', 1);
require_once __DIR__ . '/ChatService.php';
require_once __DIR__ . '/ProductCatalog.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$msg = trim($data['message'] ?? '');

if (!$msg) {
    http_response_code(400);
    echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
    exit;
}

$products = ProductCatalog::search($msg);

if (!$products) {
    $reply = "К сожалению, ничего не найдено.";
} else {
    $reply = "Нашёл товары по вашему запросу.";
}

echo json_encode([
    'reply' => $reply,
    'products' => $products
], JSON_UNESCAPED_UNICODE);