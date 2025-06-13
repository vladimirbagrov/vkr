<?php
// require_once __DIR__ . '/db.php';

// class ProductCatalog
// {
//     // Поиск по категории/ключевым словам
//     public static function search($text)
//     {
//         $pdo = db();
//         $words = array_map('trim', explode(' ', $text));
//         $sqlParts = [];
//         $params = [];
//         foreach ($words as $i => $word) {
//             if ($word !== '') {
//                 $sqlParts[] = "(name LIKE :w{$i} OR Category LIKE :w{$i} OR description LIKE :w{$i} OR processed_text LIKE :w{$i})";
//                 $params["w{$i}"] = '%' . $word . '%';
//             }
//         }
//         $where = $sqlParts ? implode(' OR ', $sqlParts) : '0=1';
//         $sql = "SELECT 
//                     id, 
//                     name, 
//                     Category, 
//                     `Selling Price`, 
//                     `Product Url`, 
//                     description
//                 FROM data2
//                 WHERE $where
//                 LIMIT 10";
//         $stmt = $pdo->prepare($sql);
//         $stmt->execute($params);
//         return $stmt->fetchAll(PDO::FETCH_ASSOC);
//     }

//     // Рекомендации через FastAPI
//     public static function recommend($name)
//     {
//         $url = 'http://localhost:8000/recommend?name=' . urlencode($name);
//         $json = @file_get_contents($url);
//         if ($json === false) {
//             // fallback на обычный поиск если python сервис не работает
//             return self::search($name);
//         }
//         return json_decode($json, true);
//     }
// }


class ProductCatalog
{
    public static function search($text, $limit = 10)
    {
        $url = 'http://127.0.0.1:5000/search?q=' . urlencode($text) . '&n=' . $limit;
        $json = @file_get_contents($url);
        if ($json === false) return [];
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        return $arr;
    }

    public static function recommend($text, $limit = 5)
    {
        $url = 'http://127.0.0.1:5000/recommend?name=' . urlencode($text) . '&n=' . $limit;
        $json = @file_get_contents($url);
        if ($json === false) return [];
        $arr = json_decode($json, true);
        if (!is_array($arr)) return [];
        return $arr;
    }
}