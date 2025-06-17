import pymysql
import pymorphy2

print("Скрипт запущен")

morph = pymorphy2.MorphAnalyzer()

def lemmatize(text):
    return ' '.join([morph.parse(w)[0].normal_form for w in text.split() if w.isalpha()])

try:
    conn = pymysql.connect(
        host='localhost',
        user='cg38360_vkr',
        password='05122003BagVA',
        db='cg38360_vkr',
        charset='utf8mb4',
        cursorclass=pymysql.cursors.DictCursor
    )

    with conn.cursor() as cursor:
        # Определи, какие поля есть в таблице data
        cursor.execute("SHOW COLUMNS FROM data")
        columns = set(row['Field'] for row in cursor.fetchall())

        # Формируем список реально существующих текстовых полей
        fields = []
        for f in ['name', 'main_category_ru', 'sub_category_ru']:
            if f in columns:
                fields.append(f)
        select_fields = ', '.join(['id'] + fields)
        cursor.execute(f"SELECT {select_fields} FROM data")
        rows = cursor.fetchall()
        print(f"Обработка таблицы data, всего строк: {len(rows)}")
        for idx, row in enumerate(rows):
            text = ' '.join([str(row.get(f, '') or '') for f in fields])
            lem = lemmatize(text.lower())
            cursor.execute("UPDATE data SET lemmas=%s WHERE id=%s", (lem, row['id']))
            if idx % 100 == 0:
                print(f"data: {idx}/{len(rows)}")
        conn.commit()
    conn.close()
    print("Готово!")
except Exception as e:
    print("Ошибка:", e)