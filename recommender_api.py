import uvicorn
from fastapi import FastAPI
from typing import List
import pymysql
import numpy as np

# ---- ВАЖНО! ----
# Скопируйте значения из вашего config.php
MYSQL_HOST = "localhost"
MYSQL_DB = "cg38360_vkr"
MYSQL_USER = "cg38360_vkr"
MYSQL_PASSWORD = "05122003BagVA"
MYSQL_TABLE = "data"

from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense
from tensorflow.keras.preprocessing.text import Tokenizer

# Получить все товары из MySQL
def load_products():
    conn = pymysql.connect(
        host=MYSQL_HOST,
        user=MYSQL_USER,
        password=MYSQL_PASSWORD,
        db=MYSQL_DB,
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )
    with conn.cursor() as cursor:
        cursor.execute(f"SELECT Name as name, Price as price, rating FROM {MYSQL_TABLE}")
        products = cursor.fetchall()
    conn.close()
    return products

# Готовим FastAPI и модель
app = FastAPI()
products = load_products()
product_texts = [p["name"] for p in products]

tokenizer = Tokenizer(num_words=2000)
tokenizer.fit_on_texts(product_texts)
X = tokenizer.texts_to_matrix(product_texts)
y = np.random.rand(len(products), 1)  # фиктивные веса

model = Sequential([
    Dense(32, activation='relu', input_shape=(X.shape[1],)),
    Dense(16, activation='relu'),
    Dense(1, activation='sigmoid')
])
model.compile(optimizer='adam', loss='mse')
model.fit(X, y, epochs=5, verbose=0)

@app.get("/recommend")
def recommend(user_query: str, top_k: int = 5) -> List[dict]:
    # При каждом запросе актуализируем список товаров
    global products, product_texts, tokenizer, X
    products = load_products()
    product_texts = [p["name"] for p in products]
    tokenizer.fit_on_texts(product_texts)
    X = tokenizer.texts_to_matrix(product_texts)

    user_vec = tokenizer.texts_to_matrix([user_query])
    # "Схожесть": косинусная или просто скалярное произведение
    scores = np.dot(X, user_vec.T).flatten()
    top_indices = np.argsort(scores)[::-1][:top_k]
    result = []
    for idx in top_indices:
        prod = products[idx]
        result.append({
            "name": prod["name"],
            "price": float(prod["price"]),
            "rating": float(prod["rating"]) if prod["rating"] is not None else None,
        })
    return result

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)