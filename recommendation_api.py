# import os

# # Ограничиваем потоки для sklearn/numpy
# os.environ["OPENBLAS_NUM_THREADS"] = "1"
# os.environ["OMP_NUM_THREADS"] = "1"
# os.environ["MKL_NUM_THREADS"] = "1"
# os.environ["NUMEXPR_NUM_THREADS"] = "1"
# os.environ["VECLIB_MAXIMUM_THREADS"] = "1"

# import pymysql
# import numpy as np
# from flask import Flask, request, jsonify
# from sklearn.feature_extraction.text import TfidfVectorizer
# from sklearn.metrics.pairwise import cosine_similarity
# from flask_cors import CORS

# # Для поддержки стоп-слов английского и русского
# import nltk
# nltk.download('stopwords')
# from nltk.corpus import stopwords

# app = Flask(__name__)
# CORS(app)

# def get_db_connection():
#     return pymysql.connect(
#         host='localhost',
#         user='cg38360_vkr',
#         password='05122003BagVA',
#         db='cg38360_vkr',
#         charset='utf8mb4',
#         cursorclass=pymysql.cursors.DictCursor
#     )

# def get_all_products():
#     conn = get_db_connection()
#     cursor = conn.cursor()
#     sql = """SELECT id, name, processed_text, Category, `Selling Price`, `About Product`, description, `Product Url` FROM data2"""
#     cursor.execute(sql)
#     rows = cursor.fetchall()
#     conn.close()
#     return rows

# def find_best_match(user_message, all_products):
#     """
#     Поиск наиболее релевантного товара по user_message.
#     Сравнивает по количеству общих слов (и рус, и англ).
#     """
#     user_message_lower = user_message.lower()
#     best_row = None
#     best_score = 0
#     for row in all_products:
#         prod_name = (row.get('name') or '').lower()
#         if not prod_name:
#             continue
#         # Считаем количество общих слов между названием товара и сообщением пользователя
#         prod_tokens = set(prod_name.replace('-', ' ').replace('—', ' ').split())
#         user_tokens = set(user_message_lower.replace('-', ' ').replace('—', ' ').split())
#         score = len(prod_tokens & user_tokens)
#         # Также допускаем частичное подстрочное вхождение фразы для редких случаев
#         if prod_name in user_message_lower or user_message_lower in prod_name:
#             score += 2
#         if score > best_score:
#             best_row = row
#             best_score = score
#     return best_row

# @app.route('/api/recommend', methods=['POST'])
# def recommend():
#     data = request.get_json(force=True)
#     user_message = data.get('message', '').strip()
#     all_products = get_all_products()
#     matched_product = find_best_match(user_message, all_products)

#     # debug print (можно убрать)
#     print("user_message:", repr(user_message))
#     print("matched_product:", matched_product['name'] if matched_product else None)

#     if not matched_product or not matched_product['processed_text']:
#         return jsonify({
#             "reply": "Товар не найден или нет информации для поиска похожих.",
#             "products": []
#         })

#     query_vec = matched_product['processed_text']

#     # Стоп-слова для русского и английского
#     stop_words = set(stopwords.words('english')) | set(stopwords.words('russian'))

#     docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]
#     vectorizer = TfidfVectorizer(stop_words=stop_words)
#     tfidf_matrix = vectorizer.fit_transform([query_vec] + docs)
#     sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()

#     # Берём топ-10 самых похожих (кроме самого товара)
#     top_idx = np.argsort(sim_scores)[-10:][::-1]
#     recommended = [
#         all_products[i] for i in top_idx
#         if sim_scores[i] > 0 and all_products[i]['id'] != matched_product['id']
#     ]

#     return jsonify({
#         "reply": f"Похожие товары для: {matched_product['name']}" if recommended else "Не найдено похожих товаров",
#         "products": recommended
#     })

# @app.route('/api/search', methods=['POST'])
# def search():
#     data = request.get_json(force=True)
#     msg = data.get('message', '').strip()
#     conn = get_db_connection()
#     cursor = conn.cursor()
#     sql = """
#         SELECT id, name, Category, `Selling Price`, `About Product`, description, `Product Url`
#         FROM data2
#         WHERE name LIKE %s
#            OR Category LIKE %s
#            OR description LIKE %s
#         LIMIT 10
#     """
#     like = f"%{msg}%"
#     cursor.execute(sql, (like, like, like))
#     products = cursor.fetchall()
#     conn.close()
#     return jsonify({
#         "reply": f"Найдено товаров: {len(products)}",
#         "products": products
#     })

# @app.route('/api/list_products')
# def list_products():
#     all_products = get_all_products()
#     return jsonify([row['name'] for row in all_products])

# if __name__ == '__main__':
#     app.run(host='127.0.0.1', port=5000, debug=True)
import os

os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"

import pymysql
import numpy as np
from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from flask_cors import CORS

