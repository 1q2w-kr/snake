<?php
/**
 * Snake Game API
 *
 * Actions:
 * - submit (POST): Save a finished run
 * - leaderboard (GET): Top scores
 * - history (GET): Recent runs for logged-in user
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$host = parse_url($origin, PHP_URL_HOST);
$currentHost = $_SERVER['HTTP_HOST'] ?? null;
$allowedHosts = array_filter(['1q2w.kr', 'www.1q2w.kr', 'localhost', '127.0.0.1', $currentHost]);
if ($origin && $host && !in_array($host, $allowedHosts, true)) {
    jsonError('invalid_origin', 'Request origin not allowed', 403);
}

// Auth bridge
$authBridgePaths = [
    '/www/fun/common/rhymix_bridge.php',
    __DIR__ . '/../../common/rhymix_bridge.php',
];
foreach ($authBridgePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

$guardPaths = [
    '/www/fun/common/service/guard.php',
    __DIR__ . '/../../common/service/guard.php',
];
foreach ($guardPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
if (function_exists('fun_service_require_enabled')) {
    fun_service_require_enabled('snake');
}

$sessionUser = function_exists('rhxCurrentUser') ? rhxCurrentUser() : ['loggedIn' => false];
$isLoggedIn = !empty($sessionUser['loggedIn']);
$memberSrl = $sessionUser['memberSrl'] ?? null;

// DB
$FUN_DB_ENV_PREFIX = 'SNAKE';
$dbConfigPaths = [
    '/www/fun/common/db.php',
    __DIR__ . '/../../common/db.php',
];
foreach ($dbConfigPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
if (!isset($conn)) {
    jsonError('database_error', 'Database connection failed', 500);
}

if (!ensureDatabaseSchema($conn)) {
    jsonError('db_init_failed', 'Database schema initialization failed', 500);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input') ?: 'null', true);
        if (!is_array($payload) || empty($payload['action'])) {
            throw new RuntimeException('INVALID_PAYLOAD');
        }
        $action = $payload['action'];
        switch ($action) {
            case 'submit':
                handleSubmit($conn, $payload, $memberSrl, $isLoggedIn);
                break;
            default:
                throw new RuntimeException('UNKNOWN_ACTION');
        }
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'leaderboard':
                handleLeaderboard($conn, $_GET);
                break;
            case 'history':
                handleHistory($conn, $memberSrl, $_GET, $isLoggedIn);
                break;
            default:
                throw new RuntimeException('UNKNOWN_ACTION');
        }
    } else {
        throw new RuntimeException('METHOD_NOT_ALLOWED');
    }
} catch (RuntimeException $e) {
    jsonError($e->getMessage(), 'Request failed', 400);
} catch (Throwable $e) {
    error_log('snake API error: ' . $e->getMessage());
    jsonError('INTERNAL_ERROR', 'An error occurred', 500);
}

function handleSubmit($conn, $payload, $memberSrl, $isLoggedIn) {
    $sessionToken = $payload['sessionToken'] ?? '';
    $score = (int)($payload['score'] ?? 0);
    $length = (int)($payload['length'] ?? 0);
    $durationMs = (int)($payload['durationMs'] ?? 0);
    $maxSpeedFps = (float)($payload['maxSpeedFps'] ?? 0);

    if (strlen($sessionToken) !== 36) {
        throw new RuntimeException('INVALID_SESSION_TOKEN');
    }
    if ($score <= 0 || $length < 3) {
        throw new RuntimeException('INVALID_SCORE');
    }
    if ($durationMs < 500) {
        throw new RuntimeException('DURATION_TOO_SHORT');
    }
    if ($maxSpeedFps <= 0) {
        throw new RuntimeException('INVALID_SPEED');
    }

    $identityHash = hash('sha256', $memberSrl ? ('member_' . $memberSrl) : ('guest_' . ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '')));

    $stmt = $conn->prepare("SELECT score_id FROM snake_scores WHERE session_token = ?");
    $stmt->bind_param('s', $sessionToken);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        throw new RuntimeException('DUPLICATE_SUBMISSION');
    }

    $stmt = $conn->prepare(
        "INSERT INTO snake_scores
        (member_srl, identity_hash, session_token, score, length, max_speed_fps, duration_ms, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param('issiiid', $memberSrl, $identityHash, $sessionToken, $score, $length, $maxSpeedFps, $durationMs);
    if (!$stmt->execute()) {
        throw new RuntimeException('INSERT_FAILED');
    }

    // rank (logged-in only; guests excluded from rank)
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS rank FROM snake_scores
         WHERE member_srl IS NOT NULL
         AND (score > ? OR (score = ? AND length > ?) OR (score = ? AND length = ? AND duration_ms < ?))"
    );
    $stmt->bind_param('iiiiii', $score, $score, $length, $score, $length, $durationMs);
    $stmt->execute();
    $rankData = $stmt->get_result()->fetch_assoc();
    $rank = (int)$rankData['rank'] + 1;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM snake_scores WHERE member_srl IS NOT NULL");
    $stmt->execute();
    $totalData = $stmt->get_result()->fetch_assoc();
    $totalEntries = (int)$totalData['total'];

    $isPersonalBest = false;
    if ($memberSrl) {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) as count FROM snake_scores
             WHERE member_srl = ?
             AND (score > ? OR (score = ? AND length > ?) OR (score = ? AND length = ? AND duration_ms < ?))"
        );
        $stmt->bind_param('iiiiiii', $memberSrl, $score, $score, $length, $score, $length, $durationMs);
        $stmt->execute();
        $pbData = $stmt->get_result()->fetch_assoc();
        $isPersonalBest = (int)$pbData['count'] === 0;
    }

    jsonSuccess([
        'rank' => $memberSrl ? $rank : null,
        'totalEntries' => $memberSrl ? $totalEntries : null,
        'isPersonalBest' => $isPersonalBest,
    ]);
}

function handleLeaderboard($conn, $params) {
    $limit = min(100, max(1, (int)($params['limit'] ?? 50)));
    $sql = "SELECT s.score, s.length, s.max_speed_fps, s.duration_ms, s.created_at,
                   COALESCE(m.nick_name, '익명') AS nickname
            FROM snake_scores s
            LEFT JOIN rhymix_member m ON s.member_srl = m.member_srl
            ORDER BY s.score DESC, s.length DESC, s.duration_ms ASC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    $scores = [];
    $rank = 1;
    while ($row = $res->fetch_assoc()) {
        $scores[] = [
            'rank' => $rank++,
            'nickname' => $row['nickname'],
            'score' => (int)$row['score'],
            'length' => (int)$row['length'],
            'speed' => formatFps((float)$row['max_speed_fps']),
            'duration' => formatTime((int)$row['duration_ms']),
            'createdAt' => $row['created_at'],
        ];
    }

    jsonSuccess(['scores' => $scores]);
}

function handleHistory($conn, $memberSrl, $params, $isLoggedIn) {
    if (!$isLoggedIn || !$memberSrl) {
        jsonSuccess(['scores' => []]);
    }

    $limit = min(100, max(1, (int)($params['limit'] ?? 10)));
    $sql = "SELECT score, length, max_speed_fps, duration_ms, created_at
            FROM snake_scores
            WHERE member_srl = ?
            ORDER BY created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $memberSrl, $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    $scores = [];
    while ($row = $res->fetch_assoc()) {
        $scores[] = [
            'score' => (int)$row['score'],
            'length' => (int)$row['length'],
            'speed' => formatFps((float)$row['max_speed_fps']),
            'duration' => formatTime((int)$row['duration_ms']),
            'createdAt' => $row['created_at'],
        ];
    }

    jsonSuccess(['scores' => $scores]);
}

function formatFps($fps) {
    return rtrim(rtrim(number_format($fps, 1, '.', ''), '0'), '.');
}

function formatTime($totalMs) {
    $min = floor($totalMs / 60000);
    $sec = floor(($totalMs % 60000) / 1000);
    $ms = floor(($totalMs % 1000) / 10);
    return sprintf('%d:%02d.%02d', $min, $sec, $ms);
}

function jsonSuccess($data) {
    echo json_encode(array_merge(['ok' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($error, $message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'ok' => false,
        'error' => $error,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureDatabaseSchema($conn) {
    static $checked = false;
    if ($checked) return true;
    $checked = true;

    try {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
        );
        if (!$stmt) {
            error_log('snake: schema check prepare failed - ' . $conn->error);
            return false;
        }
        $table = 'snake_scores';
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row && (int) $row['cnt'] > 0) {
            return true;
        }

        $migrationSql = null;
        $migrationPaths = [
            '/www/fun/snake/dbinit/0001_init.sql',
            __DIR__ . '/../dbinit/0001_init.sql',
        ];
        foreach ($migrationPaths as $path) {
            if (file_exists($path)) {
                $migrationSql = file_get_contents($path);
                break;
            }
        }
        if ($migrationSql === false || $migrationSql === null) {
            error_log('snake: migration file not found');
            return false;
        }

        if ($conn->multi_query($migrationSql)) {
            do {
                if ($res = $conn->store_result()) {
                    $res->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            error_log('snake: migration failed - ' . $conn->error);
            return false;
        }
    } catch (Throwable $e) {
        error_log('snake: schema check failed - ' . $e->getMessage());
        return false;
    }
    return true;
}
