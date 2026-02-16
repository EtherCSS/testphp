<?php
// ApplicationController.php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

// -------------------------
// PDO BOOTSTRAP
// -------------------------
$dsn  = "mysql:host=localhost;dbname=iqad;charset=utf8mb4";
$user = "root";
$pass = "";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);

// -------------------------
// HELPERS
// -------------------------
function json_input(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_auth(): array {
  if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
  }
  return $_SESSION['user']; // ['id'=>..,'role'=>..,'sdo_division'=>..]
}

function require_role(array $user, array $allowed): void {
  if (!in_array($user['role'], $allowed, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden (RBAC)']);
    exit;
  }
}

/**
 * Central transition function:
 * - locks row
 * - updates applications current_step_id/status (+ timestamp fields)
 * - inserts application_logs record
 */
function transition_app(PDO $pdo, int $appId, int $actorId, array $patch, array $log): void {
  $pdo->beginTransaction();

  // Lock the application row (prevents double-click race issues)
  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? FOR UPDATE");
  $stmt->execute([$appId]);
  $app = $stmt->fetch();
  if (!$app) {
    $pdo->rollBack();
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Application not found']);
    exit;
  }

  $fromStep   = (int)$app['current_step_id'];
  $fromStatus = (string)$app['status'];

  // Build dynamic UPDATE
  $cols = [];
  $vals = [];
  foreach ($patch as $col => $val) {
    $cols[] = "$col = ?";
    $vals[] = $val;
  }
  $vals[] = $appId;

  $sql = "UPDATE applications SET " . implode(", ", $cols) . " WHERE id = ?";
  $pdo->prepare($sql)->execute($vals);

  // Log insert
  $pdo->prepare("
    INSERT INTO application_logs
      (application_id, actor_user_id, action, from_step_id, to_step_id, from_status, to_status, notes)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?)
  ")->execute([
    $appId,
    $actorId,
    $log['action'],
    $fromStep,
    $log['to_step_id'] ?? $patch['current_step_id'] ?? null,
    $fromStatus,
    $log['to_status'] ?? $patch['status'] ?? null,
    $log['notes'] ?? null
  ]);

  $pdo->commit();
}

// -------------------------
// ROUTER
// -------------------------
$action = $_GET['action'] ?? '';

$user = require_auth();
$actorId = (int)$user['id'];

// -------------------------
// READ: list applications (role-aware dashboard)
// -------------------------
if ($action === 'list_applications') {
  // Admin-like roles see wider view; others limited
  $role = $user['role'];

  if (in_array($role, ['ADMIN_AIDE','QAD_EPS','QAD_CHIEF','REGIONAL_DIRECTOR'], true)) {
    $stmt = $pdo->query("
      SELECT a.*,
             u.full_name AS sdo_name
      FROM applications a
      JOIN users u ON u.id = a.sdo_user_id
      ORDER BY a.created_at DESC
      LIMIT 200
    ");
    $rows = $stmt->fetchAll();
  } else { // SDO
    $stmt = $pdo->prepare("
      SELECT a.*,
             u.full_name AS sdo_name
      FROM applications a
      JOIN users u ON u.id = a.sdo_user_id
      WHERE a.sdo_user_id = ?
      ORDER BY a.created_at DESC
      LIMIT 200
    ");
    $stmt->execute([$actorId]);
    $rows = $stmt->fetchAll();
  }

  echo json_encode(['success' => true, 'applications' => $rows, 'me' => $user]);
  exit;
}

// -------------------------
// STEP 1 (SDO): Create NEW APPLICATION (DRAFT)
// -------------------------
if ($action === 'create_application') {
  require_role($user, ['SDO']);
  $in = json_input();

  $schoolName = trim((string)($in['school_name'] ?? ''));
  $type       = (string)($in['application_type'] ?? '');
  $profile    = (string)($in['school_profile'] ?? '');

  $allowedTypes = ['ESTABLISHMENT','MERGING','CONVERSION','SEPARATION'];
  if ($schoolName === '' || !in_array($type, $allowedTypes, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'School Name + valid Application Type required']);
    exit;
  }

  $stmt = $pdo->prepare("
    INSERT INTO applications
      (sdo_user_id, school_name, application_type, school_profile, current_step_id, status)
    VALUES
      (?, ?, ?, ?, 1, 'DRAFT')
  ");
  $stmt->execute([$actorId, $schoolName, $type, $profile]);
  $appId = (int)$pdo->lastInsertId();

  // Create checklist shell row (optional but convenient)
  $pdo->prepare("INSERT INTO checklists (application_id) VALUES (?)")->execute([$appId]);

  $pdo->prepare("
    INSERT INTO application_logs (application_id, actor_user_id, action, to_step_id, to_status, notes)
    VALUES (?, ?, 'CREATE', 1, 'DRAFT', NULL)
  ")->execute([$appId, $actorId]);

  echo json_encode(['success' => true, 'application_id' => $appId]);
  exit;
}

// -------------------------
// STEP 1 (SDO): Edit & Re-submit if FOR_COMPLIANCE
// -------------------------
if ($action === 'update_and_submit') {
  require_role($user, ['SDO']);
  $in = json_input();
  $appId = (int)($in['application_id'] ?? 0);

  // Only allow if owned by SDO and status is DRAFT or FOR_COMPLIANCE
  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? AND sdo_user_id = ?");
  $stmt->execute([$appId, $actorId]);
  $app = $stmt->fetch();
  if (!$app) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
  }

  $status = (string)$app['status'];
  if (!in_array($status, ['DRAFT','FOR_COMPLIANCE'], true)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => "Cannot submit from status: $status"]);
    exit;
  }

  $schoolName = trim((string)($in['school_name'] ?? $app['school_name']));
  $type       = (string)($in['application_type'] ?? $app['application_type']);
  $profile    = (string)($in['school_profile'] ?? $app['school_profile']);

  if ($schoolName === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'School Name required']);
    exit;
  }

  // SUBMIT => goes to Step 2 with status SUBMITTED
  transition_app(
    $pdo,
    $appId,
    $actorId,
    [
      'school_name'      => $schoolName,
      'application_type' => $type,
      'school_profile'   => $profile,
      'current_step_id'  => 2,
      'status'           => 'SUBMITTED',
      'submitted_at'     => date('Y-m-d H:i:s')
    ],
    [
      'action'    => 'SUBMIT',
      'to_step_id'=> 2,
      'to_status' => 'SUBMITTED',
      'notes'     => null
    ]
  );

  echo json_encode(['success' => true]);
  exit;
}

