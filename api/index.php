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


header('Content-Type: application/json; charset=utf-8');

// Получаем message из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? $data['message'] : '';

// Запрос к Flask API
$ch = curl_init('http://127.0.0.1:5000/api/search'); // URL подставь свой!
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        'reply' => 'Ошибка соединения с backend',
        'products' => []
    ]);
    exit;
}

// Просто прокидываем ответ от Flask
echo $response;
?>