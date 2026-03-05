<?php
header('Content-Type: application/json; charset=utf-8');

function response($data, int $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'blushe_db';
$user = getenv('DB_USER') ?: 'blushe_user';
$pass = getenv('DB_PASS') ?: 'StrongPass_123!';

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (str_contains($path, '/health')) {
        response([
            "status" => "ok",
            "service" => "blushe-api",
            "database" => "connected"
        ]);
    }

    if (str_contains($path, '/products')) {

        $q     = trim($_GET['q'] ?? '');
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $baseSql = "FROM products";
        $params  = [];

        if ($q !== '') {
            $baseSql .= " WHERE name LIKE :q OR description LIKE :q";
            $params['q'] = "%$q%";
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) $baseSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT id, name, price, description, ingredients, skin_type, concerns, created_at
                $baseSql
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response([
            "status" => "success",
            "meta" => [
                "total" => $total,
                "page" => $page,
                "limit" => $limit,
                "query" => $q
            ],
            "data" => $rows
        ]);
    }


    // --- AI Assistant (rule-based) ---
    if (str_contains($path, '/assist') && $_SERVER['REQUEST_METHOD'] === 'POST') {

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        $text = trim((string)($body['message'] ?? ''));

        if ($text === '') {
            response(["status"=>"error","message"=>"Empty message"], 400);
        }

        $t = mb_strtolower($text);

        // 1) Извлекаем skin_type
        $skin = null;
        if (preg_match('/\b(сух|dry)\w*/u', $t)) $skin = 'dry';
        elseif (preg_match('/\b(жир|oily)\w*/u', $t)) $skin = 'oily';
        elseif (preg_match('/\b(комб|combination)\w*/u', $t)) $skin = 'combination';
        elseif (preg_match('/\b(чувств|sensitive)\w*/u', $t)) $skin = 'sensitive';
        else $skin = 'all';

        // 2) Извлекаем concerns
        $concerns = [];
        $map = [
            'acne' => ['акне','прыщ','воспален','acne'],
            'pores' => ['пор','pores'],
            'oil' => ['жирн','себум','oil'],
            'dehydration' => ['обезвож','сухост','dehydrat'],
            'redness' => ['покрасн','redness'],
            'barrier' => ['барьер','восстанов','barrier'],
            'sun' => ['солнц','spf','sun'],
            'lips' => ['губ','lips'],
        ];

        foreach ($map as $key => $words) {
            foreach ($words as $w) {
                if (mb_strpos($t, $w) !== false) { $concerns[] = $key; break; }
            }
        }

        // fallback
        if (!$concerns) $concerns = ['dehydration'];

        // 3) Запрос к БД (подбор)
        // Идея: skin_type должен совпадать либо быть 'all'
        // concerns: хотя бы одно совпадение
        $where = [];
        $params = [];

        $where[] = "(skin_type LIKE :skin OR skin_type LIKE '%all%')";
        $params['skin'] = "%$skin%";

        $cParts = [];
        foreach ($concerns as $i => $c) {
            $k = "c$i";
            $cParts[] = "concerns LIKE :$k";
            $params[$k] = "%$c%";
        }
        $where[] = "(" . implode(" OR ", $cParts) . ")";

        $sql = "SELECT id,name,price,description,ingredients,skin_type,concerns
                FROM products
                WHERE " . implode(" AND ", $where) . "
                ORDER BY id DESC
                LIMIT 5";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $answer = "Я подобрала варианты под skin_type={$skin} и запрос: " . implode(", ", $concerns) . ".";
        if (!$items) {
            $answer .= " В базе нет точных совпадений — попробуй уточнить проблему (например: 'поры', 'покраснения', 'SPF').";
        }

        response([
            "status" => "ok",
            "assistant" => [
                "reply" => $answer,
                "skin" => $skin,
                "concerns" => $concerns
            ],
            "recommendations" => $items
        ]);
    }

    response([
        "status" => "error",
        "message" => "Endpoint not found"
    ], 404);

} catch (Exception $e) {
    response([
        "status" => "error",
        "message" => $e->getMessage()
    ], 500);
}
