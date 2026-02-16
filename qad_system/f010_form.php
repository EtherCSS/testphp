<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
if (($_SESSION['user']['role'] ?? '') !== 'sdo') { die("Access denied"); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RO-QAD-F010</title>
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px;}
  .container{max-width:900px;background:#fff;padding:40px;margin:0 auto;box-shadow:0 0 10px rgba(0,0,0,.1);}
  h1{text-align:center;font-size:18px;font-weight:bold;margin-bottom:10px;text-transform:uppercase;}
  .stamp{text-align:center;font-size:13px;color:#64748b;margin-bottom:25px;}
  label{font-weight:bold;display:block;margin-bottom:5px;}
  input[type="text"], textarea, input[type="date"]{
    width:100%;padding:8px;margin-top:5px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;
  }
  textarea{min-height:110px;resize:vertical;}
  .school-info{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:25px;}
  .signature-section{margin-top:40px;display:flex;gap:20px;}
  .signature-block{flex:1;}
  .btns{display:flex;gap:10px;justify-content:center;margin-bottom:20px;}
  button,a{
    padding:10px 20px;border:none;border-radius:6px;font-size:15px;cursor:pointer;text-decoration:none;
  }
  .btn-primary{background:#0f4c81;color:white;}
  .btn-gray{background:#e2e8f0;color:#0f172a;}
  @media print{
    .btns{display:none;}
    body{background:white;padding:0;}
    .container{box-shadow:none;max-width:100%;padding:0;margin:0;}
  }
</style>
</head>
<body>

<div class="btns">
  <a class="btn-gray" href="index.php">Back</a>
  <button class="btn-primary" onclick="submitF010()">Submit Form</button>
  <button class="btn-gray" onclick="window.print()">Print / Save PDF</button>
</div>

<div class="container">
  <h1>RO-QAD-F010</h1>
  <div class="stamp">EVALUATION AND RECOMMENDATION REPORT OF SCHOOL’S APPLICATION</div>

  <div class="school-info">
    <div>
      <label>NAME OF THE SCHOOL</label>
      <input id="school_name" type="text" required>
    </div>
    <div>
      <label>ADDRESS OF THE SCHOOL</label>
      <input id="school_address" type="text" required>
    </div>
  </div>

  <div class="section">
    <label>BACKGROUND <i style="font-weight:normal;color:#555">(Based on feasibility studies / legal basis)</i></label>
    <textarea id="background" required></textarea>
  </div>

  <div class="section">
    <label>FINDINGS <i style="font-weight:normal;color:#555">(Based on ocular inspection)</i></label>
    <textarea id="findings" required placeholder="1.&#10;2."></textarea>
  </div>

  <div class="section">
    <label>RECOMMENDATIONS</label>
    <textarea id="recommendations" required placeholder="1.&#10;2."></textarea>
  </div>

  <div class="signature-section">
    <div class="signature-block">
      <label>Evaluated by:</label>
      <input id="evaluated_by" type="text">
      <label style="margin-top:10px;">Date:</label>
      <input id="evaluated_date" type="date">
      <div style="margin-top:10px;font-weight:bold;text-align:center;border-top:1px solid #000;padding-top:6px;">
        Education Program Supervisor
      </div>
    </div>

    <div class="signature-block">
      <label>Reviewed by:</label>
      <input id="reviewed_by" type="text">
      <label style="margin-top:10px;">Date:</label>
      <input id="reviewed_date" type="date">
      <div style="margin-top:10px;font-weight:bold;text-align:center;border-top:1px solid #000;padding-top:6px;">
        Chief Education Supervisor
      </div>
    </div>
  </div>

  <div style="margin-top:25px;color:#64748b;font-size:13px;">
    <b>Timestamp will be generated automatically upon submission.</b>
  </div>
</div>

<script>
async function submitF010(){
  if(!confirm("Submit RO-QAD-F010 form now?")) return;

  const payload = {
    school_name: document.getElementById('school_name').value,
    school_address: document.getElementById('school_address').value,
    background: document.getElementById('background').value,
    findings: document.getElementById('findings').value,
    recommendations: document.getElementById('recommendations').value,
    evaluated_by: document.getElementById('evaluated_by').value,
    evaluated_date: document.getElementById('evaluated_date').value,
    reviewed_by: document.getElementById('reviewed_by').value,
    reviewed_date: document.getElementById('reviewed_date').value
  };

  const res = await fetch(`api.php?action=save_f010`, {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    credentials:'same-origin',
    body: JSON.stringify(payload)
  });

  const d = await res.json();
  if(!d.success){
    alert(d.message || "Submit failed");
    return;
  }

  alert("Submitted successfully! Timestamp saved.");
  window.location.href = `f010_view.php?id=${d.id}`;
}
</script>

</body>
</html>
