<?php
function esc($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Каталог товаров (интеллектуальный поиск)</title>
    <meta name="viewport" content="width=1100,initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=SF+Pro+Display:400,600&display=swap" rel="stylesheet">
    <style>
        /* --- ваш CSS без изменений --- */
        :root {
            --apple-gray: #f5f5f7;
            --apple-white: #fff;
            --apple-black: #1d1d1f;
            --apple-blue: #0071e3;
            --apple-card: #f9f9fb;
            --apple-shadow: 0 8px 32px 0 rgba(60,60,60,0.08);
            --radius: 18px;
        }
        body {
            font-family: 'SF Pro Display', 'Segoe UI', Arial, sans-serif;
            background: var(--apple-gray);
            color: var(--apple-black);
            margin: 0; padding: 0;
        }
        #chat-container {
            max-width: 1100px;
            margin: 40px auto 0 auto;
            background: var(--apple-white);
            border-radius: var(--radius);
            box-shadow: var(--apple-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 80vh;
            position: relative;
        }
        #chat-header {
            background: var(--apple-white);
            padding: 32px;
            text-align: center;
            border-bottom: 1px solid #ececec;
        }
        #chat-header span {
            font-size: 1.6rem;
            font-weight: 600;
            margin-left: 10px;
            letter-spacing: 0.03em;
        }
        #chat {
            flex: 1;
            padding: 36px;
            overflow-y: auto;
            background: var(--apple-card);
            min-height: 300px;
        }
        .msg {
            margin-bottom: 24px;
            max-width: 65%;
            border-radius: var(--radius);
            padding: 18px 26px;
            font-size: 1.17rem;
            line-height: 1.5;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.07);
            word-break: break-word;
        }
        .bot {
            background: var(--apple-white);
            color: var(--apple-black);
            border-bottom-left-radius: 5px;
            border-top-left-radius: 5px;
        }
        .user {
            background: var(--apple-blue);
            color: #fff;
            margin-left: auto;
            border-bottom-right-radius: 5px;
            border-top-right-radius: 5px;
        }
        .products {
            margin: 12px 0 0 0;
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
        }
        .prod {
            background: var(--apple-white);
            border-radius: 14px;
            box-shadow: 0 4px 16px 0 rgba(100,100,100,0.08);
            padding: 18px 22px;
            min-width: 260px;
            max-width: 310px;
            margin-bottom: 16px;
            font-size: 1.01rem;
            color: var(--apple-black);
            transition: box-shadow .2s;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }
        .prod-category {
            color: #888;
            font-size: 15px;
        }
        .prod-name {
            font-weight: bold;
            font-size: 18px;
            margin-top: 2px;
        }
        .prod-price {
            font-size: 16px;
            color: #0071e3;
            font-weight: 600;
        }
        .prod-desc {
            color: #555;
            font-size: 15px;
            max-height: 65px;
            overflow-y: auto;
            margin-bottom: 8px;
        }
        .prod-more {
            width: 100%;
            margin-top: 4px;
            display: flex;
        }
        .prod-more-btn {
            flex: 1;
            width: 100%;
            padding: 10px 0;
            font-size: 16px;
            border-radius: 6px;
            border: none;
            background: #0071e3;
            color: #fff;
            cursor: pointer;
            transition: background .2s;
            font-weight: 500;
            text-align: center;
        }
        .prod-more-btn:hover {
            background: #005bb5;
        }
        #input-area {
            background: var(--apple-gray);
            padding: 22px 30px;
            display: flex;
            gap: 14px;
            border-top: 1px solid #ececec;
        }
        #input {
            flex: 1;
            border: none;
            border-radius: 12px;
            background: var(--apple-white);
            padding: 16px 20px;
            font-size: 1.08rem;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            outline: none;
        }
        #send {
            background: #0071e3;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.08rem;
            padding: 0 38px;
            cursor: pointer;
            font-weight: 500;
            transition: background .18s;
        }
        #send:active {
            background: #005bb5;
        }
        #nav-link {
            position: absolute;
            right: 32px;
            top: 22px;
            font-size: 16px;
            color: #0071e3;
            text-decoration: none;
            font-weight: 600;
            z-index: 100;
        }
        #filter-bar {
            display: none;
            gap: 17px;
            align-items: center;
            margin: 25px 0 32px 0;
            padding-left: 36px;
        }
        #filter-bar.active {
            display: flex;
        }
        #sort, #filter-btn {
            border: none;
            border-radius: 10px;
            background: #eaf4fc;
            padding: 11px 14px;
            font-size: 1rem;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            outline: none;
            cursor: pointer;
            color: #0071e3;
            font-weight: 500;
            border: 2px solid #0071e3;
            transition: background .15s, border .15s;
        }
        #sort:focus, #sort.active {
            background: #0071e3;
            color: #fff;
        }
        @media (max-width:1320px){
            #chat-container{max-width:99vw;}
            .prod{min-width:210px;max-width:350px;}
        }
        @media (max-width:900px){
            #chat-container{max-width:100vw;}
            #chat{padding:12px;}
            #input-area{padding:12px;}
            .prod{min-width:175px;max-width:100%;}
            #chat-header{padding:18px;}
            #nav-link{right:10px;top:10px;}
            #filter-bar{padding-left:8px;}
        }
    </style>
