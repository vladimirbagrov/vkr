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
def search():
    data = request.get_json(force=True)
    msg = data.get('message', '').strip()
    conn = get_db_connection()
    cursor = conn.cursor()  # добавлено!
    sql = """
        SELECT id, name, Category, `Selling Price`, `About Product`, description, `Product Url`
        FROM data2
        WHERE name LIKE %s
           OR Category LIKE %s
           OR description LIKE %s
        LIMIT 10
    """
    like = f"%{msg}%"
    print(f"SQL LIKE pattern: '{like}'")
    cursor.execute(sql, (like, like, like))
    products = cursor.fetchall()
    print(f"Found rows: {len(products)}")
    conn.close()
    return jsonify({
        "reply": f"Найдено товаров: {len(products)}",
        "products": products
    })

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)