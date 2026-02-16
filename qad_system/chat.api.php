<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function jOut(array $data, int $code = 200): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($data, JSON_UNESCAPED_SLASHES);
  exit;
}

function getJsonInput(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

/* =========================
   DB CONFIG (EDIT THIS)
   ========================= */
$dbHost = "localhost";
$dbName = "qad_system";
$dbUser = "root";
$dbPass = "";

/* =========================
   CHAT LIMITS
   ========================= */
const CHAT_MAX_LEN      = 2000;
const CHAT_FETCH_LIMIT  = 120;  // initial load
const CHAT_AFTER_LIMIT  = 200;  // incremental updates

try {
  $pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
    ]
  );
} catch (Exception $e) {
  jOut(['success'=>false,'message'=>'DB Connection Failed: '.$e->getMessage()], 500);
}

/* ✅ AUTO-CREATE messages TABLE (prevents "DB error" if table missing) */
try {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS `messages` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `from_id` INT NOT NULL,
      `to_id` INT NOT NULL,
      `body` TEXT NOT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `read_at` DATETIME NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_to_read` (`to_id`, `read_at`),
      KEY `idx_pair_id` (`from_id`, `to_id`, `id`),
      KEY `idx_to_from_id` (`to_id`, `from_id`, `id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  ");
} catch (Exception $e) {
  // If you see this, your DB user has no CREATE privilege
  jOut(['success'=>false,'message'=>'DB error (table create): '.$e->getMessage()], 500);
}

/* =========================
   AUTH
   ========================= */
if (!isset($_SESSION['user'])) {
  jOut(['success'=>false,'message'=>'Unauthorized'], 401);
}
$currentUser = $_SESSION['user'];
$me = (int)($currentUser['id'] ?? 0);
if ($me <= 0) jOut(['success'=>false,'message'=>'Invalid session user'], 401);

$action = $_GET['action'] ?? '';

/* =========================
   OPTIONAL: HEALTH CHECK
   ========================= */
if ($action === 'health') {
  try {
    $chk1 = $pdo->query("SELECT 1")->fetch();
    $chk2 = $pdo->query("SHOW TABLES LIKE 'messages'")->fetch();
    jOut([
      'success'=>true,
      'db'=>'ok',
      'messages_table'=> $chk2 ? 'present' : 'missing'
    ]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'Health failed: '.$e->getMessage()], 500);
  }
}

/* =========================
   USERS LIST (for new chats)
   ========================= */
