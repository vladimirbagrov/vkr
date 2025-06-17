<?php
// api/catalog_chat.php — backend для чата с GPT через ваш ChatService.php и поиск по таблице data

require_once __DIR__ . '/ChatService.php';

header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$user = 'cg38360_vkr';
$pass = '05122003BagVA';
$db = 'cg38360_vkr';
$table = 'data';
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);
$mysqli->set_charset($charset);

$data = json_decode(file_get_contents('php://input'), true);
$msg = trim($data['message'] ?? '');

if (!$msg) {
    echo json_encode(['reply'=>'Пустой запрос','products'=>[]]);
    exit;
}

// Получаем intent с помощью вашего ChatService (может быть SEARCH/RECOMMEND)
$chat = new ChatService();
$intent = $chat->getIntent($msg);

// Определяем тип поиска
$products = [];
if (preg_match('/^RECOMMEND:\s*(.+)$/iu', $intent, $m)) {
    $query = $mysqli->real_escape_string($m[1]);
    $sql = "SELECT id,name,link,ratings,actual_price,main_category_ru,sub_category_ru
            FROM `$table`
            WHERE name LIKE '%$query%' OR main_category_ru LIKE '%$query%' OR sub_category_ru LIKE '%$query%'
            LIMIT 12";
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()) $products[] = $row;
} elseif (preg_match('/^SEARCH:\s*(.+)$/iu', $intent, $m)) {
    $query = $mysqli->real_escape_string($m[1]);
    $sql = "SELECT id,name,link,ratings,actual_price,main_category_ru,sub_category_ru
            FROM `$table`
            WHERE name LIKE '%$query%' OR main_category_ru LIKE '%$query%' OR sub_category_ru LIKE '%$query%'
            LIMIT 12";
    $result = $mysqli->query($sql);
    while($row = $result->fetch_assoc()) $products[] = $row;
}

// Получаем финальный ответ GPT с учетом найденных товаров
$reply = $chat->ask($msg, $products);

// Логирование для отладки (можно закомментировать)
if (!file_exists(__DIR__.'/gpt_log.txt')) {
    file_put_contents(__DIR__.'/gpt_log.txt', '');
}
file_put_contents(__DIR__.'/gpt_log.txt', date('Y-m-d H:i:s')."\nMSG: $msg\nINTENT: $intent\nREPLY: $reply\n\n", FILE_APPEND);

echo json_encode([
    'reply' => $reply,
    'products' => $products
], JSON_UNESCAPED_UNICODE);