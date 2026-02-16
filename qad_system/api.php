<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

function jOut(array $data, int $code = 200): void {
  http_response_code($code);
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode($data);
  exit;
}

function getJsonInput(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

/* ✅ AUDIT LOGGER */
function auditLog(PDO $pdo, int $actorId, string $action, string $entityType, ?int $entityId = null, array $meta = []): void {
  try {
    $stmt = $pdo->prepare("
      INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, meta, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
      $actorId,
      $action,
      $entityType,
      $entityId,
      !empty($meta) ? json_encode($meta, JSON_UNESCAPED_SLASHES) : null
    ]);
  } catch (Exception $e) {
    // never break main flow
  }
}

/* =========================
   DB CONFIG (EDIT THIS)
   ========================= */
$dbHost = "localhost";
$dbName = "qad_system";
$dbUser = "root";
$dbPass = "";

/* =========================
   CUSTOM ADMIN CODES
   ========================= */
const CODE_ADMIN_AIDE = 'DEPED-Aide08';
const CODE_ADMIN      = 'DEPED-Admin08';
const CODE_RD         = 'DEPED-RD08';

/* Chat limits */
const CHAT_MAX_LEN = 2000;
const CHAT_FETCH_LIMIT = 300;

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
  jOut(['success'=>false,'message'=>'DB Connection Failed'], 500);
}

$action = $_GET['action'] ?? '';

/* ------------------- AUTH ------------------- */

// LOGIN
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $email = strtolower(trim((string)($in['email'] ?? '')));
  $password = (string)($in['password'] ?? '');

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if ($u && password_verify($password, $u['password'])) {
    $_SESSION['user'] = [
      'id'   => (int)$u['id'],
      'name' => $u['name'],
      'role' => $u['role'],
      'sdo'  => $u['sdo'] ?? 'Unassigned'
    ];
    jOut(['success'=>true,'role'=>$u['role']]);
  }

  jOut(['success'=>false,'message'=>'Invalid credentials'], 401);
}

// REGISTER
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();

  $name = trim((string)($in['fullname'] ?? ''));
  $email = strtolower(trim((string)($in['email'] ?? '')));
  $pass = (string)($in['password'] ?? '');
  $role = (string)($in['role'] ?? 'school');
  $sdo  = (string)($in['sdo'] ?? 'Unassigned');
  $code = (string)($in['admin_code'] ?? '');

  if ($name === '' || $email === '' || strlen($pass) < 6) {
    jOut(['success'=>false,'message'=>'Invalid input (min password 6)'], 400);
  }

  $allowedRoles = ['school','sdo','admin_aide','admin','rd'];
  if (!in_array($role, $allowedRoles, true)) {
    jOut(['success'=>false,'message'=>'Invalid role'], 400);
  }

  if ($role === 'admin_aide' && $code !== CODE_ADMIN_AIDE) {
    jOut(['success'=>false,'message'=>'Invalid Admin Aide Code'], 403);
  }
  if ($role === 'admin' && $code !== CODE_ADMIN) {
    jOut(['success'=>false,'message'=>'Invalid Admin Code'], 403);
  }
  if ($role === 'rd' && $code !== CODE_RD) {
    jOut(['success'=>false,'message'=>'Invalid RD Code'], 403);
  }

  try {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,sdo) VALUES (?,?,?,?,?)");
    $stmt->execute([
      $name,
      $email,
      password_hash($pass, PASSWORD_DEFAULT),
      $role,
      $sdo
    ]);
    jOut(['success'=>true]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'Email likely exists'], 409);
  }
}

// LOGOUT
if ($action === 'logout') {
  $_SESSION = [];

  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }

  session_destroy();
  jOut(['success'=>true]);
}

// PROTECT ROUTES
if (!isset($_SESSION['user'])) {
  jOut(['success'=>false,'message'=>'Unauthorized'], 401);
}

$currentUser = $_SESSION['user'];

/* =========================
   ✅ PERMISSION HELPER
   ========================= */
function canViewReport(array $currentUser, array $rep): bool {
  $role = $currentUser['role'];

  if (in_array($role, ['admin','admin_aide','rd'], true)) return true;
  if ((int)($rep['assigned_to'] ?? 0) === (int)$currentUser['id']) return true;
  if ($role === 'sdo' && ($rep['sdo'] ?? '') === ($currentUser['sdo'] ?? '')) return true;
  if ((int)($rep['user_id'] ?? 0) === (int)$currentUser['id']) return true;

  return false;
}