// -------------------------
// STEP 2 (ADMIN_AIDE): Receive -> forward to QAD_EPS
// -------------------------
if ($action === 'receive_application') {
  require_role($user, ['ADMIN_AIDE']);
  $in = json_input();
  $appId = (int)($in['application_id'] ?? 0);
  $epsUserId = (int)($in['eps_user_id'] ?? 0); // assigned QAD EPS personnel

  // Must be in step 2 and status SUBMITTED
  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
  $stmt->execute([$appId]);
  $app = $stmt->fetch();
  if (!$app) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

  if ((int)$app['current_step_id'] !== 2 || (string)$app['status'] !== 'SUBMITTED') {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Must be Step 2 + SUBMITTED to Receive']);
    exit;
  }

  transition_app(
    $pdo,
    $appId,
    $actorId,
    [
      'admin_aide_user_id' => $actorId,
      'eps_user_id'        => ($epsUserId > 0 ? $epsUserId : null),
      'current_step_id'    => 3,
      'status'             => 'RECEIVED',
      'received_at'        => date('Y-m-d H:i:s')
    ],
    [
      'action'    => 'RECEIVE',
      'to_step_id'=> 3,
      'to_status' => 'RECEIVED',
      'notes'     => 'Forwarded to QAD EPS'
    ]
  );

  echo json_encode(['success' => true]);
  exit;
}