if ($action === 'get_chat_users') {
  $q = trim((string)($_GET['q'] ?? ''));

  try {
    if ($q !== '') {
      $stmt = $pdo->prepare("
        SELECT id, name, role, sdo
        FROM users
        WHERE id <> ?
          AND (name LIKE ? OR role LIKE ? OR sdo LIKE ?)
        ORDER BY name ASC
        LIMIT 200
      ");
      $like = "%{$q}%";
      $stmt->execute([$me, $like, $like, $like]);
    } else {
      $stmt = $pdo->prepare("
        SELECT id, name, role, sdo
        FROM users
        WHERE id <> ?
        ORDER BY name ASC
        LIMIT 500
      ");
      $stmt->execute([$me]);
    }

    jOut(['success'=>true,'users'=>$stmt->fetchAll()]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

/* =========================
   CONVERSATIONS LIST
   ========================= */
if ($action === 'get_conversations') {
  try {
    $stmt = $pdo->prepare("
      SELECT
        t.peer_id,
        m.id AS last_id,
        m.body AS last_message,
        m.created_at AS updated_at,
        m.from_id AS last_from_id
      FROM (
        SELECT
          CASE WHEN from_id = :me THEN to_id ELSE from_id END AS peer_id,
          MAX(id) AS last_id
        FROM messages
        WHERE from_id = :me OR to_id = :me
        GROUP BY CASE WHEN from_id = :me THEN to_id ELSE from_id END
      ) t
      JOIN messages m ON m.id = t.last_id
      ORDER BY m.created_at DESC
    ");
    $stmt->execute([':me'=>$me]);
    $rows = $stmt->fetchAll();

    $uStmt = $pdo->prepare("
      SELECT from_id AS peer_id, COUNT(*) AS unread
      FROM messages
      WHERE to_id = ? AND read_at IS NULL
      GROUP BY from_id
    ");
    $uStmt->execute([$me]);
    $unreadMap = [];
    foreach ($uStmt->fetchAll() as $ur) {
      $unreadMap[(string)$ur['peer_id']] = (int)$ur['unread'];
    }

    $peerIds = array_values(array_unique(array_map(fn($r)=>(int)$r['peer_id'], $rows)));
    $peerInfo = [];
    if (count($peerIds) > 0) {
      $in = implode(',', array_fill(0, count($peerIds), '?'));
      $pStmt = $pdo->prepare("SELECT id, name, role, sdo FROM users WHERE id IN ($in)");
      $pStmt->execute($peerIds);
      foreach ($pStmt->fetchAll() as $p) $peerInfo[(string)$p['id']] = $p;
    }

    $items = [];
    foreach ($rows as $r) {
      $pid = (string)$r['peer_id'];
      if (!isset($peerInfo[$pid])) continue;
      $p = $peerInfo[$pid];

      $items[] = [
        'peer_id' => (int)$p['id'],
        'peer_name' => (string)$p['name'],
        'peer_role' => (string)$p['role'],
        'peer_sdo'  => (string)($p['sdo'] ?? 'Unassigned'),
        'last_message' => (string)($r['last_message'] ?? ''),
        'updated_at' => (string)($r['updated_at'] ?? ''),
        'last_from_me' => ((int)$r['last_from_id'] === $me),
        'unread' => (int)($unreadMap[$pid] ?? 0),
      ];
    }

    jOut(['success'=>true,'items'=>$items]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

/* =========================
   GET MESSAGES (supports after_id)
   ========================= */
if ($action === 'get_messages') {
  $peerId  = (int)($_GET['peer_id'] ?? 0);
  $afterId = (int)($_GET['after_id'] ?? 0);

  if ($peerId <= 0) jOut(['success'=>false,'message'=>'Missing peer_id'], 400);
  if ($peerId === $me) jOut(['success'=>false,'message'=>'Invalid peer'], 400);

  try {
    $pStmt = $pdo->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
    $pStmt->execute([$peerId]);
    if (!$pStmt->fetch()) jOut(['success'=>false,'message'=>'User not found'], 404);

    if ($afterId > 0) {
      $stmt = $pdo->prepare("
        SELECT id, from_id, to_id, body, created_at, read_at
        FROM messages
        WHERE ((from_id=? AND to_id=?) OR (from_id=? AND to_id=?))
          AND id > ?
        ORDER BY id ASC
        LIMIT " . CHAT_AFTER_LIMIT . "
      ");
      $stmt->execute([$me, $peerId, $peerId, $me, $afterId]);
      $msgs = $stmt->fetchAll();
    } else {
      $stmt = $pdo->prepare("
        SELECT id, from_id, to_id, body, created_at, read_at
        FROM messages
        WHERE (from_id=? AND to_id=?) OR (from_id=? AND to_id=?)
        ORDER BY id DESC
        LIMIT " . CHAT_FETCH_LIMIT . "
      ");
      $stmt->execute([$me, $peerId, $peerId, $me]);
      $msgs = array_reverse($stmt->fetchAll());
    }

    $maxId = 0;
    foreach ($msgs as $m) $maxId = max($maxId, (int)$m['id']);

    jOut(['success'=>true,'messages'=>$msgs,'max_id'=>$maxId]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

/* =========================
   SEND MESSAGE
   ========================= */
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $toId = (int)($in['to_id'] ?? 0);
  $body = trim((string)($in['body'] ?? ''));

  if ($toId <= 0) jOut(['success'=>false,'message'=>'Missing to_id'], 400);
  if ($toId === $me) jOut(['success'=>false,'message'=>'Cannot message yourself'], 400);
  if ($body === '') jOut(['success'=>false,'message'=>'Empty message'], 400);

  if (mb_strlen($body) > CHAT_MAX_LEN) $body = mb_substr($body, 0, CHAT_MAX_LEN);

  try {
    $pStmt = $pdo->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
    $pStmt->execute([$toId]);
    if (!$pStmt->fetch()) jOut(['success'=>false,'message'=>'Recipient not found'], 404);

    $stmt = $pdo->prepare("
      INSERT INTO messages (from_id, to_id, body, created_at)
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$me, $toId, $body]);

    $id = (int)$pdo->lastInsertId();
    $mStmt = $pdo->prepare("SELECT id, from_id, to_id, body, created_at, read_at FROM messages WHERE id=? LIMIT 1");
    $mStmt->execute([$id]);
    $msg = $mStmt->fetch();

    jOut(['success'=>true,'message_row'=>$msg]);
  } catch (Exception $e) {
    error_log("CHAT SEND DB ERROR: " . $e->getMessage());
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

/* =========================
   MARK READ
   ========================= */
if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $peerId = (int)($in['peer_id'] ?? 0);
  if ($peerId <= 0) jOut(['success'=>false,'message'=>'Missing peer_id'], 400);

  try {
    $stmt = $pdo->prepare("
      UPDATE messages
      SET read_at = NOW()
      WHERE to_id = ? AND from_id = ? AND read_at IS NULL
    ");
    $stmt->execute([$me, $peerId]);
    jOut(['success'=>true]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

/* =========================
   TOTAL UNREAD
   ========================= */
if ($action === 'get_unread_messages_count') {
  try {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM messages WHERE to_id=? AND read_at IS NULL");
    $stmt->execute([$me]);
    $row = $stmt->fetch();
    jOut(['success'=>true,'count'=>(int)($row['c'] ?? 0)]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error: '.$e->getMessage()], 500);
  }
}

jOut(['success'=>false,'message'=>'Invalid action'], 404);
