<?php
class ProductSearch {
    public static function findProducts($query) {
        global $pdo;

        if (!$pdo || empty($query)) {
            return [];
        }

        $query = trim($query);
        $words = preg_split('/[\s,]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        $where = [];
        $params = [];
        foreach ($words as $idx => $word) {
            $where[] = "Name LIKE :word$idx";
            $params["word$idx"] = "%$word%";
        }
        $where_str = implode(' AND ', $where);

        $sql = "SELECT Name as name, Price as price, rating FROM data WHERE $where_str LIMIT 25";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            $stmt = $pdo->prepare("SELECT Name as name, Price as price, rating FROM data WHERE Name LIKE :query LIMIT 25");
            $stmt->execute(['query' => "%$query%"]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}