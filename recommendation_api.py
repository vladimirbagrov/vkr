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

# # @app.route('/api/recommend', methods=['POST'])
# # def recommend():
# #     data = request.get_json(force=True)
# #     user_message = data.get('message', '').strip()
# #     all_products = get_all_products()
# #     docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]

# #     # Semantic search по сообщению пользователя
# #     vectorizer = TfidfVectorizer(stop_words='english')
# #     tfidf_matrix = vectorizer.fit_transform([user_message] + docs)
# #     sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()
# #     top_idx = np.argsort(sim_scores)[-10:][::-1]
# #     recommended = [all_products[i] for i in top_idx if sim_scores[i] > 0]

# #     return jsonify({
# #         "reply": f"Похожие товары по вашему запросу" if recommended else "Не найдено похожих товаров",
# #         "products": recommended
# #     })

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

def extract_product_name(user_message, all_products):
    """
    Улучшенная функция: ищет по всем названиям товаров, даже если пользователь написал длинную фразу.
    Возвращает наиболее длинное совпадающее имя товара из базы.
    """
    user_message_lower = user_message.lower()
    candidates = []
    for row in all_products:
        prod_name = (row.get('name') or '').strip()
        prod_name_lower = prod_name.lower()
        if prod_name_lower and prod_name_lower in user_message_lower:
            candidates.append((len(prod_name_lower), prod_name))
    if candidates:
        # Берем самое длинное совпадение — оно скорее всего точное
        return max(candidates, key=lambda x: x[0])[1]
    # fallback: всё, что после последнего " на "
    lowered = user_message_lower
    if " на " in lowered:
        idx = lowered.rfind(" на ")
        return user_message[idx + 4:].strip()
    return user_message.strip()

@app.route('/api/recommend', methods=['POST'])
def recommend():
    data = request.get_json(force=True)
    user_message = data.get('message', '').strip()
    all_products = get_all_products()
    product_name = extract_product_name(user_message, all_products)

    # debug print (можно убрать)
    print("user_message:", repr(user_message))
    print("product_name:", repr(product_name))
    print("all names:", [repr(row['name']) for row in all_products])

    matched_product = next(
        (row for row in all_products if (row.get('name') or '').strip().lower() == product_name.strip().lower()),
        None
    )
    if not matched_product or not matched_product['processed_text']:
        return jsonify({
            "reply": "Товар не найден или нет информации для поиска похожих.",
            "products": []
        })

    query_vec = matched_product['processed_text']
    docs = [row['processed_text'] if row['processed_text'] else '' for row in all_products]
    vectorizer = TfidfVectorizer(stop_words='english')
    tfidf_matrix = vectorizer.fit_transform([query_vec] + docs)
    sim_scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:]).flatten()

    # Берём топ-10 самых похожих (кроме самого товара)
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
    all_products = get_all_products()
    return jsonify([row['name'] for row in all_products])

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)