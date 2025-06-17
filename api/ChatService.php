<?php
class ChatService
{
    private $apiUrl;
    private $apiKey;
    private $model;

    public function __construct()
    {
        $this->apiUrl = 'https://api.vsegpt.ru/v1/chat/completions';
        $this->apiKey = 'sk-or-vv-9379e591855474852558a3034c049c6583b084035038731096805b0c655a81cf';
        $this->model = 'gpt-3.5-turbo';
    }

    public function getIntent($userText)
    {
        $prompt = <<<PROMPT
Ты — ассистент по подбору товаров.
Если пользователь просит подобрать аналоги, альтернативы, похожие товары — верни: RECOMMEND: <название товара или категория или ключевые слова>
Если ищет товары в категории/по названию — верни: SEARCH: <название, категория или ключевые слова>
В ответе только команду, без пояснений.
PROMPT;

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => $userText]
            ],
            'max_tokens' => 30,
            'temperature' => 0.0
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

        // Логируем RAW ответ для отладки
        file_put_contents(__DIR__ . '/gpt_raw_response.txt', "getIntent:\n" . $result . "\n", FILE_APPEND);

        if ($result === false) {
            return 'SEARCH: ' . $userText;
        }
        $data = json_decode($result, true);
        $intent = $data['choices'][0]['message']['content'] ?? $data['choices'][0]['text'] ?? null;
        if (!$intent) {
            return 'SEARCH: ' . $userText;
        }
        return trim($intent);
    }

    public function ask($userText, $products = [])
    {
        $sysPrompt = "Ты консультант современного онлайн-магазина. Пользуйся стилем Apple: лаконично, дружелюбно, профессионально и современно. Не используй лишних слов.
Если пользователь спрашивает о товаре и по запросу найдены подходящие товары, напиши короткий дружелюбный ответ, что такие товары есть.
Не перечисляй товары, не повторяй их названия и характеристики в ответе — просто подтверди, что они есть.
Если по запросу не найдено ни одного товара, напиши лаконично и дружелюбно, что ничего не найдено, и предложи уточнить запрос.
Не придумывай товары, не фантазируй, используй только факты из списка, если он есть.";

        if ($products && count($products)) {
            $sysPrompt .= "\n\nСписок найденных товаров:\n";
            foreach ($products as $p) {
                $item = ($p['name'] ?? $p['Product Name'] ?? '') . " — " . ($p['actual_price'] ?? $p['Selling Price'] ?? '') . "₽";
                if (!empty($p['ratings'])) $item .= " (рейтинг " . $p['ratings'] . ")";
                if (!empty($p['main_category_ru']) || !empty($p['Category']))
                    $item .= ", категория: " . ($p['main_category_ru'] ?? $p['Category']);
                if (!empty($p['sub_category_ru']))
                    $item .= " / " . $p['sub_category_ru'];
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

        // Логируем RAW ответ для отладки
        file_put_contents(__DIR__ . '/gpt_raw_response.txt', "ask:\n" . $result . "\n", FILE_APPEND);

        if ($result === false) {
            return "Извините, сервис временно недоступен.";
        }
        $data = json_decode($result, true);

        $answer = $data['choices'][0]['message']['content'] ?? 
                  $data['choices'][0]['text'] ?? 
                  null;

        if (!$answer) {
            return "Ошибка GPT: " . print_r($data, 1);
        }
        return trim($answer);
    }
}