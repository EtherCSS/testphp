<?php
session_start();
if (!isset($_SESSION['user'])) die("Access Denied");

// DB Connection
try {
  $pdo = new PDO("mysql:host=localhost;dbname=qad_system;charset=utf8mb4", "root", "");
} catch (Exception $e) { die("DB Error"); }

$id = $_GET['id'] ?? 0;

// Fetch Evaluation Data
$stmt = $pdo->prepare("SELECT * FROM evaluations WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) die("Report Not Found");

// Fetch School Name using the School ID Reference
$schoolName = "Unknown School";
$stmtS = $pdo->prepare("SELECT name FROM schools WHERE school_id = ?");
$stmtS->execute([$data['school_id_ref']]);
$school = $stmtS->fetch(PDO::FETCH_ASSOC);
if($school) $schoolName = $school['name'];

// ✅ FIX: get application type
$appType = $data['application_type'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
  <title>Evaluation Report | <?php echo htmlspecialchars($schoolName); ?></title>
  <style>
    body { font-family: 'Arial', sans-serif; max-width: 850px; margin: 40px auto; padding: 40px; line-height: 1.6; color:#333; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    .header { text-align: center; border-bottom: 2px solid #0f4c81; padding-bottom: 20px; margin-bottom: 30px; }
    .header h2 { margin: 0; color: #0f4c81; text-transform: uppercase; }
    .meta { background: #f0f7ff; padding: 15px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #cce3ff; }
    .section { margin-bottom: 25px; page-break-inside: avoid; }
    .q { font-weight: bold; font-size: 0.95rem; color: #444; margin-bottom: 5px; }
    .a { background: #f9f9f9; padding: 12px; border-left: 4px solid #0f4c81; white-space: pre-wrap; font-size: 0.95rem; }
    .btn-print { position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #0f4c81; color: white; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; }

    @media print {
      .btn-print { display: none; }
      body { box-shadow: none; margin: 0; padding: 20px; }
      .a { border-left: 1px solid #000; }
    }
  </style>
</head>
<body>
  <button class="btn-print" onclick="window.print()">Print Report</button>

  <div class="header">
    <small>Republic of the Philippines</small><br>
    <strong>Department of Education</strong><br>
    Region VIII - Eastern Visayas
    <br><br>
    <h2>Evaluation and Recommendation Report</h2>
    <div style="margin-top:5px; font-size:0.9rem;">Form: RO-QAD-F010</div>
  </div>

  <div class="meta">
    <strong>School Name:</strong> <?php echo htmlspecialchars($schoolName); ?><br>
    <strong>School ID Ref:</strong> <?php echo htmlspecialchars($data['school_id_ref']); ?><br>

    <!-- ✅ FIX: PRINT Application Type -->
    <strong>Type of Application:</strong> <?php echo htmlspecialchars($appType); ?><br>

    <strong>Evaluator:</strong> <?php echo htmlspecialchars($data['sdo_evaluator']); ?><br>
    <strong>Date of Evaluation:</strong> <?php echo date('F j, Y', strtotime($data['created_at'])); ?>
  </div>

  <h3 style="background:#333; color:white; padding:10px;">I. Findings & Observations</h3>

  <div class="section"><div class="q">1. Educational Gaps & Access Issues</div><div class="a"><?php echo $data['q1_gaps']; ?></div></div>
  <div class="section"><div class="q">2. Equitable Access Assurance</div><div class="a"><?php echo $data['q2_access']; ?></div></div>
  <div class="section"><div class="q">3. Community & LGU Support</div><div class="a"><?php echo $data['q3_support']; ?></div></div>
  <div class="section"><div class="q">4. Site Compliance (Safety/Ownership)</div><div class="a"><?php echo $data['q4_site']; ?></div></div>
  <div class="section"><div class="q">5. Learning Facilities Status</div><div class="a"><?php echo $data['q5_facilities']; ?></div></div>
  <div class="section"><div class="q">6. Enrollment Projection</div><div class="a"><?php echo $data['q6_enrollment']; ?></div></div>
  <div class="section"><div class="q">7. Personnel Requirements</div><div class="a"><?php echo $data['q7_personnel']; ?></div></div>
  <div class="section"><div class="q">8. Financial Resources</div><div class="a"><?php echo $data['q8_resources']; ?></div></div>
  <div class="section"><div class="q">9. Sustainability Plan</div><div class="a"><?php echo $data['q9_sustainability']; ?></div></div>

  <h3 style="background:#333; color:white; padding:10px;">II. Recommendations</h3>
  <div class="a" style="min-height: 100px;"><?php echo $data['recommendations']; ?></div>

  <div style="margin-top:50px; text-align:right; border-top:1px solid #ccc; padding-top:10px;">
    <small>Generated via QAD Cloud System | <?php echo date('Y-m-d H:i'); ?></small>
  </div>
</body>
</html>
