<?php
class ProductSearch {
    /**
     * Поиск товаров по запросу пользователя
     */
    public static function findProducts($query) {
        global $pdo;

        if (!$pdo || empty($query)) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT name, price 
            FROM data 
            WHERE name LIKE :query 
            OR SOUNDEX(name) = SOUNDEX(:query) 
            LIMIT 25
        ");

        $stmt->execute(['query' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение случайных товаров, если ничего не найдено
     */
    public static function getRandomProducts() {
        global $pdo;

        if (!$pdo) {
            return [];
        }

        $stmt = $pdo->query("
            SELECT name, price 
            FROM data 
            ORDER BY RAND() 
            LIMIT 6
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