// -------------------------
// STEP 3 (QAD_EPS): Validation (3-part decision)
// If any FAIL/INCOMPLETE => FOR_COMPLIANCE => back to Step 1
// If all PASS => RECOMMENDED => Step 4
// -------------------------
if ($action === 'validate_application') {
  require_role($user, ['QAD_EPS']);
  $in = json_input();
  $appId = (int)($in['application_id'] ?? 0);

  // booleans from form
  $docPass   = isset($in['doc_check_pass']) ? (int)((bool)$in['doc_check_pass']) : null;
  $evalPass  = isset($in['evaluation_pass']) ? (int)((bool)$in['evaluation_pass']) : null;
  $ocularPass= isset($in['ocular_pass']) ? (int)((bool)$in['ocular_pass']) : null;

  $docNotes   = (string)($in['doc_check_notes'] ?? null);
  $evalNotes  = (string)($in['evaluation_notes'] ?? null);
  $ocularNotes= (string)($in['ocular_notes'] ?? null);

  // Must be in Step 3
  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
  $stmt->execute([$appId]);
  $app = $stmt->fetch();
  if (!$app) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

  if ((int)$app['current_step_id'] !== 3) {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Must be in Step 3 to validate']);
    exit;
  }

  // Update checklist (independent of pass/fail)
  $pdo->prepare("
    UPDATE checklists
    SET doc_check_pass=?, doc_check_notes=?,
        evaluation_pass=?, evaluation_notes=?,
        ocular_pass=?, ocular_notes=?,
        last_updated_by=?, last_updated_at=?
    WHERE application_id=?
  ")->execute([
    $docPass, $docNotes,
    $evalPass, $evalNotes,
    $ocularPass, $ocularNotes,
    $actorId, date('Y-m-d H:i:s'),
    $appId
  ]);

  // Decision logic: if ANY is not 1 => loop back for compliance
  $allPass = ($docPass === 1 && $evalPass === 1 && $ocularPass === 1);

  if (!$allPass) {
    // Return to Step 1 for compliance
    transition_app(
      $pdo,
      $appId,
      $actorId,
      [
        'eps_user_id'       => $actorId,
        'current_step_id'   => 1,
        'status'            => 'FOR_COMPLIANCE',
        'validated_at'      => date('Y-m-d H:i:s')
      ],
      [
        'action'    => 'RETURN_FOR_COMPLIANCE',
        'to_step_id'=> 1,
        'to_status' => 'FOR_COMPLIANCE',
        'notes'     => 'One or more validation parts failed/incomplete'
      ]
    );

    echo json_encode(['success' => true, 'result' => 'FOR_COMPLIANCE']);
    exit;
  }

  // All passed => RECOMMENDED => Step 4 (QAD Chief)
  transition_app(
    $pdo,
    $appId,
    $actorId,
    [
      'eps_user_id'       => $actorId,
      'current_step_id'   => 4,
      'status'            => 'RECOMMENDED',
      'validated_at'      => date('Y-m-d H:i:s'),
      'recommended_at'    => date('Y-m-d H:i:s')
    ],
    [
      'action'    => 'RECOMMEND',
      'to_step_id'=> 4,
      'to_status' => 'RECOMMENDED',
      'notes'     => 'All Step 3 validations passed'
    ]
  );

  echo json_encode(['success' => true, 'result' => 'RECOMMENDED']);
  exit;
}

// -------------------------
// STEP 4 (QAD_CHIEF): Forward to Regional Director
// -------------------------
if ($action === 'forward_to_rd') {
  require_role($user, ['QAD_CHIEF']);
  $in = json_input();
  $appId = (int)($in['application_id'] ?? 0);
  $rdUserId = (int)($in['rd_user_id'] ?? 0);

  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
  $stmt->execute([$appId]);
  $app = $stmt->fetch();
  if (!$app) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

  if ((int)$app['current_step_id'] !== 4 || (string)$app['status'] !== 'RECOMMENDED') {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Must be Step 4 + RECOMMENDED to forward']);
    exit;
  }

  transition_app(
    $pdo,
    $appId,
    $actorId,
    [
      'chief_user_id'      => $actorId,
      'rd_user_id'         => ($rdUserId > 0 ? $rdUserId : null),
      'current_step_id'    => 5,
      'status'             => 'FORWARDED_TO_RD',
      'forwarded_to_rd_at' => date('Y-m-d H:i:s')
    ],
    [
      'action'    => 'FORWARD_TO_RD',
      'to_step_id'=> 5,
      'to_status' => 'FORWARDED_TO_RD',
      'notes'     => 'Forwarded to Regional Director for final action'
    ]
  );

  echo json_encode(['success' => true]);
  exit;
}

// -------------------------
// STEP 5 (REGIONAL_DIRECTOR): Approve / Disapprove
// -------------------------
if ($action === 'decide') {
  require_role($user, ['REGIONAL_DIRECTOR']);
  $in = json_input();
  $appId = (int)($in['application_id'] ?? 0);
  $decision = strtoupper(trim((string)($in['decision'] ?? ''))); // APPROVE / DISAPPROVE
  $notes = (string)($in['notes'] ?? null);

  if (!in_array($decision, ['APPROVE','DISAPPROVE'], true)) {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'Decision must be APPROVE or DISAPPROVE']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT * FROM applications WHERE id=?");
  $stmt->execute([$appId]);
  $app = $stmt->fetch();
  if (!$app) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

  if ((int)$app['current_step_id'] !== 5 || (string)$app['status'] !== 'FORWARDED_TO_RD') {
    http_response_code(409);
    echo json_encode(['success'=>false,'message'=>'Must be Step 5 + FORWARDED_TO_RD to decide']);
    exit;
  }

  $newStatus = ($decision === 'APPROVE') ? 'APPROVED' : 'DISAPPROVED';

  transition_app(
    $pdo,
    $appId,
    $actorId,
    [
      'rd_user_id'       => $actorId,
      'current_step_id'  => 5,
      'status'           => $newStatus,
      'decided_at'       => date('Y-m-d H:i:s')
    ],
    [
      'action'    => $decision,
      'to_step_id'=> 5,
      'to_status' => $newStatus,
      'notes'     => $notes
    ]
  );

  echo json_encode(['success' => true, 'status' => $newStatus]);
  exit;
}

// -------------------------
// Default
// -------------------------
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
