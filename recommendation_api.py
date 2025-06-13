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

@app.route('/api/search', methods=['POST'])
def search_products():
    data = request.get_json()
    msg = data.get('message', '').strip()
    if not msg:
        return jsonify({'reply': 'Пустой запрос', 'products': []}), 400

    with get_db_connection() as conn:
        with conn.cursor() as cursor:
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

    # Можно интегрировать AI-ответ, пока простой шаблон:
    if products:
        reply = "Нашёл товары по вашему запросу."
    else:
        reply = "К сожалению, по вашему запросу ничего не найдено. Попробуйте изменить формулировку."

    return jsonify({
        'reply': reply,
        'products': products
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)