</head>
<body>
<a id="nav-link" href="index.html">← На главную</a>
<div id="chat-container">
    <div id="chat-header">
        <span>Каталог товаров (интеллектуальный поиск)</span>
    </div>
    <div id="chat">
        <div class="msg bot">Здравствуйте! Я помогу подобрать подходящий товар. Напишите ваш запрос.</div>
    </div>
    <div id="filter-bar">
        <span style="font-size:1.03rem;">Сортировать:</span>
        <select id="sort" class="active">
            <option value="">Без сортировки</option>
            <option value="price_asc">Цена ↑</option>
            <option value="price_desc">Цена ↓</option>
            <option value="rating_desc">Рейтинг ↓</option>
            <option value="rating_asc">Рейтинг ↑</option>
        </select>
    </div>
    <form id="input-area" autocomplete="off">
        <input id="input" placeholder="Например: Кондиционер LG, рейтинг выше 4.0" autofocus>
        <button id="send" type="submit">→</button>
    </form>
</div>
<script>
let lastProducts = [];
const chat = document.getElementById('chat');
const filterBar = document.getElementById('filter-bar');
const sortSel = document.getElementById('sort');

function showMsg(text, who='bot') {
    let div = document.createElement('div');
    div.className = 'msg ' + who;
    div.textContent = text;
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
}

function showProducts(products) {
    const old = chat.querySelector('.products');
    if (old) old.remove();

    if (!products || !products.length) {
        filterBar.classList.remove('active');
        return;
    }
    filterBar.classList.add('active');
    let wrap = document.createElement('div');
    wrap.className = 'products';
    products.forEach(p => {
        let d = document.createElement('div');
        d.className = 'prod';
        d.innerHTML = `
            <div class="prod-category">${(p.main_category_ru||'') + (p.sub_category_ru ? " / "+p.sub_category_ru : "")}</div>
            <div class="prod-name">${p.name}</div>
            <div class="prod-price">${p.actual_price ? p.actual_price + '₽' : ''}</div>
            <div>Рейтинг: ${p.ratings || 'нет'}</div>
            <div class="prod-more">
                <a href="${p.link || '#'}" target="_blank" rel="noopener" style="width:100%">
                    <button class="prod-more-btn" type="button">Подробнее</button>
                </a>
            </div>
        `;
        wrap.appendChild(d);
    });
    chat.appendChild(wrap);
    chat.scrollTop = chat.scrollHeight;
}

document.getElementById('input-area').onsubmit = async e => {
    e.preventDefault();
    const input = document.getElementById('input');
    const text = input.value.trim();
    if (!text) return;
    showMsg(text, 'user');
    input.value = '';
    input.disabled = true;
    sortSel.value = '';

    // Чистим старые товары
    const old = chat.querySelector('.products');
    if (old) old.remove();
    filterBar.classList.remove('active');

    // Только Flask!
    let flaskResp = await fetch('/api/cosine_search', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({query: text})
    });
    let flaskData = await flaskResp.json();

    input.disabled = false;

    // Показываем только ответ и товары от Flask
    if (flaskData.reply) showMsg(flaskData.reply, 'bot');
    if (flaskData.products && flaskData.products.length) {
        lastProducts = flaskData.products;
        showProducts(lastProducts);
    } else {
        lastProducts = [];
    }
};

sortSel.onchange = () => {
    sortSel.classList.add('active');
    if (!lastProducts.length) return;
    let arr = [...lastProducts];
    switch (sortSel.value) {
        case 'price_asc':
            arr.sort((a,b)=>(parseFloat(a.actual_price)||0)-(parseFloat(b.actual_price)||0)); break;
        case 'price_desc':
            arr.sort((a,b)=>(parseFloat(b.actual_price)||0)-(parseFloat(a.actual_price)||0)); break;
        case 'rating_asc':
            arr.sort((a,b)=>(parseFloat(a.ratings)||0)-(parseFloat(b.ratings)||0)); break;
        case 'rating_desc':
            arr.sort((a,b)=>(parseFloat(b.ratings)||0)-(parseFloat(a.ratings)||0)); break;
    }
    showProducts(arr);
};
sortSel.onblur = () => { sortSel.classList.remove('active'); }
</script>
</body>
</html>