<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

// ✅ allow both admin + admin_aide to edit (change if you want only admin)
$role = $_SESSION['user']['role'] ?? '';
$canEdit = in_array($role, ['admin','admin_aide'], true);

$reportId = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
if (!$reportId) { die("Missing report_id"); }

// ✅ read type of application from URL (passed from dashboard link)
$applicationType = isset($_GET['application_type']) ? trim((string)$_GET['application_type']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annex C-1 (Electronic)</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; padding:20px; color:#333; }
    .container { max-width: 850px; background:#fff; margin:0 auto; padding:40px; box-shadow:0 0 10px rgba(0,0,0,.1); border:1px solid #ddd; }
    .header { text-align:center; margin-bottom:30px; }
    .header h2, .header h3 { margin:5px 0; text-transform:uppercase; }
    .header h2 { font-size:16px; }
    .header h3 { font-size:14px; font-weight:normal; }
    .doc-label { text-align:right; font-weight:bold; margin-bottom:10px; font-size:14px; }
    .section-title { text-align:center; font-weight:bold; margin:20px 0; text-transform:uppercase; text-decoration:underline; }

    .form-row { display:flex; align-items:center; margin-bottom:10px; }
    .form-row label { font-weight:bold; margin-right:10px; white-space:nowrap; }
    .form-row input[type="text"] { flex-grow:1; border:none; border-bottom:1px solid #000; outline:none; padding:5px; background:transparent; }

    .checklist { margin-top:20px; border-top:2px solid #333; padding-top:20px; }
    .checklist-item { display:flex; align-items:flex-start; margin-bottom:12px; line-height:1.4; }
    .checklist-item input[type="checkbox"] { margin-top:3px; margin-right:15px; transform:scale(1.2); flex-shrink:0; }
    .checklist-text { text-align:justify; }
    .sub-item { margin-left:35px; margin-bottom:8px; display:flex; align-items:flex-start; }
    .sub-item input[type="checkbox"] { margin-right:10px; }
    .or-divider { text-align:center; font-weight:bold; margin:10px 0; font-style:italic; }

    .notes { margin-top:30px; font-size:.9em; background:#f9f9f9; padding:15px; border:1px solid #eee; }
    .notes h4 { margin-top:0; }

    .footer-section { margin-top:40px; display:flex; justify-content:flex-end; }
    .receiver-box { width:300px; }
    .signature-line { border-top:1px solid #000; margin-top:40px; padding-top:5px; text-align:center; font-size:14px; }
    .date-line { margin-top:15px; }

    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px; }
    .pill { padding:6px 12px; border-radius:999px; font-weight:700; font-size:12px; }
    .pill.ok { background:#dcfce7; color:#166534; }
    .pill.warn { background:#ffedd5; color:#9a3412; }
    .pill.neutral { background:#e2e8f0; color:#0f172a; }

    .btn { padding:10px 16px; border:none; cursor:pointer; font-weight:700; border-radius:10px; }
    .btn-primary { background:#0f4c81; color:#fff; }
    .btn-primary:disabled { opacity:.6; cursor:not-allowed; }

    /* ✅ Type of Application banner (prints too) */
    .app-type-banner{
      margin: 0 0 14px;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      font-weight: 700;
      font-size: 13px;
    }
    .app-type-banner b{ color:#0f4c81; }

    @media print {
      body { background:white; padding:0; }
      .container { box-shadow:none; border:none; padding:0; }
      .topbar { display:none; }
    }
  </style>
</head>
<body>

<div class="container">

  <div class="topbar">
    <div id="statusPill" class="pill neutral">Loading...</div>
    <div>
      <button id="saveBtn" class="btn btn-primary" type="button" onclick="App.save()">Save Checklist</button>
    </div>
  </div>

  <!-- ✅ Shows and PRINTS -->
  <?php if ($applicationType !== ''): ?>
    <div class="app-type-banner">
      Type of Application: <b><?php echo htmlspecialchars($applicationType, ENT_QUOTES, 'UTF-8'); ?></b>
    </div>
  <?php endif; ?>

  <div class="header">
    <h3>Department of Education</h3>
    <h3>Division of ___________________</h3>
    <h3>Region ___________________</h3>
    <h2>Application for Establishment of Public Elementary/Secondary School</h2>
  </div>

  <div class="doc-label">Annex C-1</div>
  <div class="section-title">Checklist of Documents</div>

  <form id="c1Form">

    <div class="form-row">
      <label>Requesting Office/School:</label>
      <input type="text" name="requesting_office">
    </div>
    <div class="form-row">
      <label>Name of Proponent(s):</label>
      <input type="text" name="proponent_name">
    </div>
    <div class="form-row">
      <label>Position/Designation:</label>
      <input type="text" name="position">
    </div>
    <div class="form-row">
      <label>Proposed Name of School:</label>
      <input type="text" name="school_name">
    </div>
    <div class="form-row">
      <label>Address:</label>
      <input type="text" name="address">
    </div>

    <div class="checklist">
      <div class="checklist-item">
        <input type="checkbox" id="item1" name="item1">
        <label for="item1" class="checklist-text">1. Letter request to open a school addressed to the Schools Division Superintendent (SDS) (either from PTA or Barangay Council).</label>
      </div>

      <div class="checklist-item">
        <input type="checkbox" id="item2" name="item2">
        <label for="item2" class="checklist-text">2. Feasibility study, duly recommended/endorsed by the SDS indicating the following:</label>
      </div>
      <div class="sub-item"><input type="checkbox" id="item2a" name="item2a"><label for="item2a">a. Justification on the need to establish a school;</label></div>
      <div class="sub-item"><input type="checkbox" id="item2b" name="item2b"><label for="item2b">b. Proposed Organizational Structure;</label></div>
      <div class="sub-item"><input type="checkbox" id="item2c" name="item2c"><label for="item2c">c. School Environment (environmental scanning/situational analysis);</label></div>
      <div class="sub-item"><input type="checkbox" id="item2d" name="item2d"><label for="item2d">d. Proposed School Development Plan; and</label></div>
      <div class="sub-item"><input type="checkbox" id="item2e" name="item2e"><label for="item2e">e. Proposed Budget/Budgetary Requirements (to cover the proposed school's crucial resources).</label></div>

      <div class="checklist-item"><input type="checkbox" id="item3" name="item3"><label for="item3" class="checklist-text">3. Division Inspection Report signed by the SDS.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item4" name="item4"><label for="item4" class="checklist-text">4. Sangguniang Bayan/Panglungsod Resolution supporting the establishment of a school, duly approved by the Municipal/City Mayor, indicating therein the proposed name of the school.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item5" name="item5"><label for="item5" class="checklist-text">5. List of prospective enrollees per grade level, indicating their names, ages, addresses and/or school where they are currently or were enrolled.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item6" name="item6"><label for="item6" class="checklist-text">6. Justification on the need for the establishment of an MG school, if necessary.</label></div>

      <div class="checklist-item"><input type="checkbox" id="item7a" name="item7a"><label for="item7a" class="checklist-text">7.a Certification from the SDS that no private high school within the Municipality/City is participating in the GASTPE Program of DepED, or that GASTPE participating high school has reached its allocation or number of available slots;</label></div>

      <div class="or-divider">OR</div>

      <div class="checklist-item"><input type="checkbox" id="item7b" name="item7b"><label for="item7b" class="checklist-text">7.b Justification by the SDS on the need to establish a public school to cater to the elementary school graduates/students who cannot afford to enrol in a private high school.</label></div>

      <div class="checklist-item"><input type="checkbox" id="item8" name="item8"><label for="item8" class="checklist-text">8. Map, preferably drawn to scale, showing the distances of the existing schools within the catchment area of the proposed new school, duly certified by the Municipal/City Engineer.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item9" name="item9"><label for="item9" class="checklist-text">9. Certification from the Municipal/City Engineer that the proposed school is not within the 2-km radius (for rural areas) and 1 km radius (for urban areas) from any existing public elementary/high school.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item10" name="item10"><label for="item10" class="checklist-text">10. Justification by the SDS for the waiver on the 2 or 1 km radius requirement, if necessary.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item11" name="item11"><label for="item11" class="checklist-text">11. Any document such as but not limited to Deed of Donation, Deed of Sale or Contract of Usufruct for 50 years executed in favor of DepED; Original Certificate of Title (OCT) or Transfer Certificate of Title (TCT) in the name of DepED, reflecting the size and boundaries of the school site.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item12" name="item12"><label for="item12" class="checklist-text">12. Justification from the SDS in case the required size of school site cannot be met.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item13" name="item13"><label for="item13" class="checklist-text">13. Clearance/permit from the provincial Mines and Geosciences Bureau (MGB) and the Regional Office of the Department of Environment and Natural Resources (DENR) stating that the proposed school site is not a high risk area.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item14" name="item14"><label for="item14" class="checklist-text">14. School site development plan.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item15" name="item15"><label for="item15" class="checklist-text">15. School building plan indicating the number and technical specifications of the classrooms to be built.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item16" name="item16"><label for="item16" class="checklist-text">16. School building design duly approved by DepED Education Facilities Division, Administrative Service.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item17" name="item17"><label for="item17" class="checklist-text">17. School building permit issued by the Municipal/City Engineer.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item18" name="item18"><label for="item18" class="checklist-text">18. Bureau of Fire Protection Certificate.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item19" name="item19"><label for="item19" class="checklist-text">19. Inspection Report from Division In-Charge of Facilities Section, in case classrooms are already constructed.</label></div>

      <div class="checklist-item"><input type="checkbox" id="item20" name="item20"><label for="item20" class="checklist-text">20. Duly notarized MOA* by and between DepED, represented by SDS, and LGU, represented by the Municipal/City Mayor or Provincial Governor, as the case may be, where the LGU shall provide funds for, among others, the following:</label></div>
      <div class="sub-item"><input type="checkbox" id="item20a" name="item20a"><label for="item20a">a. Construction of the new school building(s);</label></div>
      <div class="sub-item"><input type="checkbox" id="item20b" name="item20b"><label for="item20b">b. Procurement of educational facilities, furniture, textbooks and instructional materials;</label></div>
      <div class="sub-item"><input type="checkbox" id="item20c" name="item20c"><label for="item20c">c. Operation and maintenance for at least five (5) years or until such time when funds for the purpose are incorporated in the national budget; and</label></div>
      <div class="sub-item"><input type="checkbox" id="item20d" name="item20d"><label for="item20d">d. Salaries of teaching and non-teaching personnel, preferably at par with national salary rates.</label></div>

      <div class="checklist-item"><input type="checkbox" id="item21" name="item21"><label for="item21" class="checklist-text">21. Sangguniang Bayan/Panglalawigan/Panglungsod Resolution for the purpose.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item22" name="item22"><label for="item22" class="checklist-text">22. Certification from the Schools Division Superintendent that the Division Office has sufficient fund to cover resulting expenses, if any.</label></div>
      <div class="checklist-item"><input type="checkbox" id="item23" name="item23"><label for="item23" class="checklist-text">23. List of teaching and non-teaching personnel to be borrowed from the existing nearby school(s), duly identified by the respective Item Number per Personal Services Itemization and Plantilla of Personnel (PSIPOP) and name of school, if any.</label></div>
    </div>

    <div class="notes">
      <h4>Notes:</h4>
      <p>a. Please indicate N/A, if not applicable.</p>
      <p>b. Kindly submit all the lacking documents on or before
        <input type="text" name="submit_deadline" style="border:none; border-bottom:1px solid #000; width:150px;">
      </p>
      <p>c. Documents to be submitted must be two (2) sets, placed in separate folders, arranged following the sequence above and labeled appropriately.</p>
      <p>d. To facilitate evaluation, please do not include other documents not listed above.</p>
    </div>

    <!-- ✅ Encodable footer fields -->
    <div class="footer-section">
      <div class="receiver-box">
        <p><strong>Documents Received by:</strong></p>
        <input type="text" name="documents_received_by" style="width:100%; border:none; border-bottom:1px solid #000; padding:6px 0;">
        <div class="signature-line">(Signature over Printed Name and Designation)</div>
        <div class="date-line">
          <strong>Date:</strong>
          <input type="date" name="documents_received_date" style="border:none; border-bottom:1px solid #000; width:170px;">
        </div>
      </div>
    </div>

  </form>
</div>

<script>
const Config = {
  api: 'api.php',
  reportId: <?php echo (int)$reportId; ?>,
  canEdit: <?php echo $canEdit ? 'true' : 'false'; ?>,
  applicationType: <?php echo json_encode($applicationType, JSON_UNESCAPED_SLASHES); ?>
};

const App = {
  setPill: (type, text) => {
    const pill = document.getElementById('statusPill');
    pill.className = `pill ${type}`;
    pill.textContent = text;
  },

  load: async () => {
    try {
      const res = await fetch(`${Config.api}?action=get_checklist&report_id=${Config.reportId}`);
      const data = await res.json();
      if(!data.success) { App.setPill('warn', data.message || 'Load failed'); return; }

      if(data.checklist && data.checklist.data) {
        App.applyData(data.checklist.data);
        const last = data.checklist.updated_at || 'N/A';
        App.setPill('ok', `Loaded (Last update: ${last})`);
      } else {
        App.setPill('neutral', 'No saved checklist yet');
      }

      // lock if not editor
      App.lockIfNoEdit();
    } catch (e) {
      console.error(e);
      App.setPill('warn', 'Network/Server error');
    }
  },

  lockIfNoEdit: () => {
    if(Config.canEdit) return;

    document.querySelectorAll('#c1Form input, #c1Form textarea, #c1Form select')
      .forEach(el => el.disabled = true);

    const btn = document.getElementById('saveBtn');
    if(btn) btn.style.display = 'none';

    App.setPill('neutral', 'VIEW ONLY');
  },

  collectData: () => {
    const f = document.getElementById('c1Form');
    const obj = {};

    // text fields
    obj.requesting_office = f.requesting_office.value || '';
    obj.proponent_name = f.proponent_name.value || '';
    obj.position = f.position.value || '';
    obj.school_name = f.school_name.value || '';
    obj.address = f.address.value || '';
    obj.submit_deadline = f.submit_deadline.value || '';

    // footer fields
    obj.documents_received_by = f.documents_received_by.value || '';
    obj.documents_received_date = f.documents_received_date.value || '';

    // checklist items
    const checkboxNames = [
      'item1','item2','item2a','item2b','item2c','item2d','item2e',
      'item3','item4','item5','item6','item7a','item7b','item8','item9','item10',
      'item11','item12','item13','item14','item15','item16','item17','item18','item19',
      'item20','item20a','item20b','item20c','item20d','item21','item22','item23'
    ];
    checkboxNames.forEach(k => obj[k] = !!f[k]?.checked);

    return obj;
  },

  applyData: (saved) => {
    const f = document.getElementById('c1Form');

    // text fields
    f.requesting_office.value = saved.requesting_office || '';
    f.proponent_name.value = saved.proponent_name || '';
    f.position.value = saved.position || '';
    f.school_name.value = saved.school_name || '';
    f.address.value = saved.address || '';
    f.submit_deadline.value = saved.submit_deadline || '';

    // footer fields
    f.documents_received_by.value = saved.documents_received_by || '';
    f.documents_received_date.value = saved.documents_received_date || '';

    // checkboxes
    Object.keys(saved).forEach(k => {
      if(f[k] && f[k].type === 'checkbox') f[k].checked = !!saved[k];
    });
  },

  save: async () => {
    if(!Config.canEdit) return alert("View only.");

    const payload = {
      report_id: Config.reportId,
      data: App.collectData(),
      has_missing: false
    };

    try {
      const res = await fetch(`${Config.api}?action=save_checklist`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
      });
      const d = await res.json();
      if(d.success) App.setPill('ok', 'Saved successfully');
      else App.setPill('warn', d.message || 'Save failed');
    } catch (e) {
      console.error(e);
      App.setPill('warn', 'Network/Server error');
    }
  }
};

App.load();
</script>

</body>
</html>
