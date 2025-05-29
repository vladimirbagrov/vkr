<?php
require_once 'db.php';
require_once 'product_search.php';
require_once 'config.php';

class GPT {
    public static function getResponse($message, $products, $found = true) {
    $productText = empty($products)
        ? "Товары по запросу \"$message\" не найдены."
        : "Вот товары " . ($found ? "по вашему запросу" : "которые мы можем порекомендовать") . ":\n\n" .
            implode("\n", array_map(function($p) {
                return "✅ {$p['name']} — {$p['price']} руб.";
            }, $products));

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "Ты продавец-консультант автотоваров. Отвечай строго по теме автотоваров и запчастей, показывай товары только из базы."],
            ["role" => "user", "content" => "Запрос: \"$message\"\n\n$productText"]
        ],
        "temperature" => 0.7,
        "max_tokens" => 500
    ];

    $response = self::send($data);
    return $response ?: "Ошибка в ответе ChatGPT.";
}

    private static function send($data) {
        $headers = [
            "Authorization: Bearer " . GPT_API_KEY,
            "Content-Type: application/json"
        ];

        $ch = curl_init(GPT_API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            return "Ошибка соединения с GPT: " . curl_error($ch);
        }

        curl_close($ch);
        $decoded = json_decode($response, true);

        return $decoded['choices'][0]['message']['content'] ?? "Ошибка в ответе ChatGPT.";
    }
}

// 🔹 Главная функция обработки запроса пользователя
function handleUserMessage($message) {
    $products = ProductSearch::findProducts($message);

    // Если ничего не найдено — получаем случайные рекомендации
    if (empty($products)) {
        $products = ProductSearch::getRandomProducts();
    }

    $gptReply = GPT::getResponse($message, $products);

    // Возвращаем структуру для AJAX
    return [
        'success' => true,
        'response' => $gptReply,
        'products' => $products
    ];
}
