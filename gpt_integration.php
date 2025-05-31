<?php
require_once 'db.php';
require_once 'product_search.php';
require_once 'config.php';

class GPT {
    public static function extractFilters($userMessage) {
        $systemPrompt = "Ты помощник в любом онлайн-магазине. По запросу пользователя выдели ключевые слова или категории для поиска товара (например: 'лампа', 'чайник', 'рюкзак', 'Nike', 'до 1000 рублей' и т.д.). Верни только JSON, например: {\"keywords\":[\"лампа\"]}. Если есть ограничение по цене — добавь ключ max_price. Не добавляй в ответ ничего, кроме JSON!";
        $data = [
            "model" => GPT_MODEL,
            "messages" => [
                ["role" => "system", "content" => $systemPrompt],
                ["role" => "user", "content" => $userMessage]
            ],
            "temperature" => 0,
            "max_tokens" => 150
        ];
        $response = self::send($data);
        $json = trim($response);
        $json = preg_replace('/^[^{]*({.*?})[^}]*$/s', '$1', $json); // Оставить только JSON
        $params = json_decode($json, true);
        return is_array($params) ? $params : [];
    }

    public static function searchAndReply($userMessage, $products, $recommendations = []) {
        if (!empty($products)) {
            $reply = "Вот товары, которые у нас есть:";
        } elseif (!empty($recommendations)) {
            $reply = "По вашему запросу ничего не найдено, но вот что я могу порекомендовать:";
        } else {
            $reply = "Извините, по вашему запросу ничего не найдено. Пожалуйста, уточните запрос или попробуйте другой товар.";
        }
        return $reply;
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