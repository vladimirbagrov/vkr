# import os
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

# # @app.route('/api/search', methods=['POST'])
# # def search():
# #     data = request.get_json(force=True)
# #     msg = data.get('message', '').strip()
# #     print(f"Message: '{msg}'")
# #     words = [w for w in msg.split() if len(w) > 2]
# #     if not words:
# #         return jsonify({"reply": "Пустой запрос", "products": []})

# #     fields = ["name", "Category"]
# #     conn = get_db_connection()
# #     cursor = conn.cursor()
# #     products = []
# #     for field in fields:
# #         conditions = [f"{field} LIKE %s" for w in words]
# #         params = [f"%{w}%" for w in words]
# #         where = " OR ".join(conditions)
# #         sql = f"""
# #             SELECT id, name, Category, `Selling Price`, `About Product`, description, `Product Url`
# #             FROM data2
# #             WHERE {where}
# #         """
# #         print(f"SQL: {sql}")
# #         print(f"Params: {params}")
# #         cursor.execute(sql, params)
# #         products = cursor.fetchall()
# #         if products:
# #             break  # если найдено — остановить цикл

# #     conn.close()
# #     return jsonify({
# #         "reply": f"Найдено товаров: {len(products)}",
# #         "products": products
# #     })

# if __name__ == '__main__':
#     app.run(host='127.0.0.1', port=5000, debug=True)
import os
import pymysql
import numpy as np
import pandas as pd
from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from flask_cors import CORS
import re

# Ограничиваем потоки для sklearn/numpy
os.environ["OPENBLAS_NUM_THREADS"] = "1"
os.environ["OMP_NUM_THREADS"] = "1"
os.environ["MKL_NUM_THREADS"] = "1"
os.environ["NUMEXPR_NUM_THREADS"] = "1"
os.environ["VECLIB_MAXIMUM_THREADS"] = "1"

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

# --- Загрузка товаров из БД в DataFrame и подготовка TF-IDF ----

def load_products_from_db():
    conn = get_db_connection()
    df = pd.read_sql("""
        SELECT id, name, Category, `Selling Price`, `About Product`, description, `Product Url`
        FROM data2
    """, conn)
    conn.close()
    return df

def preprocess_text(text):
    if not isinstance(text, str):
        return ""
    text = text.lower()
    text = re.sub(r"[^a-zа-я0-9 ]", " ", text)
    return text

# Загружаем товары и строим матрицу один раз при старте Flask
products_df = load_products_from_db()
# Формируем поле для семантического поиска
products_df["sem_text"] = (
    products_df["name"].fillna("") + " " +
    products_df["Category"].fillna("") + " " +
    products_df["description"].fillna("")
).apply(preprocess_text)

# Обучаем TF-IDF
vectorizer = TfidfVectorizer(stop_words=None)  # Можно добавить стоп-слова
tfidf_matrix = vectorizer.fit_transform(products_df["sem_text"])

@app.route('/api/recommend', methods=['POST'])
def recommend():
    data = request.get_json(force=True)
    user_query = data.get('message', '').strip()
    query_processed = preprocess_text(user_query)
    query_vec = vectorizer.transform([query_processed])
    cos_sim = cosine_similarity(query_vec, tfidf_matrix)[0]
    # Получаем индексы топ-10 наиболее похожих товаров
    top_idx = np.argsort(cos_sim)[-10:][::-1]
    results = products_df.iloc[top_idx]
    # Можно фильтровать по cos_sim > threshold, если нужно
    products = results.to_dict(orient="records")
    return jsonify({
        "reply": f"Найдено товаров: {len(products)}",
        "products": products
    })

# Старый простой поиск (оставить по желанию)
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

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)