<?php
// session_start();
// ini_set('display_errors', 1);
// require_once __DIR__ . '/ChatService.php';
// require_once __DIR__ . '/ProductCatalog.php';

// header('Content-Type: application/json; charset=utf-8');

// $data = json_decode(file_get_contents('php://input'), true);

// if (isset($data['get_history'])) {
//     $history = $_SESSION['chat_history'] ?? [];
//     echo json_encode(['history' => $history], JSON_UNESCAPED_UNICODE);
//     exit;
// }

// $msg = trim($data['message'] ?? '');

// if (!$msg) {
//     http_response_code(400);
//     echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
//     exit;
// }

// $chat = new ChatService();
// $intent_result = $chat->getIntent($msg);

// if (stripos($intent_result, 'RECOMMEND:') === 0) {
//     $name = trim(substr($intent_result, strlen('RECOMMEND:')));
//     $products = ProductCatalog::recommend($name);
//     $reply = $chat->ask($msg, $products);
// } elseif (stripos($intent_result, 'SEARCH:') === 0) {
//     $query = trim(substr($intent_result, strlen('SEARCH:')));
//     $products = ProductCatalog::search($query);
//     $reply = $chat->ask($msg, $products);
// } else {
//     $products = [];
//     $reply = "Я не понял ваш запрос, уточните пожалуйста.";
// }

// if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
// $_SESSION['chat_history'][] = [
//     'user' => $msg,
//     'bot' => $reply,
//     'products' => $products
// ];

// echo json_encode([
//     'reply' => $reply,
//     'products' => $products
// ], JSON_UNESCAPED_UNICODE);


ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/ChatService.php';
require_once __DIR__ . '/ProductCatalog.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (isset($data['get_history'])) {
    $history = $_SESSION['history'] ?? [];
    echo json_encode(['history' => $history], JSON_UNESCAPED_UNICODE);
    exit;
}

$msg = trim($data['message'] ?? '');

if (!$msg) {
    http_response_code(400);
    echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
    exit;
}

$chat = new ChatService();
$intent = $chat->getIntent($msg);

// Логирование для отладки
// file_put_contents('/tmp/intent.log', $intent."\n", FILE_APPEND);

if (stripos($intent, 'RECOMMEND:') === 0) {
    $query = trim(substr($intent, strlen('RECOMMEND:')));
    $products = ProductCatalog::recommend($query);
    $reply = $chat->ask($msg, $products);
} else { // SEARCH
    $query = trim(substr($intent, strlen('SEARCH:')));
    $products = ProductCatalog::search($query);
    $reply = $chat->ask($msg, $products);
}

// Сохраняем историю для user
$_SESSION['history'][] = [
    'user' => $msg,
    'bot' => $reply,
    'products' => $products
];

echo json_encode([
    'reply' => $reply,
    'products' => $products // <-- теперь всегда массив товаров, если они есть!
], JSON_UNESCAPED_UNICODE);