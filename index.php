<?php
session_start();
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/gpt_integration.php';
require_once __DIR__.'/product_search.php';

// --- Блок для обработки "Похожие товары" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['similar_to'])) {
    $baseProduct = trim($_POST['similar_to']);
    $products = ProductSearch::getSimilarProducts($baseProduct);
    $response = GPT::getResponse("Покажи похожие на $baseProduct", $products, false);

    // Сохраняем в сессии
    $_SESSION['chat'][] = [
        'query' => "Похожие на: $baseProduct",
        'response' => $response,
        'products' => $products,
        'timestamp' => time()
    ];

    echo json_encode([
        'success' => true,
        'response' => $response,
        'products' => $products
    ]);
    exit;
}

// --- Обычный запрос пользователя ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json');

    $userMessage = trim($_POST['message']);

    if (!empty($userMessage)) {
        $products = ProductSearch::findProducts($userMessage);
        $found = !empty($products);

        // Если ничего не найдено — получить рекомендации
        if (!$found) {
            $products = ProductSearch::getRandomProducts(); // использовать рекомендуемые
        }

        $response = GPT::getResponse($userMessage, $products, $found); // передаём флаг $found

        // Сохраняем в сессию
        $_SESSION['chat'][] = [
            'query' => $userMessage,
            'response' => $response,
            'products' => $products,
            'timestamp' => time()
        ];

        echo json_encode([
            'success' => true,
            'response' => $response,
            'products' => $products
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Пустое сообщение']);
    }

    exit;
}

// Получаем историю чата
$chatHistory = $_SESSION['chat'] ?? [];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автоконсультант - помощь в выборе автозапчастей</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--secondary-color);
        }
        
        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 180px);
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 20px;
        }
        
        .chat-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-header i {
            font-size: 20px;
        }
        
        #chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 12px 15px;
            border-radius: 18px;
            line-height: 1.4;
            position: relative;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-message {
            background-color: var(--secondary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .bot-message {
            background-color: white;
            border: 1px solid #ddd;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .product-card {
            background-color: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .product-price {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 8px;
        }
        
        .product-article {
            color: #7f8c8d;
            font-size: 0.8rem;
            margin: 5px 0;
        }
        
        .input-area {
            display: flex;
            padding: 15px;
            background-color: white;
            border-top: 1px solid #eee;
        }
        
        #user-message {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        #user-message:focus {
            border-color: var(--secondary-color);
        }
        
        #send-btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin-left: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        #send-btn:hover {
            background-color: #2980b9;
        }
        
        #send-btn i {
            font-size: 18px;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px 15px;
            background-color: white;
            border-radius: 18px;
            margin-bottom: 15px;
            width: fit-content;
            border: 1px solid #eee;
        }
        
        .typing-dots {
            display: flex;
            gap: 5px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: #bbb;
            border-radius: 50%;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: 0s; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        .suggestion-btn {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .suggestion-btn:hover {
            background-color: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        
        .error-message {
            color: var(--accent-color);
            background-color: #fdecea;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 3px solid var(--accent-color);
        }
        
        .timestamp {
            font-size: 0.7rem;
            color: #95a5a6;
            margin-top: 5px;
            text-align: right;
        }
        
        .bot-avatar {
            width: 30px;
            height: 30px;
            background-color: var(--secondary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .message-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .message-content {
            max-width: calc(100% - 40px);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .logo h1 {
                font-size: 1.2rem;
            }
            
            .chat-container {
                height: calc(100vh - 150px);
            }
            
            .message {
                max-width: 90%;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <i class="fas fa-car"></i>
                <h1>Автоконсультант</h1>
            </div>
            <div class="header-info">
                <span>Помощь в выборе автозапчастей</span>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <i class="fas fa-robot"></i>
                <h2>Чат-помощник</h2>
            </div>
            
            <div id="chat-box">
                <!-- Приветственное сообщение -->
                <div class="message-container">
                    <div class="bot-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="bot-message message">
                            Добрый день! Я ваш помощник в выборе автозапчастей. Чем могу помочь?
                            <div class="timestamp"><?= date('H:i') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- История чата -->
                <?php foreach ($chatHistory as $chatItem): ?>
                    <!-- Сообщение пользователя -->
                    <div class="message-container" style="justify-content: flex-end;">
                        <div class="message-content">
                            <div class="user-message message">
                                <?= htmlspecialchars($chatItem['query']) ?>
                                <div class="timestamp"><?= date('H:i', $chatItem['timestamp']) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ответ бота -->
                    <div class="message-container">
                        <div class="bot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            <div class="bot-message message">
                                <?= $chatItem['response'] ?>
                                <div class="timestamp"><?= date('H:i', $chatItem['timestamp']) ?></div>
                            </div>
                            
                            <!-- Товары -->
                            <?php if (!empty($chatItem['products'])): ?>
                                <div class="products-grid">
                                    <?php foreach ($chatItem['products'] as $product): ?>
                                        <div class="product-card">
                                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                                            <?php if (!empty($product['article'])): ?>
                                                <div class="product-article">Артикул: <?= htmlspecialchars($product['article']) ?></div>
                                            <?php endif; ?>
                                            <div class="product-price"><?= number_format($product['price'], 0, '', ' ') ?> руб.</div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Быстрые подсказки -->
                <div class="suggestions">
                    <div class="suggestion-btn">Щетки стеклоочистителя</div>
                    <div class="suggestion-btn">Автомобильные лампы</div>
                    <div class="suggestion-btn">Моторные масла</div>
                    <div class="suggestion-btn">Аксессуары</div>
                </div>
            </div>
            
            <!-- Индикатор набора сообщения -->
            <div class="typing-indicator">
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
            
            <!-- Поле ввода -->
            <div class="input-area">
                <input type="text" id="user-message" placeholder="Например: зимние щетки для Kia Rio..." autocomplete="off">
                <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const chatBox = $('#chat-box');
        chatBox.scrollTop(chatBox[0].scrollHeight);

        $('#send-btn').click(sendMessage);
        $('#user-message').keypress(function(e) {
            if (e.which == 13) sendMessage();
        });

        // Быстрые подсказки и "Похожие товары"
        $(document).on('click', '.suggestion-btn', function() {
            const text = $(this).text();
            if (text === "Похожие товары" && window.lastProductName) {
                sendSimilar(window.lastProductName);
            } else {
                $('#user-message').val(text);
                sendMessage();
            }
        });

        function sendMessage() {
            const message = $('#user-message').val().trim();
            if (!message) return;

            const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            chatBox.append(`
                <div class="message-container" style="justify-content: flex-end;">
                    <div class="message-content">
                        <div class="user-message message">
                            ${escapeHtml(message)}
                            <div class="timestamp">${timestamp}</div>
                        </div>
                    </div>
                </div>
            `);

            $('#user-message').val('');
            $('.typing-indicator').show();
            chatBox.scrollTop(chatBox[0].scrollHeight);
            $('.suggestions').hide();

            $.post('', {message: message}, function(data) {
                $('.typing-indicator').hide();

                if (data.success) {
                    const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    chatBox.append(`
                        <div class="message-container">
                            <div class="bot-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <div class="bot-message message">
                                    ${data.response || 'Вот что я нашел:'}
                                    <div class="timestamp">${timestamp}</div>
                                </div>
                            </div>
                        </div>
                    `);

                    // Карточки товаров
                    if (data.products && data.products.length > 0) {
                        let productsHtml = '<div class="products-grid">';
                        data.products.forEach(product => {
                            productsHtml += `
                                <div class="product-card">
                                    <div class="product-title">${escapeHtml(product.name)}</div>
                                    ${product.article ? `<div class="product-article">Артикул: ${escapeHtml(product.article)}</div>` : ''}
                                    <div class="product-price">${product.price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")} руб.</div>
                                </div>
                            `;
                        });
                        productsHtml += '</div>';

                        chatBox.append(`
                            <div class="message-container">
                                <div class="bot-avatar"></div>
                                <div class="message-content">
                                    ${productsHtml}
                                </div>
                            </div>
                        `);

                        // Сохраняем последнее название товара для подсказки "Похожие товары"
                        window.lastProductName = data.products[0].name;
                    }

                    // Подсказки
                    $('.suggestions').html(''
                        + '<div class="suggestion-btn">Похожие товары</div>'
                        + '<div class="suggestion-btn">Аксессуары</div>'
                        + '<div class="suggestion-btn">Сопутствующие товары</div>'
                    ).show();

                } else {
                    chatBox.append(`
                        <div class="message-container">
                            <div class="bot-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <div class="error-message">
                                    Ошибка: ${data.error || 'Неизвестная ошибка'}
                                </div>
                            </div>
                        </div>
                    `);
                }

                chatBox.scrollTop(chatBox[0].scrollHeight);
            }, 'json').fail(function() {
                $('.typing-indicator').hide();
                chatBox.append(`
                    <div class="message-container">
                        <div class="bot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="message-content">
                            <div class="error-message">
                                Ошибка соединения с сервером
                            </div>
                        </div>
                    </div>
                `);
                chatBox.scrollTop(chatBox[0].scrollHeight);
            });
        }

        function sendSimilar(productName) {
            $('.typing-indicator').show();
            chatBox.scrollTop(chatBox[0].scrollHeight);
            $('.suggestions').hide();

            $.post('', {similar_to: productName}, function(data) {
                $('.typing-indicator').hide();
                const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                if (data.success) {
                    chatBox.append(`
                        <div class="message-container">
                            <div class="bot-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <div class="bot-message message">
                                    ${data.response || 'Вот похожие товары:'}
                                    <div class="timestamp">${timestamp}</div>
                                </div>
                            </div>
                        </div>
                    `);

                    if (data.products && data.products.length > 0) {
                        let productsHtml = '<div class="products-grid">';
                        data.products.forEach(product => {
                            productsHtml += `
                                <div class="product-card">
                                    <div class="product-title">${escapeHtml(product.name)}</div>
                                    ${product.article ? `<div class="product-article">Артикул: ${escapeHtml(product.article)}</div>` : ''}
                                    <div class="product-price">${product.price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")} руб.</div>
                                </div>
                            `;
                        });
                        productsHtml += '</div>';
                        chatBox.append(`
                            <div class="message-container">
                                <div class="bot-avatar"></div>
                                <div class="message-content">
                                    ${productsHtml}
                                </div>
                            </div>
                        `);
                        window.lastProductName = data.products[0].name;
                    }
                } else {
                    chatBox.append(`
                        <div class="message-container">
                            <div class="bot-avatar">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="message-content">
                                <div class="error-message">
                                    Ошибка: ${data.error || 'Неизвестная ошибка'}
                                </div>
                            </div>
                        </div>
                    `);
                }
                $('.suggestions').html(''
                    + '<div class="suggestion-btn">Похожие товары</div>'
                    + '<div class="suggestion-btn">Аксессуары</div>'
                    + '<div class="suggestion-btn">Сопутствующие товары</div>'
                ).show();
                chatBox.scrollTop(chatBox[0].scrollHeight);
            });
        }

        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    });
    </script>
</body>
</html>