<?php
class ChatService
{
    private $apiUrl;
    private $apiKey;
    private $model;

    public function __construct()
    {
        // Пропиши свои значения прямо здесь:
        $this->apiUrl = 'https://api.vsegpt.ru/v1/chat/completions';
        $this->apiKey = 'sk-or-vv-9379e591855474852558a3034c049c6583b084035038731096805b0c655a81cf';
        $this->model = 'gpt-3.5-turbo'; // или другую модель, если нужно
    }

    public function ask($userText, $products = [])
    {
        $sysPrompt = "Ты консультант современного онлайн-магазина. Пользуйся стилем Apple: лаконично, дружелюбно, профессионально. 
Если пользователь спрашивает о товаре и по запросу найдены подходящие товары, напиши короткий дружелюбный ответ, что товары найдены и предложи посмотреть карточки ниже. 
Не перечисляй товары, не повторяй их названия и характеристики в ответе — просто подтверди, что они есть.
Если по запросу не найдено ни одного товара, напиши лаконично и дружелюбно, что ничего не найдено, и предложи уточнить или изменить запрос.
Не придумывай товары, не фантазируй, используй только факты из списка, если он есть.";

        // Передаём список товаров для контекста, но не просим перечислять их в ответе.
        if ($products && count($products)) {
            $sysPrompt .= "\n\nСписок найденных товаров:\n";
            foreach ($products as $p) {
                $item = $p['name'] . " — " . $p['actual_price'] . "₽";
                if (!empty($p['ratings'])) $item .= " (рейтинг " . $p['ratings'] . ")";
                if (!empty($p['main_category_ru'])) $item .= ", категория: " . $p['main_category_ru'];
                if (!empty($p['sub_category_ru'])) $item .= " / " . $p['sub_category_ru'];
                $sysPrompt .= $item . "\n";
            }
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $sysPrompt],
                ['role' => 'user', 'content' => $userText]
            ],
            'max_tokens' => 160,
            'temperature' => 0.7
        ];
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->apiKey}"
            ]
        ]);
        $result = curl_exec($ch);

        if ($result === false) {
            return "Извините, сервис временно недоступен.";
        }
        $data = json_decode($result, true);
        return trim($data['choices'][0]['message']['content'] ?? "Ответ от ассистента не получен.");
    }
}