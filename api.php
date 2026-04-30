<?php
/**
 * 心情便签 API
 *
 * GET    /api.php          获取所有便签
 * POST   /api.php          新建便签
 * PUT    /api.php?id=xxx   更新便签
 * DELETE /api.php?id=xxx   删除便签
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

/* ── 数据库连接 ── */
$config = require __DIR__ . '/config.php';

try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset={$config['db_charset']}";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库连接失败']);
    exit;
}

/* ── CSRF 保护（不依赖 session，适配 Cloudflare 环境） ── */
define('CSRF_SECRET', 'mood_notes_csrf_2026');

function generateCsrfToken(): string {
    $nonce = bin2hex(random_bytes(16));
    $ts    = (string) time();
    $sig   = hash_hmac('sha256', $nonce . '|' . $ts, CSRF_SECRET);
    return $nonce . '|' . $ts . '|' . $sig;
}

function validateCsrfToken(string $token): bool {
    $parts = explode('|', $token);
    if (count($parts) !== 3) return false;
    [$nonce, $ts, $sig] = $parts;
    if (!ctype_digit($ts)) return false;
    // 有效期 2 小时
    if (time() - (int)$ts > 7200) return false;
    $expected = hash_hmac('sha256', $nonce . '|' . $ts, CSRF_SECRET);
    return hash_equals($expected, $sig);
}

function getCsrfToken(array $input = []): string {
    // 优先从 X-Api-Key header 获取，其次从 JSON body 获取
    $token = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($token === '' && !empty($input)) {
        $token = $input['csrf_token'] ?? '';
    }
    return $token;
}

/* ── 输入验证 ── */
const ALLOWED_COLORS = ['#e06850', '#e8963e', '#d4a84e', '#4a90d9', '#a86cc4', '#e05a8a', '#3ab0a0'];
const MAX_CONTENT_LENGTH = 10000;
const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

function validateId(string $id): bool {
    return preg_match(UUID_PATTERN, $id) === 1;
}

function validateColor(string $color): bool {
    return in_array($color, ALLOWED_COLORS, true);
}

function readJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── 路由处理 ── */
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    /* ── GET: 返回所有便签 + CSRF token ── */
    case 'GET':
        $token = generateCsrfToken();
        try {
            $stmt = $pdo->query('SELECT id, content, color, created_at, updated_at FROM notes ORDER BY created_at DESC');
            $notes = $stmt->fetchAll();
            jsonResponse(['csrf_token' => $token, 'notes' => $notes]);
        } catch (PDOException $e) {
            jsonResponse(['error' => '查询失败'], 500);
        }

    /* ── POST: 新建便签 ── */
    case 'POST':
        $data = readJsonInput();
        $csrf = getCsrfToken($data);
        if (!validateCsrfToken($csrf)) {
            jsonResponse(['error' => 'CSRF token 无效'], 403);
        }
        $content = trim($data['content'] ?? '');
        $color   = trim($data['color']   ?? '');

        if ($content === '') {
            jsonResponse(['error' => '内容不能为空'], 400);
        }
        if (mb_strlen($content) > MAX_CONTENT_LENGTH) {
            jsonResponse(['error' => '内容过长'], 400);
        }
        if (!validateColor($color)) {
            jsonResponse(['error' => '无效的颜色'], 400);
        }

        $id = bin2hex(random_bytes(16));
        $id = sprintf('%s-%s-%s-%s-%s',
            substr($id, 0, 8), substr($id, 8, 4),
            substr($id, 12, 4), substr($id, 16, 4),
            substr($id, 20, 12)
        );

        try {
            $stmt = $pdo->prepare('INSERT INTO notes (id, content, color) VALUES (?, ?, ?)');
            $stmt->execute([$id, $content, $color]);

            $stmt = $pdo->prepare('SELECT id, content, color, created_at, updated_at FROM notes WHERE id = ?');
            $stmt->execute([$id]);
            jsonResponse(['note' => $stmt->fetch()], 201);
        } catch (PDOException $e) {
            jsonResponse(['error' => '创建失败'], 500);
        }

    /* ── PUT: 更新便签 ── */
    case 'PUT':
        $data = readJsonInput();
        $csrf = getCsrfToken($data);
        if (!validateCsrfToken($csrf)) {
            jsonResponse(['error' => 'CSRF token 无效'], 403);
        }

        $id = trim($_GET['id'] ?? '');
        if (!validateId($id)) {
            jsonResponse(['error' => '无效的 ID'], 400);
        }
        $content = trim($data['content'] ?? '');
        $color   = trim($data['color']   ?? '');

        if ($content === '') {
            jsonResponse(['error' => '内容不能为空'], 400);
        }
        if (mb_strlen($content) > MAX_CONTENT_LENGTH) {
            jsonResponse(['error' => '内容过长'], 400);
        }
        if (!validateColor($color)) {
            jsonResponse(['error' => '无效的颜色'], 400);
        }

        try {
            $stmt = $pdo->prepare('UPDATE notes SET content = ?, color = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$content, $color, $id]);

            if ($stmt->rowCount() === 0) {
                jsonResponse(['error' => '便签不存在'], 404);
            }

            $stmt = $pdo->prepare('SELECT id, content, color, created_at, updated_at FROM notes WHERE id = ?');
            $stmt->execute([$id]);
            jsonResponse(['note' => $stmt->fetch()]);
        } catch (PDOException $e) {
            jsonResponse(['error' => '更新失败'], 500);
        }

    /* ── DELETE: 删除便签 ── */
    case 'DELETE':
        $data = readJsonInput();
        $csrf = getCsrfToken($data);
        if (!validateCsrfToken($csrf)) {
            jsonResponse(['error' => 'CSRF token 无效'], 403);
        }

        $id = trim($_GET['id'] ?? '');
        if (!validateId($id)) {
            jsonResponse(['error' => '无效的 ID'], 400);
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                jsonResponse(['error' => '便签不存在'], 404);
            }
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => '删除失败'], 500);
        }

    default:
        jsonResponse(['error' => '不支持的请求方法'], 405);
}