/* ------------------- DASHBOARD DATA ------------------- */

if ($action === 'get_data') {
  if (in_array($currentUser['role'], ['admin','admin_aide','rd'], true)) {
    $reports = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC")->fetchAll();
  } else {
    $stmt = $pdo->prepare("
      SELECT * FROM reports
      WHERE sdo = ?
         OR user_id = ?
         OR assigned_to = ?
      ORDER BY created_at DESC
    ");
    $stmt->execute([$currentUser['sdo'], $currentUser['id'], $currentUser['id']]);
    $reports = $stmt->fetchAll();
  }

  try {
    $schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll();
  } catch (Exception $e) {
    $schools = [];
  }

  jOut(['success'=>true,'user'=>$currentUser,'reports'=>$reports,'schools'=>$schools]);
}

/* ------------------- NOTIFICATIONS ------------------- */

if ($action === 'get_notifications') {
  $role = $currentUser['role'];
  $sdo  = $currentUser['sdo'];

  if (in_array($role, ['admin','admin_aide'], true)) {
    $stmt = $pdo->query("
      SELECT id,title,type,sdo,status,created_at,updated_at,filename
      FROM reports
      ORDER BY updated_at DESC
      LIMIT 30
    ");
    jOut(['success'=>true,'items'=>$stmt->fetchAll()]);
  }

  if ($role === 'rd') {
    $stmt = $pdo->query("
      SELECT id,title,type,sdo,status,created_at,updated_at,filename
      FROM reports
      WHERE status IN ('forwarded')
      ORDER BY updated_at DESC
      LIMIT 30
    ");
    jOut(['success'=>true,'items'=>$stmt->fetchAll()]);
  }

  if ($role === 'sdo') {
    $stmt = $pdo->prepare("
      SELECT id,title,type,sdo,status,created_at,updated_at,filename
      FROM reports
      WHERE sdo = ?
      ORDER BY updated_at DESC
      LIMIT 30
    ");
    $stmt->execute([$sdo]);
    jOut(['success'=>true,'items'=>$stmt->fetchAll()]);
  }

  if ($role === 'school') {
    $stmt = $pdo->prepare("
      SELECT id,title,type,sdo,status,created_at,updated_at,filename
      FROM reports
      WHERE user_id = ?
      ORDER BY updated_at DESC
      LIMIT 30
    ");
    $stmt->execute([(int)$currentUser['id']]);
    jOut(['success'=>true,'items'=>$stmt->fetchAll()]);
  }

  jOut(['success'=>true,'items'=>[]]);
}

/* ------------------- USERS ------------------- */

if ($action === 'get_users') {
  if (!in_array($currentUser['role'], ['admin','admin_aide','rd'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }
  $users = $pdo->query("SELECT id,name,email,role,sdo FROM users ORDER BY id DESC")->fetchAll();
  jOut(['success'=>true,'users'=>$users]);
}

if ($action === 'create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!in_array($currentUser['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $name = trim((string)($in['fullname'] ?? ''));
  $email = strtolower(trim((string)($in['email'] ?? '')));
  $pass = (string)($in['password'] ?? '');
  $role = (string)($in['role'] ?? 'school');
  $sdo  = (string)($in['sdo'] ?? 'Unassigned');

  if ($name === '' || $email === '' || $pass === '') {
    jOut(['success'=>false,'message'=>'Missing fields'], 400);
  }

  try {
    $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role,sdo) VALUES (?,?,?,?,?)");
    $stmt->execute([$name,$email,password_hash($pass, PASSWORD_DEFAULT),$role,$sdo]);
    auditLog($pdo, (int)$currentUser['id'], 'CREATE_USER', 'user', null, [
      'email' => $email,
      'role'  => $role,
      'sdo'   => $sdo
    ]);
    jOut(['success'=>true]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'Email likely exists'], 409);
  }
}

/* ------------------- PROFILE ------------------- */

if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $name = trim((string)($in['name'] ?? ''));
  if ($name === '') jOut(['success'=>false,'message'=>'Name required'], 400);

  $stmt = $pdo->prepare("UPDATE users SET name=? WHERE id=?");
  $stmt->execute([$name, $currentUser['id']]);

  $_SESSION['user']['name'] = $name;
  auditLog($pdo, (int)$currentUser['id'], 'UPDATE_PROFILE', 'user', (int)$currentUser['id'], ['name'=>$name]);
  jOut(['success'=>true]);
}

if ($action === 'update_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $pass = (string)($in['password'] ?? '');
  if (strlen($pass) < 6) jOut(['success'=>false,'message'=>'Min 6 chars'], 400);

  $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
  $stmt->execute([password_hash($pass, PASSWORD_DEFAULT), $currentUser['id']]);

  auditLog($pdo, (int)$currentUser['id'], 'UPDATE_PASSWORD', 'user', (int)$currentUser['id']);
  jOut(['success'=>true]);
}

/* ------------------- STATUS UPDATE ------------------- */

if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!in_array($currentUser['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $id = (int)($in['id'] ?? 0);
  $status = (string)($in['status'] ?? '');

  if ($id <= 0 || $status === '') jOut(['success'=>false,'message'=>'Missing id/status'], 400);

  $allowed = ['pending','received','forwarded','processing','compliance','inspection','approved','disapproved'];
  if (!in_array($status, $allowed, true)) jOut(['success'=>false,'message'=>'Invalid status'], 400);

  $oldStmt = $pdo->prepare("SELECT status FROM reports WHERE id=? LIMIT 1");
  $oldStmt->execute([$id]);
  $old = $oldStmt->fetch();
  if (!$old) jOut(['success'=>false,'message'=>'Report not found'], 404);

  $stmt = $pdo->prepare("UPDATE reports SET status=?, updated_at=NOW() WHERE id=?");
  $stmt->execute([$status, $id]);

  auditLog($pdo, (int)$currentUser['id'], 'UPDATE_STATUS', 'report', $id, [
    'from' => $old['status'] ?? null,
    'to'   => $status
  ]);

  jOut(['success'=>true]);
}

/* ------------------- DELETE REPORT ------------------- */

if ($action === 'delete_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!in_array($currentUser['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  if ($reportId <= 0) jOut(['success'=>false,'message'=>'Missing report_id'], 400);

  $stmt = $pdo->prepare("SELECT id, filename FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();

  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  $filename = (string)($rep['filename'] ?? '');

  try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM checklists WHERE report_id=?")->execute([$reportId]);

    if ($filename !== '' && strpos($filename, 'EVAL:') === 0) {
      $evalId = (int)substr($filename, 5);
      if ($evalId > 0) {
        $pdo->prepare("DELETE FROM evaluations WHERE id=?")->execute([$evalId]);
      }
    }

    auditLog($pdo, (int)$currentUser['id'], 'DELETE_REPORT', 'report', $reportId, [
      'filename' => $filename
    ]);

    $pdo->prepare("DELETE FROM reports WHERE id=?")->execute([$reportId]);

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    jOut(['success'=>false,'message'=>'Delete failed'], 500);
  }

  if ($filename !== '' && strpos($filename, 'EVAL:') !== 0) {
    $safe = basename($filename);
    $path = __DIR__ . '/uploads/' . $safe;
    if (is_file($path)) @unlink($path);
  }

  jOut(['success'=>true]);
}

/* ------------------- UPLOAD REPORT ------------------- */

if ($action === 'upload_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jOut(['success'=>false,'message'=>'File upload error'], 400);
  }

  $file = $_FILES['file'];
  $title = (string)($_POST['title'] ?? 'Untitled Report');
  $type  = (string)($_POST['type'] ?? 'General Report');

  $uploadDir = __DIR__ . '/uploads/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $cleanName = uniqid('DOC_') . '_' . time() . '.' . $ext;
  $targetPath = $uploadDir . $cleanName;

  if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jOut(['success'=>false,'message'=>'Failed to save file'], 500);
  }

  $stmt = $pdo->prepare("
    INSERT INTO reports (user_id, owner_name, sdo, title, type, filename, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
  ");
  $stmt->execute([
    $currentUser['id'],
    $currentUser['name'],
    $currentUser['sdo'],
    $title,
    $type,
    $cleanName
  ]);

  $newId = (int)$pdo->lastInsertId();
  auditLog($pdo, (int)$currentUser['id'], 'UPLOAD_REPORT', 'report', $newId, [
    'title' => $title,
    'type'  => $type
  ]);

  jOut(['success'=>true]);
}

/* ------------------- CREATE EVALUATION (SDO) ------------------- */

if ($action === 'create_evaluation' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($currentUser['role'] !== 'sdo') jOut(['success'=>false,'message'=>'Unauthorized'], 403);

  $in = getJsonInput();

  $applicationType = trim((string)($in['application_type'] ?? ''));
  $allowedTypes = ['Establishment','Merging','Conversion','Separation'];

  if ($applicationType === '' || !in_array($applicationType, $allowedTypes, true)) {
    jOut(['success'=>false,'message'=>'Invalid Type of Application'], 400);
  }

  try {
    $pdo->beginTransaction();

    $stmtSchool = $pdo->prepare("
      INSERT INTO schools (school_id, name, address, level, division, contact_number, email)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtSchool->execute([
      $in['school_id'] ?? 'N/A',
      $in['school_name'] ?? '',
      $in['address'] ?? '',
      $in['program'] ?? '',
      $currentUser['sdo'],
      $in['contact'] ?? '',
      $in['email'] ?? ''
    ]);

    $stmtEval = $pdo->prepare("
      INSERT INTO evaluations (
        school_id_ref, sdo_evaluator, application_type,
        q1_gaps, q2_access, q3_support, q4_site, q5_facilities,
        q6_enrollment, q7_personnel, q8_resources, q9_sustainability,
        recommendations, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmtEval->execute([
      $in['school_id'] ?? 'N/A',
      $currentUser['name'],
      $applicationType,
      $in['q1'] ?? '', $in['q2'] ?? '', $in['q3'] ?? '', $in['q4'] ?? '', $in['q5'] ?? '',
      $in['q6'] ?? '', $in['q7'] ?? '', $in['q8'] ?? '', $in['q9'] ?? '',
      $in['recommendations'] ?? ''
    ]);

    $evalId = (int)$pdo->lastInsertId();
    $fileCode = "EVAL:" . $evalId;

    $stmtReport = $pdo->prepare("
      INSERT INTO reports (user_id, owner_name, sdo, title, type, filename, status, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmtReport->execute([
      $currentUser['id'],
      $currentUser['name'],
      $currentUser['sdo'],
      "New App: " . ($in['school_name'] ?? ''),
      "School Application (" . $applicationType . ")",
      $fileCode
    ]);

    $repId = (int)$pdo->lastInsertId();
    auditLog($pdo, (int)$currentUser['id'], 'CREATE_EVALUATION', 'report', $repId, [
      'eval_id' => $evalId,
      'application_type' => $applicationType
    ]);

    $pdo->commit();
    jOut(['success'=>true]);
  } catch (Exception $e) {
    $pdo->rollBack();
    jOut(['success'=>false,'message'=>$e->getMessage()], 500);
  }
}

/* ------------------- ASSIGN REPORT ------------------- */

if ($action === 'assign_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!in_array($currentUser['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $personnelId = (int)($in['personnel_id'] ?? 0);

  if ($reportId <= 0 || $personnelId <= 0) {
    jOut(['success'=>false,'message'=>'Missing report_id/personnel_id'], 400);
  }

  $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id=? LIMIT 1");
  $stmt->execute([$personnelId]);
  $u = $stmt->fetch();

  if (!$u) jOut(['success'=>false,'message'=>'Personnel not found'], 404);
  if (!in_array($u['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Invalid personnel role'], 400);
  }

  $oldStmt = $pdo->prepare("SELECT assigned_to, status FROM reports WHERE id=? LIMIT 1");
  $oldStmt->execute([$reportId]);
  $old = $oldStmt->fetch();
  if (!$old) jOut(['success'=>false,'message'=>'Report not found'], 404);

  $stmt = $pdo->prepare("
    UPDATE reports
    SET assigned_to=?, status='processing', updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([$personnelId, $reportId]);

  auditLog($pdo, (int)$currentUser['id'], 'ASSIGN_REPORT', 'report', $reportId, [
    'old_assigned_to' => $old['assigned_to'] ?? null,
    'new_assigned_to' => $personnelId,
    'old_status'      => $old['status'] ?? null,
    'new_status'      => 'processing'
  ]);

  jOut(['success'=>true]);
}

/* ------------------- FORWARD TO RD ------------------- */

if ($action === 'forward_to_rd' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($currentUser['role'] !== 'admin') {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  if ($reportId <= 0) jOut(['success'=>false,'message'=>'Missing report_id'], 400);

  $oldStmt = $pdo->prepare("SELECT status FROM reports WHERE id=? LIMIT 1");
  $oldStmt->execute([$reportId]);
  $old = $oldStmt->fetch();
  if (!$old) jOut(['success'=>false,'message'=>'Report not found'], 404);

  $stmt = $pdo->prepare("
    UPDATE reports
    SET status='forwarded', updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([$reportId]);

  auditLog($pdo, (int)$currentUser['id'], 'FORWARD_TO_RD', 'report', $reportId, [
    'from' => $old['status'] ?? null,
    'to'   => 'forwarded'
  ]);

  jOut(['success'=>true]);
}

/* ------------------- OCULAR SCHEDULE ------------------- */

if ($action === 'set_ocular_schedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!in_array($currentUser['role'], ['admin','admin_aide'], true)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $ocularSchedule = trim((string)($in['ocular_schedule'] ?? ''));
  $ocularNotes = trim((string)($in['ocular_notes'] ?? ''));

  if ($reportId <= 0 || $ocularSchedule === '') {
    jOut(['success'=>false,'message'=>'Missing report_id/ocular_schedule'], 400);
  }

  $ocularSchedule = str_replace('T', ' ', $ocularSchedule);

  $dt = DateTime::createFromFormat('Y-m-d H:i', $ocularSchedule)
     ?: DateTime::createFromFormat('Y-m-d H:i:s', $ocularSchedule);

  if (!$dt) jOut(['success'=>false,'message'=>'Invalid datetime format'], 400);

  $stmt = $pdo->prepare("SELECT ocular_schedule, ocular_notes FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $old = $stmt->fetch();
  if (!$old) jOut(['success'=>false,'message'=>'Report not found'], 404);

  $stmt = $pdo->prepare("
    UPDATE reports
    SET ocular_schedule=?, ocular_notes=?, updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([$dt->format('Y-m-d H:i:s'), $ocularNotes, $reportId]);

  auditLog($pdo, (int)$currentUser['id'], 'SET_OCULAR_SCHEDULE', 'report', $reportId, [
    'old_schedule' => $old['ocular_schedule'] ?? null,
    'new_schedule' => $dt->format('Y-m-d H:i:s'),
    'old_notes'    => $old['ocular_notes'] ?? null,
    'new_notes'    => $ocularNotes
  ]);

  jOut(['success'=>true]);
}

/* ------------------- RD DECISION ------------------- */

if ($action === 'rd_decision' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($currentUser['role'] !== 'rd') {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $decision = (string)($in['decision'] ?? '');
  $remarks  = trim((string)($in['remarks'] ?? ''));

  if ($reportId <= 0) jOut(['success'=>false,'message'=>'Missing report_id'], 400);
  if (!in_array($decision, ['approved','disapproved'], true)) {
    jOut(['success'=>false,'message'=>'Invalid decision'], 400);
  }

  $stmt = $pdo->prepare("SELECT status FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();

  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);
  if ($rep['status'] !== 'forwarded') {
    jOut(['success'=>false,'message'=>'This report is not yet forwarded to RD'], 400);
  }

  $stmt = $pdo->prepare("
    UPDATE reports
    SET status=?, rd_remarks=?, rd_decided_by=?, rd_decided_at=NOW(), updated_at=NOW()
    WHERE id=?
  ");
  $stmt->execute([$decision, $remarks, (int)$currentUser['id'], $reportId]);

  auditLog($pdo, (int)$currentUser['id'], 'RD_DECISION', 'report', $reportId, [
    'decision' => $decision,
    'remarks'  => $remarks
  ]);

  jOut(['success'=>true]);
}

/* ------------------- CHECKLIST LOAD ------------------- */

if ($action === 'get_checklist') {
  $reportId = (int)($_GET['report_id'] ?? 0);
  if (!$reportId) jOut(['success'=>false,'message'=>'Missing report_id'], 400);

  $stmt = $pdo->prepare("SELECT id, user_id, sdo, assigned_to, title FROM reports WHERE id=?");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();
  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  if (!canViewReport($currentUser, $rep)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  if (!empty($rep['assigned_to'])) {
    $chk = $pdo->prepare("SELECT * FROM checklists WHERE report_id=? AND personnel_id=? ORDER BY updated_at DESC, id DESC LIMIT 1");
    $chk->execute([$reportId, (int)$rep['assigned_to']]);
  } else {
    $chk = $pdo->prepare("SELECT * FROM checklists WHERE report_id=? ORDER BY updated_at DESC, id DESC LIMIT 1");
    $chk->execute([$reportId]);
  }

  $row = $chk->fetch();
  $data = $row ? json_decode($row['data'], true) : null;

  jOut([
    'success' => true,
    'report' => $rep,
    'checklist' => $row ? [
      'id' => (int)$row['id'],
      'personnel_id' => (int)$row['personnel_id'],
      'has_missing' => (int)$row['has_missing'],
      'data' => $data,
      'updated_at' => $row['updated_at'] ?? null
    ] : null
  ]);
}

/* ------------------- CHECKLIST SAVE ------------------- */

if ($action === 'save_checklist' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $data = $in['data'] ?? null;
  $hasMissing = !empty($in['has_missing']) ? 1 : 0;

  if (!$reportId || !$data) jOut(['success'=>false,'message'=>'Missing report_id/data'], 400);

  $stmt = $pdo->prepare("SELECT id, user_id, assigned_to, sdo, title, status FROM reports WHERE id=?");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();
  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  if (!in_array($currentUser['role'], ['admin','admin_aide'], true) && (int)$rep['assigned_to'] !== (int)$currentUser['id']) {
    jOut(['success'=>false,'message'=>'Not assigned'], 403);
  }

  $personnelIdToStore = !empty($rep['assigned_to']) ? (int)$rep['assigned_to'] : (int)$currentUser['id'];

  $chk = $pdo->prepare("SELECT id FROM checklists WHERE report_id=? AND personnel_id=? LIMIT 1");
  $chk->execute([$reportId, $personnelIdToStore]);
  $existing = $chk->fetch();

  if ($existing) {
    $pdo->prepare("UPDATE checklists SET data=?, has_missing=?, updated_at=NOW() WHERE id=?")
        ->execute([json_encode($data), $hasMissing, $existing['id']]);
  } else {
    $pdo->prepare("INSERT INTO checklists (report_id, personnel_id, data, has_missing, updated_at) VALUES (?,?,?,?,NOW())")
        ->execute([$reportId, $personnelIdToStore, json_encode($data), $hasMissing]);
  }

  $newStatus = $hasMissing ? 'compliance' : 'inspection';
  $pdo->prepare("UPDATE reports SET status=?, updated_at=NOW() WHERE id=?")->execute([$newStatus, $reportId]);

  auditLog($pdo, (int)$currentUser['id'], 'SAVE_CHECKLIST', 'report', $reportId, [
    'has_missing' => (int)$hasMissing,
    'old_status'  => $rep['status'] ?? null,
    'new_status'  => $newStatus,
    'personnel_id'=> $personnelIdToStore
  ]);

  jOut(['success'=>true]);
}

/* =========================
   ✅ D1 FORM (LOAD/SAVE/SUBMIT)
   ========================= */

if ($action === 'get_d1') {
  $reportId = (int)($_GET['report_id'] ?? 0);
  if ($reportId <= 0) jOut(['success'=>false,'message'=>'Missing report_id'], 400);

  $stmt = $pdo->prepare("SELECT id, user_id, sdo, assigned_to, title FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();
  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  if (!canViewReport($currentUser, $rep)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $dStmt = $pdo->prepare("SELECT status, data, updated_at, submitted_at FROM d1_forms WHERE report_id=? LIMIT 1");
  $dStmt->execute([$reportId]);
  $row = $dStmt->fetch();

  jOut([
    'success' => true,
    'report'  => $rep,
    'status'  => $row['status'] ?? null,
    'updated_at' => $row['updated_at'] ?? null,
    'submitted_at' => $row['submitted_at'] ?? null,
    'data'    => $row ? (json_decode($row['data'], true) ?: null) : null
  ]);
}

if ($action === 'save_d1' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $formData = $in['form_data'] ?? null;

  if ($reportId <= 0 || !is_array($formData)) {
    jOut(['success'=>false,'message'=>'Missing report_id/form_data'], 400);
  }

  $stmt = $pdo->prepare("SELECT id, user_id, sdo, assigned_to, title, status FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();
  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  if (!canViewReport($currentUser, $rep)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $chk = $pdo->prepare("SELECT status FROM d1_forms WHERE report_id=? LIMIT 1");
  $chk->execute([$reportId]);
  $existing = $chk->fetch();
  if ($existing && $existing['status'] === 'final') {
    jOut(['success'=>false,'message'=>'D1 already submitted (final).'], 400);
  }

  $json = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $up = $pdo->prepare("
    INSERT INTO d1_forms (report_id, user_id, status, data, updated_at, submitted_at)
    VALUES (?, ?, 'draft', ?, NOW(), NULL)
    ON DUPLICATE KEY UPDATE
      user_id = VALUES(user_id),
      status  = 'draft',
      data    = VALUES(data),
      updated_at = NOW(),
      submitted_at = NULL
  ");
  $up->execute([$reportId, (int)$currentUser['id'], $json]);

  auditLog($pdo, (int)$currentUser['id'], 'SAVE_D1', 'report', $reportId, [
    'mode' => 'draft'
  ]);

  jOut(['success'=>true]);
}

if ($action === 'submit_d1' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $in = getJsonInput();
  $reportId = (int)($in['report_id'] ?? 0);
  $formData = $in['form_data'] ?? null;

  if ($reportId <= 0 || !is_array($formData)) {
    jOut(['success'=>false,'message'=>'Missing report_id/form_data'], 400);
  }

  $stmt = $pdo->prepare("SELECT id, user_id, sdo, assigned_to, title, status FROM reports WHERE id=? LIMIT 1");
  $stmt->execute([$reportId]);
  $rep = $stmt->fetch();
  if (!$rep) jOut(['success'=>false,'message'=>'Report not found'], 404);

  if (!canViewReport($currentUser, $rep)) {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $json = json_encode($formData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $up = $pdo->prepare("
    INSERT INTO d1_forms (report_id, user_id, status, data, updated_at, submitted_at)
    VALUES (?, ?, 'final', ?, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      user_id = VALUES(user_id),
      status  = 'final',
      data    = VALUES(data),
      updated_at = NOW(),
      submitted_at = NOW()
  ");
  $up->execute([$reportId, (int)$currentUser['id'], $json]);

  auditLog($pdo, (int)$currentUser['id'], 'SUBMIT_D1', 'report', $reportId, [
    'mode' => 'final'
  ]);

  jOut(['success'=>true]);
}

/* ------------------- AUDIT LOG (ADMIN ONLY) ------------------- */

if ($action === 'get_audit_log') {
  if ($currentUser['role'] !== 'admin') {
    jOut(['success'=>false,'message'=>'Unauthorized'], 403);
  }

  $limit = (int)($_GET['limit'] ?? 200);
  if ($limit < 10) $limit = 10;
  if ($limit > 500) $limit = 500;

  $stmt = $pdo->prepare("
    SELECT a.id, a.actor_id, u.name AS actor_name, u.role AS actor_role,
           a.action, a.entity_type, a.entity_id, a.meta, a.created_at
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.actor_id
    ORDER BY a.created_at DESC
    LIMIT ?
  ");
  $stmt->bindValue(1, $limit, PDO::PARAM_INT);
  $stmt->execute();

  jOut(['success'=>true,'items'=>$stmt->fetchAll()]);
}

/* =========================
   ✅ MESSAGING / CHAT API
   ========================= */

if ($action === 'get_chat_users') {
  $stmt = $pdo->prepare("SELECT id, name, role, sdo FROM users WHERE id <> ? ORDER BY name ASC");
  $stmt->execute([(int)$currentUser['id']]);
  jOut(['success'=>true,'users'=>$stmt->fetchAll()]);
}

if ($action === 'get_conversations') {
  $me = (int)$currentUser['id'];

  $stmt = $pdo->prepare("
    SELECT x.peer_id, m.body AS last_message, m.created_at AS updated_at
    FROM (
      SELECT
        (CASE WHEN from_id = :me THEN to_id ELSE from_id END) AS peer_id,
        MAX(id) AS last_id
      FROM messages
      WHERE from_id = :me OR to_id = :me
      GROUP BY (CASE WHEN from_id = :me THEN to_id ELSE from_id END)
    ) x
    JOIN messages m ON m.id = x.last_id
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
  $unreadRows = $uStmt->fetchAll();

  $unreadMap = [];
  foreach ($unreadRows as $ur) {
    $unreadMap[(string)$ur['peer_id']] = (int)$ur['unread'];
  }

  $peerIds = array_map(fn($r) => (int)$r['peer_id'], $rows);
  $peerInfo = [];
  if (count($peerIds) > 0) {
    $in = implode(',', array_fill(0, count($peerIds), '?'));
    $pStmt = $pdo->prepare("SELECT id, name, role, sdo FROM users WHERE id IN ($in)");
    $pStmt->execute($peerIds);
    foreach ($pStmt->fetchAll() as $p) {
      $peerInfo[(string)$p['id']] = $p;
    }
  }

  $items = [];
  foreach ($rows as $r) {
    $pid = (string)$r['peer_id'];
    if (!isset($peerInfo[$pid])) continue;
    $p = $peerInfo[$pid];
    $items[] = [
      'peer_id'   => (int)$p['id'],
      'peer_name' => (string)$p['name'],
      'peer_role' => (string)$p['role'],
      'peer_sdo'  => (string)($p['sdo'] ?? 'Unassigned'),
      'last_message' => (string)($r['last_message'] ?? ''),
      'updated_at'   => (string)($r['updated_at'] ?? ''),
      'unread'       => (int)($unreadMap[$pid] ?? 0),
    ];
  }

  jOut(['success'=>true,'items'=>$items]);
}

if ($action === 'get_messages') {
  $me = (int)$currentUser['id'];
  $peerId = (int)($_GET['peer_id'] ?? 0);
  if ($peerId <= 0) jOut(['success'=>false,'message'=>'Missing peer_id'], 400);
  if ($peerId === $me) jOut(['success'=>false,'message'=>'Invalid peer'], 400);

  $pStmt = $pdo->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
  $pStmt->execute([$peerId]);
  if (!$pStmt->fetch()) jOut(['success'=>false,'message'=>'User not found'], 404);

  $stmt = $pdo->prepare("
    SELECT id, from_id, to_id, body, created_at, read_at
    FROM messages
    WHERE (from_id=? AND to_id=?) OR (from_id=? AND to_id=?)
    ORDER BY id DESC
    LIMIT ".CHAT_FETCH_LIMIT."
  ");
  $stmt->execute([$me, $peerId, $peerId, $me]);
  $msgs = array_reverse($stmt->fetchAll());

  jOut(['success'=>true,'messages'=>$msgs]);
}

if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $me = (int)$currentUser['id'];
  $in = getJsonInput();

  $toId = (int)($in['to_id'] ?? 0);
  $body = trim((string)($in['body'] ?? ''));

  if ($toId <= 0) jOut(['success'=>false,'message'=>'Missing to_id'], 400);
  if ($toId === $me) jOut(['success'=>false,'message'=>'Cannot message yourself'], 400);
  if ($body === '') jOut(['success'=>false,'message'=>'Empty message'], 400);
  if (mb_strlen($body) > CHAT_MAX_LEN) {
    $body = mb_substr($body, 0, CHAT_MAX_LEN);
  }

  $pStmt = $pdo->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
  $pStmt->execute([$toId]);
  if (!$pStmt->fetch()) jOut(['success'=>false,'message'=>'Recipient not found'], 404);

  try {
    $stmt = $pdo->prepare("
      INSERT INTO messages (from_id, to_id, body, created_at)
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$me, $toId, $body]);
  } catch (Exception $e) {
    jOut(['success'=>false,'message'=>'DB error'], 500);
  }

  jOut(['success'=>true]);
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $me = (int)$currentUser['id'];
  $in = getJsonInput();
  $peerId = (int)($in['peer_id'] ?? 0);
  if ($peerId <= 0) jOut(['success'=>false,'message'=>'Missing peer_id'], 400);

  $stmt = $pdo->prepare("
    UPDATE messages
    SET read_at = NOW()
    WHERE to_id = ? AND from_id = ? AND read_at IS NULL
  ");
  $stmt->execute([$me, $peerId]);

  jOut(['success'=>true]);
}

if ($action === 'get_unread_messages_count') {
  $me = (int)$currentUser['id'];
  $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM messages WHERE to_id=? AND read_at IS NULL");
  $stmt->execute([$me]);
  $row = $stmt->fetch();
  jOut(['success'=>true,'count'=>(int)($row['c'] ?? 0)]);
}

/* ✅ fallback MUST be LAST */
jOut(['success'=>false,'message'=>'Invalid action'], 404);
