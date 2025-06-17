<?php
// require_once __DIR__ . '/ChatService.php';

// header('Content-Type: application/json; charset=utf-8');

// $data = json_decode(file_get_contents('php://input'), true);
// $message = isset($data['message']) ? trim($data['message']) : '';

// if (!$message) {
//     http_response_code(400);
//     echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
//     exit;
// }

// $chat = new ChatService();
// $intent = $chat->getIntent($message);

// // Можешь упростить — если эта копия только для catalog.php, просто всегда отправляй на cosine_search.
// if (preg_match('/^RECOMMEND:\s*(.+)$/iu', $intent, $m)) {
//     $endpoint = '/api/recommend';
//     $query = trim($m[1]);
// } elseif (preg_match('/^SEARCH:\s*(.+)$/iu', $intent, $m)) {
//     $endpoint = '/api/search';
//     $query = trim($m[1]);
// } elseif (preg_match('/^CATALOG:\s*(.+)$/iu', $intent, $m)) {
//     $endpoint = '/api/cosine_search';
//     $query = trim($m[1]);
// } else {
//     // fallback — обычный поиск
//     $endpoint = '/api/search';
//     $query = $message;
// }

// $postField = ['message' => $query];

// file_put_contents(__DIR__ . "/debug.log", date('c') . " | endpoint: $endpoint | post: " . json_encode($postField) . "\n", FILE_APPEND);

// $ch = curl_init('http://127.0.0.1:5000' . $endpoint);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postField));

// $response = curl_exec($ch);

// if ($response === false) {
//     echo json_encode([
//         'reply' => 'Ошибка соединения с backend',
//         'products' => []
//     ]);
//     exit;
// }

// echo $response;
require_once __DIR__ . '/ChatService.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$message = isset($data['message']) ? trim($data['message']) : '';
$intent = isset($data['intent']) ? strtoupper(trim($data['intent'])) : '';

// Если intent не пришёл с фронта — определяем через GPT (на всякий случай)
if (!$intent) {
    $chat = new ChatService();
    $intent = strtoupper(trim($chat->getIntent($message)));
}

if (!$message) {
    http_response_code(400);
    echo json_encode(['reply' => 'Пустой запрос', 'products' => []]);
    exit;
}

if ($intent === 'CATALOG') {
    $endpoint = '/api/cosine_search';
    $postField = ['message' => $message];
} elseif ($intent === 'RECOMMEND') {
    $endpoint = '/api/recommend';
    $postField = ['message' => $message];
} else {
    $endpoint = '/api/search';
    $postField = ['message' => $message];
}

$ch = curl_init('http://127.0.0.1:5000' . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postField));
$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        'reply' => 'Ошибка соединения с backend',
        'products' => []
    ]);
    exit;
}

echo $response;
?>