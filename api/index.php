<?php
// ile_put_contents('log.txt', "Запрос: " . $query . "\n", FILE_APPEND);
// file_put_contents('log.txt', "Ответ от Python: " . $response . "\n", FILE_APPEND);
// ini_set('display_errors', 1);
// require_once __DIR__ . '/ChatService.php';
// require_once __DIR__ . '/ProductCatalog.php';

// header('Content-Type: application/json; charset=utf-8');

// $data = json_decode(file_get_contents('php://input'), true);
// $msg = trim($data['message'] ?? '');

// if (!$msg) {
//     http_response_code(400);
//     echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
//     exit;
// }

// $products = ProductCatalog::search($msg);

// // Для отладки (после $products = ProductCatalog::search($msg);)
// error_log('Найдено товаров: ' . count($products));
// error_log(print_r($products, 1));

// // Используем ChatService для генерации ответа ассистента на основе сообщения пользователя и найденных товаров
// $chat = new ChatService();
// $reply = $chat->ask($msg, $products);

// echo json_encode([
//     'reply' => $reply,
//     'products' => $products
// ], JSON_UNESCAPED_UNICODE);

require_once __DIR__ . '/ChatService.php';
// require_once __DIR__ . '/ProductCatalog.php'; // не нужен, если всё ходит через Flask

header('Content-Type: application/json; charset=utf-8');

// Получаем message из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? trim($data['message']) : '';

if (!$message) {
    http_response_code(400);
    echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
    exit;
}

// 1. Получаем intent от GPT
$chat = new ChatService();
$intent = $chat->getIntent($message);

// 2. Разбираем, какой endpoint использовать
if (preg_match('/^RECOMMEND:\s*(.+)$/iu', $intent, $m)) {
    $endpoint = '/api/recommend';
    $query = trim($m[1]);
} elseif (preg_match('/^SEARCH:\s*(.+)$/iu', $intent, $m)) {
    $endpoint = '/api/search';
    $query = trim($m[1]);
} else {
    // fallback — обычный поиск
    $endpoint = '/api/search';
    $query = $message;
}

// 3. Отправляем запрос на нужный endpoint Flask
$ch = curl_init('http://127.0.0.1:5000' . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $query]));

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        'reply' => 'Ошибка соединения с backend',
        'products' => []
    ]);
    exit;
}

// Просто прокидываем ответ от Flask (он уже в нужном формате)
echo $response;
?>