import nltk
nltk.download('stopwords')
from nltk.corpus import stopwords

app = Flask(__name__)
CORS(app)

def get_db_connection():
    return pymysql.connect(
        host='localhost',
        user='cg38360_vkr',
        password='05122003BagVA',
        db='cg38360_vkr',
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )

# --- Для первой страницы (рекомендации и поиск, таблица data2) ---

def get_all_products_data2():
    conn = get_db_connection()
    cursor = conn.cursor()
    sql = """SELECT id, name, processed_text, Category, `Selling Price`, `About Product`, description, `Product Url` FROM data2"""
    cursor.execute(sql)
    rows = cursor.fetchall()
    conn.close()
    return rows

def find_best_match(user_message, all_products):
    user_message_lower = user_message.lower()
    best_row = None
    best_score = 0
    for row in all_products:
        prod_name = (row.get('name') or '').lower()
        if not prod_name:
            continue
        prod_tokens = set(prod_name.replace('-', ' ').replace('—', ' ').split())
        user_tokens = set(user_message_lower.replace('-', ' ').replace('—', ' ').split())
        score = len(prod_tokens & user_tokens)
        if prod_name in user_message_lower or user_message_lower in prod_name:
            score += 2
        if score > best_score:
            best_row = row
            best_score = score
    return best_row

@app.route('/api/recommend', methods=['POST'])
def recommend():
    data = request.get_json(force=True)
    user_message = data.get('message', '').strip()
    all_products = get_all_products_data2()
    matched_product = find_best_match(user_message, all_products)

    print("user_message:", repr(user_message))
    print("matched_product:", matched_product['name'] if matched_product else None)

    if not matched_product or not matched_product['processed_text']:
        return jsonify({
            "reply": "Товар не найден или нет информации для поиска похожих.",
            "products": []
        })

    query_vec = matched_product['processed_text']
    stop_words = set(stopwords.words('english')) | set(stopwords.words('russian'))
    docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]
    vectorizer = TfidfVectorizer(stop_words=stop_words)
    tfidf_matrix = vectorizer.fit_transform([query_vec] + docs)
    sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()
    top_idx = np.argsort(sim_scores)[-10:][::-1]
    recommended = [
        all_products[i] for i in top_idx
        if sim_scores[i] > 0 and all_products[i]['id'] != matched_product['id']
    ]

    return jsonify({
        "reply": f"Похожие товары для: {matched_product['name']}" if recommended else "Не найдено похожих товаров",
        "products": recommended
    })

@app.route('/api/search', methods=['POST'])
def search():
    data = request.get_json(force=True)
    msg = data.get('message', '').strip()
    conn = get_db_connection()
    cursor = conn.cursor()
    sql = """
        SELECT id, name, Category, `Selling Price`, `About Product`, description, `Product Url`
        FROM data2
        WHERE name LIKE %s
           OR Category LIKE %s
           OR description LIKE %s
        LIMIT 10
    """
    like = f"%{msg}%"
    cursor.execute(sql, (like, like, like))
    products = cursor.fetchall()
    conn.close()
    return jsonify({
        "reply": f"Найдено товаров: {len(products)}",
        "products": products
    })

@app.route('/api/list_products')
def list_products():
    all_products = get_all_products_data2()
    return jsonify([row['name'] for row in all_products])

# --- Для второй страницы (поиск по косинусному расстоянию, таблица data) ---

def get_all_products_data():
    conn = get_db_connection()
    cursor = conn.cursor()
    sql = """SELECT id, name, main_category_ru, sub_category_ru, actual_price, ratings, description, processed_text, link FROM data"""
    cursor.execute(sql)
    rows = cursor.fetchall()
    conn.close()
    return rows

@app.route('/api/cosine_search', methods=['POST'])
def cosine_search():
    data = request.get_json(force=True)
    user_query = data.get('query', '').strip()
    if not user_query:
        return jsonify({"reply": "Пустой запрос", "products": []})
    all_products = get_all_products_data()
    docs = []
    for row in all_products:
        text = (
            (row.get('processed_text') or '') + ' ' +
            (row.get('name') or '') + ' ' +
            (row.get('main_category_ru') or '') + ' ' +
            (row.get('sub_category_ru') or '') + ' ' +
            (row.get('description') or '')
        )
        docs.append(text.strip())
    stop_words = set(stopwords.words('english')) | set(stopwords.words('russian'))
    vectorizer = TfidfVectorizer(stop_words=stop_words)
    tfidf_matrix = vectorizer.fit_transform([user_query] + docs)
    sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()
    top_idx = np.argsort(sim_scores)[-12:][::-1]
    results = [all_products[i] for i in top_idx if sim_scores[i] > 0]
    return jsonify({
        "reply": f"Найдено товаров: {len(results)}",
        "products": results
    })

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)