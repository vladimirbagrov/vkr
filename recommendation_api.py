# import pandas as pd
# from fastapi import FastAPI, Query
# from fastapi.middleware.cors import CORSMiddleware
# from sqlalchemy import create_engine
# from sklearn.feature_extraction.text import TfidfVectorizer
# from sklearn.metrics.pairwise import cosine_similarity

# app = FastAPI()

# app.add_middleware(
#     CORSMiddleware,
#     allow_origins=["*"],
#     allow_methods=["*"],
#     allow_headers=["*"]
# )

# DB_USER = "cg38360_vkr"
# DB_PASS = "05122003BagVA"  # замените на ваш пароль
# DB_HOST = "localhost"
# DB_NAME = "cg38360_vkr"
# engine = create_engine(f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}?charset=utf8mb4")

# # Загрузка данных и построение матрицы TF-IDF
# with engine.connect() as conn:
#     df = pd.read_sql(
#         "SELECT id, `Product Name`, Category, `Selling Price`, `About Product`, `Technical Details`, `Product Url`, description, processed_text FROM data2",
#         conn
#     )

# df['processed_text'] = df['processed_text'].fillna('')
# df['name'] = df['Product Name']
# tfidf = TfidfVectorizer(stop_words='english')
# tfidf_matrix = tfidf.fit_transform(df['processed_text'])
# indices = pd.Series(df.index, index=df['name']).drop_duplicates()
# cosine_sim = cosine_similarity(tfidf_matrix, tfidf_matrix)

# def get_recommendations(name, cosine_sim=cosine_sim):
#     if name not in indices:
#         return []
#     idx = indices[name]
#     sim_scores = list(enumerate(cosine_sim[idx]))
#     sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
#     sim_scores = sim_scores[1:11]  # exclude itself, top-10
#     product_indices = [i[0] for i in sim_scores]
#     result = df.iloc[product_indices][[
#         'name', 'Category', 'Selling Price', 'About Product', 'Technical Details', 'Product Url', 'description'
#     ]].to_dict(orient='records')
#     return result

# @app.get("/recommend")
# def recommend(name: str = Query(..., description="Название товара для поиска похожих")):
#     return get_recommendations(name)

import pymysql
import pandas as pd
import numpy as np
from flask import Flask, request, jsonify
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

# --- Настройки подключения ---
MYSQL_HOST = 'localhost'
MYSQL_USER = 'cg38360_vkr'
MYSQL_PASSWORD = '05122003BagVA'  # ВАЖНО: замените на свой пароль!
MYSQL_DB = 'cg38360_vkr'
MYSQL_TABLE = 'data2'

app = Flask(__name__)

@app.route("/")
def index():
    return "Hello, Flask!"

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)

def get_all_products():
    conn = pymysql.connect(host=MYSQL_HOST, user=MYSQL_USER, password=MYSQL_PASSWORD, database=MYSQL_DB, charset='utf8mb4')
    query = f"SELECT id, name, Category, `Selling Price`, processed_text, `Product Url`, description FROM {MYSQL_TABLE}"
    df = pd.read_sql(query, conn)
    conn.close()
    return df

@app.route('/recommend', methods=['GET'])
def recommend():
    name = request.args.get('name')
    category = request.args.get('category')
    n = int(request.args.get('n', 5))
    
    df = get_all_products()
    df['processed_text'] = df['processed_text'].fillna('')
    df['name'] = df['name'].fillna('')
    df['Category'] = df['Category'].fillna('')
    df['Selling Price'] = df['Selling Price'].replace('[\$,]', '', regex=True).astype(float)
    
    # --- Рекомендации по названию товара ---
    if name:
        matches = df[df['name'].str.lower().str.contains(name.lower())]
        if matches.empty:
            return jsonify([])
        idx = matches.index[0]
        base_price = df.iloc[idx]['Selling Price']
        vectorizer = TfidfVectorizer(stop_words='english')
        tfidf_matrix = vectorizer.fit_transform(df['processed_text'])
        similarities = cosine_similarity(tfidf_matrix[idx], tfidf_matrix)[0]
        prices = df['Selling Price'].values
        price_diff = np.abs(prices - base_price) / (base_price if base_price else 1)
        price_sim = 1 - price_diff
        score = 0.7 * similarities + 0.3 * price_sim
        score[idx] = -1
        top_indices = np.argsort(score)[-n:][::-1]
        result = df.iloc[top_indices][['name','Category','Selling Price','Product Url','description']]
        return jsonify(result.to_dict(orient='records'))
    
    # --- Рекомендации по категории ---
    if category:
        cat_df = df[df['Category'].str.lower().str.contains(category.lower())].copy()
        if cat_df.empty or len(cat_df) < 2:
            return jsonify([])
        vectorizer = TfidfVectorizer(stop_words='english')
        tfidf_matrix = vectorizer.fit_transform(cat_df['processed_text'])
        # Просто случайные N товаров из категории (можно усложнить)
        indices = np.random.choice(cat_df.index, size=min(n, len(cat_df)), replace=False)
        result = cat_df.loc[indices][['name','Category','Selling Price','Product Url','description']]
        return jsonify(result.to_dict(orient='records'))
    return jsonify([])

@app.route('/search', methods=['GET'])
def search():
    query = request.args.get('q', '')
    n = int(request.args.get('n', 10))
    df = get_all_products()
    mask = (
        df['name'].str.lower().str.contains(query.lower()) |
        df['Category'].str.lower().str.contains(query.lower()) |
        df['processed_text'].str.lower().str.contains(query.lower())
    )
    result = df[mask].head(n)[['name','Category','Selling Price','Product Url','description']]
    return jsonify(result.to_dict(orient='records'))

if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000)