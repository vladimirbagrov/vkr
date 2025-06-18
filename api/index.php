<?php
// require_once __DIR__ . '/ChatService.php';

// header('Content-Type: application/json; charset=utf-8');

// // Получаем message из POST-запроса
// $data = json_decode(file_get_contents('php://input'), true);
// $message = isset($data['message']) ? trim($data['message']) : '';

// if (!$message) {
//     http_response_code(400);
//     echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
//     exit;
// }

// // Используем ChatService для определения intent (RECOMMEND/SEARCH)
// $chat = new ChatService();
// $intent = $chat->getIntent($message);

// // Определяем, какой endpoint Flask использовать
// if (preg_match('/^RECOMMEND:\s*(.+)$/iu', $intent, $m)) {
//     $endpoint = '/api/recommend';
//     $query = trim($m[1]);
// } elseif (preg_match('/^SEARCH:\s*(.+)$/iu', $intent, $m)) {
//     $endpoint = '/api/search';
//     $query = trim($m[1]);
// } else {
//     $endpoint = '/api/search';
//     $query = $message;
// }

// // Отправляем запрос на нужный endpoint Flask (на 127.0.0.1:5000)
// $ch = curl_init('http://127.0.0.1:5000' . $endpoint);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $query]));

// $response = curl_exec($ch);

// if ($response === false) {
//     echo json_encode([
//         'reply' => 'Ошибка соединения с backend',
//         'products' => []
//     ]);
//     exit;
// }

// // Просто прокидываем ответ от Flask (он уже в нужном формате)
// echo $response;
require_once __DIR__ . '/ChatService.php';

header('Content-Type: application/json; charset=utf-8');

// Получаем параметры из POST-запроса
$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? trim($data['message']) : '';
$query   = isset($data['query']) ? trim($data['query']) : '';

// --- 1. Прямая прокси для /api/cosine_search ---
if (strpos($_SERVER['REQUEST_URI'], '/api/cosine_search') !== false) {
    // Если пришёл запрос именно на /api/cosine_search — прокидываем его на Flask
    $ch = curl_init('http://127.0.0.1:5000/api/cosine_search');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
    // В cosine_search Flask ждёт {"query": "..."}
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query ?: $message]));
    $response = curl_exec($ch);
    if ($response === false) {
        echo json_encode([
            'reply' => 'Ошибка соединения с backend',
            'products' => []
        ]);
        exit;
    }
    echo $response;
    exit;
}

// --- 2. Обычный (чат/главная страница) режим ---
if (!$message) {
    http_response_code(400);
    echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
    exit;
}

// Используем ChatService для определения intent (RECOMMEND/SEARCH)
$chat = new ChatService();
$intent = $chat->getIntent($message);

// Определяем, какой endpoint Flask использовать
if (preg_match('/^RECOMMEND:\s*(.+)$/iu', $intent, $m)) {
    $endpoint = '/api/recommend';
    $query = trim($m[1]);
} elseif (preg_match('/^SEARCH:\s*(.+)$/iu', $intent, $m)) {
    $endpoint = '/api/search';
    $query = trim($m[1]);
} else {
    $endpoint = '/api/search';
    $query = $message;
}

// Отправляем запрос на нужный endpoint Flask (на 127.0.0.1:5000)
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