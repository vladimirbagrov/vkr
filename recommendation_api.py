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
# from difflib import get_close_matches
# import re

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

# def extract_product_name(user_message):
#     """
#     Извлекает название товара из пользовательской фразы, например:
#     'порекомендуй мне товары похожие на ...'
#     Возвращает всё, что после последнего 'на ' или исходную строку, если такого нет.
#     """
#     lowered = user_message.lower()
#     if " на " in lowered:
#         idx = lowered.rfind(" на ")
#         return user_message[idx + 4:].strip()
#     return user_message.strip()

# def normalize_name(name):
#     """
#     Приведение строки к нижнему регистру без спецсимволов для сравнения.
#     """
#     return re.sub(r'[^a-z0-9а-яё ]+', '', name.lower()).strip()

# def get_processed_text_by_name(product_name):
#     # Получить processed_text по имени товара
#     conn = get_db_connection()
#     cursor = conn.cursor()
#     sql = "SELECT processed_text FROM data2 WHERE name = %s"
#     cursor.execute(sql, (product_name,))
#     row = cursor.fetchone()
#     conn.close()
#     if row and row['processed_text']:
#         return row['processed_text']
#     return None

# def get_all_products():
#     # Получить все товары с нужными полями
#     conn = get_db_connection()
#     cursor = conn.cursor()
#     sql = """SELECT id, name, processed_text, Category, `Selling Price`, `About Product`, description, `Product Url` FROM data2"""
#     cursor.execute(sql)
#     rows = cursor.fetchall()
#     conn.close()
#     return rows

# @app.route('/api/recommend', methods=['POST'])
# def recommend():
#     data = request.get_json(force=True)
#     user_message = data.get('message', '').strip()
#     product_name = extract_product_name(user_message)
#     print(f"User message: {user_message}")
#     print(f"Extracted product_name: {product_name}")

#     all_products = get_all_products()

#     # Сначала пробуем точное совпадение
#     processed_text = get_processed_text_by_name(product_name)

#     # Если не найдено — нормализуем и делаем fuzzy matching по имени (без регистра и спецсимволов)
#     if not processed_text:
#         norm_product_name = normalize_name(product_name)
#         norm_names = [normalize_name(row['name']) for row in all_products]
#         matches = get_close_matches(norm_product_name, norm_names, n=1, cutoff=0.5)
#         if matches:
#             ind = norm_names.index(matches[0])
#             product_name = all_products[ind]['name']
#             processed_text = get_processed_text_by_name(product_name)
#             print(f"Fuzzy matched product_name: {product_name}")
#         else:
#             print("No fuzzy match found.")
#             return jsonify({"reply": "Не найдено похожих товаров", "products": []})

#     # Если всё равно не найден processed_text — возвращаем пусто
#     if not processed_text:
#         print("No processed_text found in DB for product_name.")
#         return jsonify({"reply": "Не найдено похожих товаров", "products": []})

#     docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]
#     # Векторизация: первым идёт processed_text искомого товара
#     vectorizer = TfidfVectorizer(stop_words='english')
#     tfidf_matrix = vectorizer.fit_transform([processed_text] + docs)
#     sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()

#     # Берём топ-10 похожих (кроме самого товара)
#     top_idx = np.argsort(sim_scores)[-10:][::-1]
#     recommended = [all_products[i] for i in top_idx]

#     # Исключаем сам товар из рекомендаций (точное совпадение по имени)
#     recommended = [item for item in recommended if normalize_name(item['name']) != normalize_name(product_name)]

#     return jsonify({
#         "reply": f"Похожие товары для: {product_name}" if recommended else "Не найдено похожих товаров",
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

# # Маршрут для отладки: список товаров
# @app.route('/api/list_products')
# def list_products():
#     all_products = get_all_products()
#     return jsonify([row['name'] for row in all_products])

# if __name__ == '__main__':
#     app.run(host='127.0.0.1', port=5000, debug=True)
import os

# Ограничиваем потоки для sklearn/numpy
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

def get_all_products():
    conn = get_db_connection()
    cursor = conn.cursor()
    sql = """SELECT id, name, processed_text, Category, `Selling Price`, `About Product`, description, `Product Url` FROM data2"""
    cursor.execute(sql)
    rows = cursor.fetchall()
    conn.close()
    return rows

@app.route('/api/recommend', methods=['POST'])
def recommend():
    data = request.get_json(force=True)
    user_message = data.get('message', '').strip()
    all_products = get_all_products()
    docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]

    # Semantic search по сообщению пользователя
    vectorizer = TfidfVectorizer(stop_words='english')
    tfidf_matrix = vectorizer.fit_transform([user_message] + docs)
    sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()
    top_idx = np.argsort(sim_scores)[-10:][::-1]
    recommended = [all_products[i] for i in top_idx if sim_scores[i] > 0]

    return jsonify({
        "reply": f"Похожие товары по вашему запросу" if recommended else "Не найдено похожих товаров",
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
    all_products = get_all_products()
    return jsonify([row['name'] for row in all_products])

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)