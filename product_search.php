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
            SELECT name, price, article
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
            SELECT name, price, article
            FROM data
            ORDER BY RAND()
            LIMIT 6
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение похожих товаров (для рекомендаций)
     */
    public static function getSimilarProducts($productName, $limit = 6) {
        global $pdo;

        if (!$pdo || empty($productName)) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT name, price, article
            FROM data
            WHERE name LIKE :name AND name != :exact
            ORDER BY RAND()
            LIMIT :limit
        ");
        $stmt->bindValue(':name', '%' . $productName . '%');
        $stmt->bindValue(':exact', $productName);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}