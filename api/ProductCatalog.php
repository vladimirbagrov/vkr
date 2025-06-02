<?php
require_once __DIR__ . '/db.php';

class ProductCatalog
{
    public static function search($text)
    {
        $pdo = db();
        $sql = "SELECT name, ratings, actual_price, main_category_ru, sub_category_ru 
                FROM data 
                WHERE MATCH(name) AGAINST(:q IN NATURAL LANGUAGE MODE)
                   OR name LIKE :like
                   OR main_category_ru LIKE :like
                   OR sub_category_ru LIKE :like
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'q' => $text,
            'like' => '%' . $text . '%'
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function random($limit = 5)
    {
        $pdo = db();
        $stmt = $pdo->query("SELECT name, ratings, actual_price, main_category_ru, sub_category_ru FROM data ORDER BY RAND() LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}