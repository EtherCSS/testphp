
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$userPayload = json_encode([
  'id'   => (int)($_SESSION['user']['id'] ?? 0),
  'name' => (string)($_SESSION['user']['name'] ?? 'User'),
  'role' => (string)($_SESSION['user']['role'] ?? 'school'),
  'sdo'  => (string)($_SESSION['user']['sdo'] ?? 'Unassigned')
], JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>i-QAD | Region VIII</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
:root{
  --primary:#0f4c81; --primary-dark:#0a355c; --accent:#fbbf24;
  --success:#10b981; --danger:#ef4444; --warning:#f59e0b; --info:#3b82f6;
  --bg:#f8fafc; --surface:#ffffff; --text:#0f172a; --muted:#64748b; --border:#e2e8f0;
  --radius:14px; --sidebar:290px;

  --shadow-sm: 0 1px 2px rgba(2,6,23,.06), 0 10px 22px rgba(2,6,23,.06);
  --shadow-md: 0 3px 10px rgba(2,6,23,.10), 0 18px 50px rgba(2,6,23,.12);
}

*{box-sizing:border-box}
body{
  margin:0;
  font-family:'Plus Jakarta Sans',sans-serif;
  background:linear-gradient(180deg, #f8fafc, #f3f7ff 60%, #f8fafc);
  color:var(--text);
  height:100vh;
  display:flex;
  overflow:hidden
}
aside{width:var(--sidebar);background:#0b1220;color:white;display:flex;flex-direction:column}
main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* ✅ BRAND AREA */
.brand{
  height:72px;
  display:flex;
  align-items:center;
  gap:12px;
  padding:0 18px;
  border-bottom:1px solid rgba(255,255,255,.08)
}
.brand-logo{
  width:42px;height:42px;
  border-radius:14px;
  background:#ffffff;
  padding:6px;
  object-fit:contain;
  box-shadow: 0 10px 25px rgba(0,0,0,.18);
}
.badge-r8{
  margin-left:auto;
  width:42px;height:42px;
  border-radius:16px;
  background:var(--accent);
  display:grid;
  place-items:center;
  color:#111827;
  font-weight:1000;
  box-shadow:0 14px 35px rgba(251,191,36,.16);
}
.brand .title{font-weight:1000;line-height:1}
.brand small{opacity:.65}

.nav{flex:1;padding:14px 10px;overflow:auto}
.nav-group-label{
  padding:10px 14px 6px;
  font-size:.72rem;
  font-weight:1000;
  color:#93a4bf;
  text-transform:uppercase;
  letter-spacing:.12em;
  opacity:.9;
}
.nav-item{
  display:flex;align-items:center;gap:12px;
  padding:12px 14px;border-radius:12px;
  color:#94a3b8;font-weight:800;cursor:pointer;transition:.2s;margin-bottom:6px;
}
.nav-item:hover{background:rgba(251,191,36,.14);color:#fff}
.nav-item.active{background:var(--accent);color:#111827}
.nav-item .spacer{flex:1}
.nav-badge{
  min-width:24px;
  height:22px;
  padding:0 8px;
  border-radius:999px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:.72rem;
  font-weight:1000;
  background:rgba(255,255,255,.12);
  color:#fff;
}
.nav-item.active .nav-badge{
  background:rgba(17,24,39,.12);
  color:#111827;
}
.hidden{display:none!important}

.userbox{padding:16px;border-top:1px solid rgba(255,255,255,.08)}
.userrow{display:flex;align-items:center;gap:10px}
.avatar{width:38px;height:38px;border-radius:12px;background:#1f2a44;display:grid;place-items:center;font-weight:900}
.userrow b{font-size:.92rem}
.userrow small{opacity:.65}

header{
  height:72px;
  background:rgba(255,255,255,.75);
  backdrop-filter: blur(10px);
  border-bottom:1px solid rgba(226,232,240,.9);
  display:flex;align-items:center;justify-content:space-between;padding:0 22px
}
header h2{margin:0;font-size:1.15rem;color:var(--primary);font-weight:900}
.header-actions{display:flex;gap:10px;align-items:center}

.content{
  padding:22px;
  overflow:auto;
  height:calc(100vh - 72px)
}

.card{
  position:relative;
  background: rgba(255,255,255,.90);
  border: 1px solid rgba(226,232,240,.95);
  border-radius: calc(var(--radius) + 2px);
  padding: 18px;
  box-shadow: 0 2px 10px rgba(2,6,23,.06), 0 22px 60px rgba(2,6,23,.08);
  overflow:hidden;
  margin-bottom:12px;
}
.card::before{
  content:"";
  position:absolute;
  inset:-2px;
  background: radial-gradient(650px 220px at 0% 0%, rgba(15,76,129,.14), transparent 60%),
              radial-gradient(650px 220px at 100% 0%, rgba(251,191,36,.12), transparent 55%);
  pointer-events:none;
}
.card > *{ position:relative; z-index:1; }

.grid{display:grid;gap:10px}

/* ✅ space between Dashboard stats and Recent Submissions */
#view-dashboard{
  display:flex;
  flex-direction:column;
  gap:18px;
}
#view-dashboard .card{ margin-bottom:0; }

/* status cards */
.grid.stats{ grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); }

.stat{
  --c: var(--primary);
  position:relative;
  overflow:hidden;
  border-radius: calc(var(--radius) + 1px);
  border:1px solid rgba(226,232,240,.92);
  background: rgba(255,255,255,.86);
  box-shadow: var(--shadow-sm);
  padding: 12px 12px 10px;
  transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
  min-height: 92px;
}
.stat:hover{
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
  border-color:#cbd5e1;
}
.stat::before{
  content:"";
  position:absolute; left:0; top:0; bottom:0;
  width:5px;
  background: var(--c);
}
.stat::after{
  content:"";
  position:absolute;
  inset:0;
  background: radial-gradient(620px 200px at 10% 0%, rgba(15,76,129,.10), transparent 55%);
  opacity:.85;
  pointer-events:none;
}
.stat .row{
  position:relative;
  z-index:1;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}
.stat .label{
  font-size:.70rem;
  text-transform:uppercase;
  letter-spacing:.10em;
  font-weight:900;
  color: var(--muted);
  line-height:1.25;
}
.stat .value{
  margin-top:6px;
  font-size:1.75rem;
  font-weight:900;
  letter-spacing:-.03em;
  color:#0f172a;
  line-height:1;
}
.stat .sub{
  margin-top:6px;
  font-size:.80rem;
  color:var(--muted);
}
.stat .ic{
  width:34px;height:34px;border-radius:12px;
  display:grid;place-items:center;
  color: var(--c);
  background: rgba(15,76,129,.10);
  box-shadow: inset 0 0 0 1px rgba(15,76,129,.10);
  flex: 0 0 auto;
}
@supports (background: color-mix(in srgb, red 10%, white)) {
  .stat .ic{
    background: color-mix(in srgb, var(--c) 16%, white);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--c) 18%, white);
  }
}
.stat .ic i{ font-size:18px; }

/* colors */
.stat.pending{ --c:#f59e0b; }
.stat.received{ --c:#64748b; }
.stat.forwarded{ --c:#3b82f6; }
.stat.processing{ --c:#3b82f6; }
.stat.compliance{ --c:#f59e0b; }
.stat.inspection{ --c:#8b5cf6; }
.stat.approved{ --c:#10b981; }
.stat.disapproved{ --c:#ef4444; }
.stat.schools{ --c:#0f4c81; }

/* table, buttons */
.table{width:100%;border-collapse:collapse}
.table th{background:#f1f5f9;text-align:left;padding:10px;font-size:.70rem;text-transform:uppercase;color:var(--muted);font-weight:900}
.table td{padding:10px;border-bottom:1px solid var(--border);font-size:.88rem;vertical-align:middle}

.pill{display:inline-block;padding:5px 10px;border-radius:10px;font-size:.72rem;font-weight:900;color:white}
.btn{border:none;border-radius:12px;padding:9px 11px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.btn-primary{background:var(--primary);color:white}
.btn-ghost{background:#f1f5f9;color:#111827}
.btn-danger{background:#ef4444;color:white}
.btn-sm{padding:6px 10px;border-radius:10px;font-size:.78rem}

.input,.select,textarea{
  width:100%;padding:9px 11px;border-radius:12px;border:1px solid var(--border);
  font-family:inherit;background:white
}
textarea{min-height:90px;resize:vertical}

.toastbox{position:fixed;top:16px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:10px}
.toast{background:white;border:1px solid var(--border);border-left:5px solid var(--success);padding:12px 14px;border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,.12);min-width:280px}
.toast b{display:block;margin-bottom:2px}

.modal{position:fixed;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(5px);display:none;place-items:center;z-index:999}
.modal .box{width:100%;max-width:520px;background:white;border-radius:18px;padding:18px;border:1px solid var(--border);box-shadow:0 30px 80px rgba(0,0,0,.25)}
.modal .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
hr{border:none;border-top:1px solid var(--border);margin:12px 0}

/* notifications popup */
#notif-panel{
  position:fixed;
  top:80px;
  right:20px;
  width:380px;
  max-height:520px;
  background:white;
  border-radius:16px;
  border:1px solid var(--border);
  box-shadow:0 25px 60px rgba(0,0,0,.2);
  overflow:auto;
  display:none;
  z-index:9999;
}

/* step banner */
.step-banner{
  background:#dbeafe;
  border:1px solid #bfdbfe;
  border-radius:18px;
  padding:14px;
  margin-bottom:14px;
}
.step-title{font-weight:900;color:#0f172a;font-size:1.05rem;margin-bottom:10px}
.step-inner{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:14px 16px}
.step-inner-title{font-weight:900;letter-spacing:.4px;margin-bottom:8px}
.step-list{margin:0;padding-left:18px;color:#0f172a}
.step-list li{margin:6px 0}

/* ✅ Type of Application badge */
.app-type{
  display:inline-flex;
  align-items:center;
  gap:8px;
  margin-bottom:6px;
  padding:6px 10px;
  border-radius:999px;
  background:#eef2ff;
  border:1px solid #e0e7ff;
  color:#3730a3;
  font-weight:1000;
  font-size:.72rem;
}
.app-type b{color:#111827}
@media (max-width: 640px){
  .app-type{display:none}
}

/* ✅ tabs */
.tabs-row{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-top:10px;
}
.tabbtn{
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  border-radius:14px;
  padding:8px 10px;
  font-weight:1000;
  cursor:pointer;
  font-size:.78rem;
  display:inline-flex;
  gap:8px;
  align-items:center;
}
.tabbtn.active{
  background:var(--primary);
  border-color:var(--primary);
  color:#fff;
}
.tabbtn .badge{
  min-width:22px;
  height:20px;
  padding:0 8px;
  border-radius:999px;
  font-size:.72rem;
  font-weight:1000;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:#eef2ff;
  color:#1e3a8a;
}
.tabbtn.active .badge{
  background:rgba(255,255,255,.20);
  color:#fff;
}

/* ✅ simple KPI cards in analytics */
.kpi-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
.kpi{
  background:#fff;border:1px solid rgba(226,232,240,.95);
  border-radius:18px;padding:14px;
  box-shadow:0 12px 35px rgba(2,6,23,.06);
}
.kpi .k{font-size:.72rem;text-transform:uppercase;letter-spacing:.12em;color:var(--muted);font-weight:1000}
.kpi .v{margin-top:6px;font-weight:1000;font-size:1.35rem}

/* =========================
   ✅ MODERN CHAT UI (your existing)
   ========================= */
#chat-fab{
  position:fixed; right:18px; bottom:18px;
  width:58px; height:58px; border-radius:18px;
  border:0; cursor:pointer;
  background: linear-gradient(135deg, var(--primary), #123e68);
  color:#fff; box-shadow:0 18px 55px rgba(2,6,23,.35);
  z-index:10000;
  display:flex; align-items:center; justify-content:center;
}
#chat-fab i{font-size:22px}
#chat-badge{
  position:absolute; top:-6px; right:-6px;
  background:#ef4444; color:#fff;
  font-size:.72rem; padding:2px 7px;
  border-radius:999px; display:none;
  box-shadow:0 10px 25px rgba(0,0,0,.25);
}

#chat-panel{
  position:fixed; right:18px; bottom:90px;
  width:420px; max-width:calc(100vw - 30px);
  height:600px; max-height:calc(100vh - 120px);
  background: rgba(255,255,255,.92);
  border:1px solid rgba(226,232,240,.95);
  border-radius:22px;
  box-shadow:0 35px 110px rgba(2,6,23,.28);
  overflow:hidden; display:none; z-index:10000;
  backdrop-filter: blur(12px);
}

#chat-panel .chat-top{
  height:60px;
  padding:0 14px;
  display:flex; align-items:center; justify-content:space-between;
  border-bottom:1px solid rgba(226,232,240,.95);
  background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.75));
}
.chat-top .title{
  display:flex; align-items:center; gap:10px;
  font-weight:1000; color:var(--primary);
  letter-spacing:.2px;
}
.chat-top .title .dot{
  width:10px; height:10px; border-radius:50%;
  background:var(--accent);
  box-shadow:0 0 0 6px rgba(251,191,36,.18);
}
.chat-top .actions{display:flex; gap:8px; align-items:center}
.chat-top .actions button{
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  border-radius:14px;
  padding:8px 10px;
  cursor:pointer;
  font-weight:900;
}
.chat-top .actions button:hover{background:#f8fafc}

#chat-panel .chat-body{
  display:grid;
  grid-template-columns: 160px 1fr;
  height:calc(100% - 60px);
}

.chat-left{
  border-right:1px solid rgba(226,232,240,.95);
  background: rgba(248,250,252,.8);
  display:flex; flex-direction:column;
}
.chat-left .tabs{
  display:flex; gap:8px;
  padding:10px;
}
.chat-left .tab{
  flex:1;
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  border-radius:14px;
  padding:8px 10px;
  font-weight:1000;
  cursor:pointer;
  font-size:.78rem;
}
.chat-left .tab.active{
  background:var(--primary);
  color:#fff;
  border-color:var(--primary);
}

.chat-left .search{padding:0 10px 10px}
.chat-left .search input{
  width:100%;
  border:1px solid rgba(226,232,240,.95);
  border-radius:14px;
  padding:9px 10px;
  font-family:inherit;
  background:#fff;
}

.chat-list{flex:1; overflow:auto; padding:6px 8px 12px}
.chat-item{
  display:flex;
  gap:10px;
  align-items:center;
  border:1px solid transparent;
  background:#fff;
  border-radius:16px;
  padding:10px 10px;
  cursor:pointer;
  margin:7px 2px;
  box-shadow:0 1px 0 rgba(2,6,23,.04);
}
.chat-item:hover{border-color:#cbd5e1}
.chat-item.active{
  border-color:rgba(15,76,129,.35);
  box-shadow:0 18px 55px rgba(15,76,129,.12);
}
.chat-avatar{
  width:34px; height:34px;
  border-radius:14px;
  display:grid; place-items:center;
  background: rgba(15,76,129,.10);
  color:var(--primary);
  font-weight:1000;
  flex:0 0 auto;
}
.chat-item b{display:block;font-size:.86rem; line-height:1.05}
.chat-item small{color:var(--muted);display:block;font-size:.72rem;margin-top:2px}
.chat-item .unread{
  margin-left:auto;
  font-size:.70rem;
  background:#fee2e2;
  color:#991b1b;
  padding:2px 8px;
  border-radius:999px;
  font-weight:1000;
}

.chat-right{display:flex; flex-direction:column}
.chat-peerbar{
  padding:12px 14px;
  border-bottom:1px solid rgba(226,232,240,.95);
  display:flex; align-items:center; justify-content:space-between;
  background:#fff;
}
.chat-peerbar .peer{
  font-weight:1000;
  color:#0f172a;
}
.chat-peerbar .peer small{display:block;color:var(--muted);font-weight:900;font-size:.72rem}

.chat-messages{
  flex:1;
  overflow:auto;
  padding:14px;
  background: linear-gradient(180deg, #f8fafc, #ffffff);
}

.msg-row{display:flex; margin:10px 0}
.msg-row.me{justify-content:flex-end}
.bubble{
  max-width:78%;
  padding:10px 12px;
  border-radius:18px;
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  box-shadow:0 12px 30px rgba(2,6,23,.06);
  font-size:.88rem;
  line-height:1.35;
  white-space:pre-wrap;
  word-break:break-word;
}
.msg-row.me .bubble{
  background: linear-gradient(180deg, rgba(15,76,129,.16), rgba(15,76,129,.10));
  border-color:rgba(15,76,129,.18);
}
.bubble .meta{
  margin-top:6px;
  font-size:.70rem;
  color:var(--muted);
  font-weight:900;
}

.chat-compose{
  padding:10px;
  border-top:1px solid rgba(226,232,240,.95);
  background:#fff;
  display:flex; gap:8px; align-items:flex-end;
}
.chat-compose textarea{
  flex:1;
  border:1px solid rgba(226,232,240,.95);
  border-radius:16px;
  padding:11px 12px;
  font-family:inherit;
  background:#fff;
  resize:none;
  min-height:44px;
  max-height:120px;
  overflow:auto;
}
.chat-compose button{
  border:0;
  border-radius:16px;
  padding:11px 14px;
  background: linear-gradient(135deg, var(--primary), #123e68);
  color:#fff;
  font-weight:1000;
  cursor:pointer;
}
.chat-compose button:hover{filter:brightness(1.03)}
.chat-compose button:disabled{opacity:.55; cursor:not-allowed}

.chat-empty{padding:18px; color:var(--muted); font-weight:900}
</style>
</head>

<body>
<aside>
  <div class="brand">
    <img class="brand-logo" src="RO-VIII.png" alt="i-QADs">
    <div>
      <div class="title">i-QAD Portal</div>
      <small>DepEd Region VIII</small>
    </div>
    <div class="badge-r8">R8</div>
  </div>

  <div class="nav">
    <div class="nav-group-label">Core</div>
    <div class="nav-item active" id="nav-dashboard" onclick="UI.switchView('dashboard')">
      <i class="ph-bold ph-squares-four"></i> Dashboard
      <span class="spacer"></span>
    </div>

    <!-- ✅ NEW: Applications / Reports -->
    <div class="nav-item" id="nav-applications" onclick="UI.switchView('applications')">
      <i class="ph-bold ph-files"></i> Applications
      <span class="spacer"></span>
      <span class="nav-badge" id="nav-badge-active">0</span>
    </div>

    <!-- ✅ NEW: Tasks / Assignments -->
    <div class="nav-item hidden" id="nav-tasks" onclick="UI.switchView('tasks')">
      <i class="ph-bold ph-check-square-offset"></i> My Tasks
      <span class="spacer"></span>
      <span class="nav-badge" id="nav-badge-my">0</span>
    </div>

    <!-- ✅ NEW: Ocular Schedule -->
    <div class="nav-item" id="nav-ocular" onclick="UI.switchView('ocular')">
      <i class="ph-bold ph-calendar-check"></i> Ocular Schedule
      <span class="spacer"></span>
      <span class="nav-badge" id="nav-badge-ocular">0</span>
    </div>

    <!-- ✅ RD Decisions -->
    <div class="nav-item hidden" id="nav-rd" onclick="UI.switchView('rd')">
      <i class="ph-bold ph-gavel"></i> RD Decisions
      <span class="spacer"></span>
      <span class="nav-badge" id="nav-badge-forwarded">0</span>
    </div>

    <!-- ✅ Notifications full page -->
    <div class="nav-item" id="nav-notifications" onclick="UI.switchView('notifications')">
      <i class="ph-bold ph-bell"></i> Notifications
      <span class="spacer"></span>
      <span class="nav-badge" id="nav-badge-notif">0</span>
    </div>

    <div class="nav-group-label">Management</div>
    <div class="nav-item hidden" id="nav-upload" onclick="UI.openModal('upload-modal')"><i class="ph-bold ph-upload-simple"></i> Upload Report</div>
    <div class="nav-item hidden" id="nav-eval" onclick="UI.switchView('eval')"><i class="ph-bold ph-file-plus"></i> New Application</div>
    <div class="nav-item hidden" id="nav-users" onclick="UI.switchView('users')"><i class="ph-bold ph-users"></i> Users</div>

    <div class="nav-item" id="nav-map" onclick="UI.switchView('map')"><i class="ph-bold ph-map-trifold"></i> GIS Map</div>
    <div class="nav-item" id="nav-schools" onclick="UI.switchView('schools')"><i class="ph-bold ph-buildings"></i> Schools</div>

    <!-- ✅ Nice adds (mostly computed UI; backend optional) -->
    <div class="nav-item" id="nav-analytics" onclick="UI.switchView('analytics')"><i class="ph-bold ph-chart-line"></i> Analytics</div>
    <div class="nav-item hidden" id="nav-audit" onclick="UI.switchView('audit')"><i class="ph-bold ph-shield-check"></i> Audit Log</div>
    <div class="nav-item" id="nav-templates" onclick="UI.switchView('templates')"><i class="ph-bold ph-download-simple"></i> Templates</div>
    <div class="nav-item" id="nav-help" onclick="UI.switchView('help')"><i class="ph-bold ph-question"></i> Help</div>

    <div class="nav-item" id="nav-settings" onclick="UI.switchView('settings')"><i class="ph-bold ph-gear"></i> Settings</div>
  </div>

  <div class="userbox">
    <div class="userrow">
      <div class="avatar" id="u-initial">U</div>
      <div style="flex:1">
        <b id="u-name">Loading...</b><br>
        <small id="u-role">...</small>
      </div>
      <button class="btn btn-ghost btn-sm" title="Logout" onclick="App.logout()"><i class="ph-bold ph-sign-out"></i></button>
    </div>
  </div>
</aside>

<main>
  <header>
    <h2 id="page-title">Dashboard</h2>
    <div class="header-actions" id="header-actions"></div>
  </header>

  <div class="content">

    <!-- DASHBOARD -->
    <section id="view-dashboard" class="view">
      <div class="grid stats">

        <!-- ✅ ADDED: Pending Review -->
        <div class="stat pending">
          <div class="row">
            <div>
              <div class="label">PENDING REVIEW</div>
              <div class="value" id="stat-pending">0</div>
              <div class="sub">New / for screening</div>
            </div>
            <div class="ic"><i class="ph-bold ph-hourglass-simple"></i></div>
          </div>
        </div>

        <div class="stat received">
          <div class="row">
            <div>
              <div class="label">RECEIVED</div>
              <div class="value" id="stat-received">0</div>
              <div class="sub">Reports received</div>
            </div>
            <div class="ic"><i class="ph-bold ph-inbox"></i></div>
          </div>
        </div>

        <!-- ✅ ADDED: Forwarded to RD -->
        <div class="stat forwarded">
          <div class="row">
            <div>
              <div class="label">FORWARDED TO RD</div>
              <div class="value" id="stat-forwarded">0</div>
              <div class="sub">Awaiting RD decision</div>
            </div>
            <div class="ic"><i class="ph-bold ph-paper-plane-tilt"></i></div>
          </div>
        </div>

        <div class="stat processing">
          <div class="row">
            <div>
              <div class="label">PROCESSING</div>
              <div class="value" id="stat-processing">0</div>
              <div class="sub">Under review</div>
            </div>
            <div class="ic"><i class="ph-bold ph-gear-six"></i></div>
          </div>
        </div>

        <div class="stat compliance">
          <div class="row">
            <div>
              <div class="label">PENDING FOR COMPLIANCE OF LACKING REQUIREMENT</div>
              <div class="value" id="stat-compliance">0</div>
              <div class="sub">Waiting documents</div>
            </div>
            <div class="ic"><i class="ph-bold ph-warning-circle"></i></div>
          </div>
        </div>

        <div class="stat inspection">
          <div class="row">
            <div>
              <div class="label">FOR OCULAR INSPECTION</div>
              <div class="value" id="stat-inspection">0</div>
              <div class="sub">Schedule / on-site</div>
            </div>
            <div class="ic"><i class="ph-bold ph-eye"></i></div>
          </div>
        </div>

        <div class="stat approved">
          <div class="row">
            <div>
              <div class="label">APPROVED</div>
              <div class="value" id="stat-approved">0</div>
              <div class="sub">Completed</div>
            </div>
            <div class="ic"><i class="ph-bold ph-check-circle"></i></div>
          </div>
        </div>

        <div class="stat disapproved">
          <div class="row">
            <div>
              <div class="label">DISAPPROVED</div>
              <div class="value" id="stat-disapproved">0</div>
              <div class="sub">Closed</div>
            </div>
            <div class="ic"><i class="ph-bold ph-x-circle"></i></div>
          </div>
        </div>

        <div class="stat schools">
          <div class="row">
            <div>
              <div class="label">TOTAL Division</div>
              <div class="value" id="stat-schools">0</div>
              <div class="sub">In database</div>
            </div>
            <div class="ic"><i class="ph-bold ph-buildings"></i></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">Recent Submissions</h3>
            <div style="color:var(--muted);font-size:.86rem">Role-based visibility</div>
          </div>
          <input class="input" id="report-search" placeholder="Search title / SDO / status..." style="max-width:320px">
        </div>
        <hr>
        <div style="overflow:auto">
          <table class="table">
            <thead>
              <tr>
                <th>Title</th><th>Owner (SDO)</th><th>Date</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="table-body"></tbody>
          </table>
        </div>
        <div id="empty-state" class="hidden" style="text-align:center;color:var(--muted);padding:18px;">No records found.</div>
      </div>
    </section>

    <!-- ✅ NEW: APPLICATIONS / REPORTS (tabs + badges) -->
    <section id="view-applications" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">Applications / Reports</h3>
            <div style="color:var(--muted);font-size:.86rem">Browse by workflow status</div>
          </div>
          <input class="input" id="apps-search" placeholder="Search title / SDO / owner..." style="max-width:360px">
        </div>

        <div class="tabs-row" id="apps-tabs"></div>
        <hr>

        <div style="overflow:auto">
          <table class="table">
            <thead>
              <tr>
                <th>Title</th><th>Owner (SDO)</th><th>Assigned</th><th>Date</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="apps-body"></tbody>
          </table>
        </div>
        <div id="apps-empty" class="hidden" style="text-align:center;color:var(--muted);padding:18px;">No records found.</div>
      </div>
    </section>

    <!-- ✅ NEW: TASKS / ASSIGNMENTS -->
    <section id="view-tasks" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">My Assigned Tasks</h3>
            <div style="color:var(--muted);font-size:.86rem">Assigned reports with due/overdue indicator</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" id="tasks-filter" style="max-width:260px">
              <option value="all">All statuses</option>
              <option value="open">Open only</option>
              <option value="overdue">Overdue only</option>
            </select>
            <button class="btn btn-ghost" onclick="App.renderTasks()"><i class="ph-bold ph-arrow-clockwise"></i> Refresh</button>
          </div>
        </div>
        <hr>
        <div style="overflow:auto">
          <table class="table">
            <thead>
              <tr>
                <th>Title</th><th>Owner (SDO)</th><th>Due</th><th>Priority</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="tasks-body"></tbody>
          </table>
        </div>
        <div id="tasks-empty" class="hidden" style="text-align:center;color:var(--muted);padding:18px;">No assigned tasks.</div>
      </div>
    </section>

    <!-- ✅ NEW: OCULAR SCHEDULE -->
    <section id="view-ocular" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">Ocular Schedule</h3>
            <div style="color:var(--muted);font-size:.86rem">Inspection list (schedule/reschedule/mark completed)</div>
          </div>
          <input class="input" id="ocular-search" placeholder="Search title / SDO..." style="max-width:320px">
        </div>
        <hr>

        <div style="overflow:auto">
          <table class="table">
            <thead>
              <tr>
                <th>Title</th><th>Owner (SDO)</th><th>Schedule</th><th>Notes</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="ocular-body"></tbody>
          </table>
        </div>
        <div id="ocular-empty" class="hidden" style="text-align:center;color:var(--muted);padding:18px;">No ocular items.</div>
      </div>
    </section>

    <!-- ✅ NEW: RD DECISIONS -->
    <section id="view-rd" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">RD Decisions</h3>
            <div style="color:var(--muted);font-size:.86rem">Forwarded items awaiting decision</div>
          </div>
          <input class="input" id="rd-search" placeholder="Search title / SDO..." style="max-width:320px">
        </div>
        <hr>

        <div style="overflow:auto">
          <table class="table">
            <thead>
              <tr>
                <th>Title</th><th>Owner (SDO)</th><th>Date</th><th>Status</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="rd-body"></tbody>
          </table>
        </div>
        <div id="rd-empty" class="hidden" style="text-align:center;color:var(--muted);padding:18px;">No forwarded items.</div>
      </div>
    </section>

    <!-- ✅ NEW: NOTIFICATIONS PAGE -->
    <section id="view-notifications" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <div>
            <h3 style="margin:0;color:var(--primary);font-weight:900">Notifications</h3>
            <div style="color:var(--muted);font-size:.86rem">Inbox with filters</div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" id="notif-filter" style="max-width:260px">
              <option value="all">All</option>
              <option value="pending">Pending Review</option>
              <option value="forwarded">Forwarded to RD</option>
              <option value="inspection">Ocular</option>
              <option value="approved">Approved</option>
              <option value="disapproved">Disapproved</option>
            </select>
            <button class="btn btn-ghost" onclick="Notifications.loadFull()"><i class="ph-bold ph-arrow-clockwise"></i> Refresh</button>
          </div>
        </div>
        <hr>
        <div id="notif-full-body"></div>
      </div>
    </section>

    <!-- NEW APPLICATION (SDO only) -->
    <section id="view-eval" class="view hidden">
      <div class="card" style="max-width:900px;margin:0 auto">

        <div class="step-banner">
          <div class="step-title">Step 1: SDO - Encoding &amp; Initial Review</div>
          <div class="step-inner">
            <div class="step-inner-title">NEW APPLICATION</div>
            <ul class="step-list">
              <li>Choose Type of Application</li>
              <li>School Profile / Document Information</li>
              <li>Findings during On-Site Inspection</li>
            </ul>
          </div>
        </div>

        <h3 style="margin:0;color:var(--primary);font-weight:900">New School Application (SDO)</h3>
        <div style="color:var(--muted);font-size:.9rem;margin-top:4px">Encoding + Findings + Recommendations</div>
        <hr>

        <form onsubmit="App.submitEvaluation(event)">
          <label style="font-weight:900;font-size:.8rem">Type of Application</label>
          <select class="select" name="application_type" required>
            <option value="" selected disabled>Select...</option>
            <option value="Establishment">Establishment</option>
            <option value="Merging">Merging</option>
            <option value="Conversion">Conversion</option>
            <option value="Separation">Separation</option>
          </select>

          <div class="grid" style="grid-template-columns:1fr 1fr">
            <div>
              <label style="font-weight:900;font-size:.8rem">Division (SDO)</label>
              <input class="input" name="division" id="eval-division" readonly style="background:#f1f5f9">
            </div>
            <div>
              <label style="font-weight:900;font-size:.8rem">School ID (If available)</label>
              <input class="input" name="school_id" placeholder="e.g. 123456">
            </div>
          </div>

          <label style="font-weight:900;font-size:.8rem">Name of Proposed School</label>
          <input class="input" name="school_name" required>

          <label style="font-weight:900;font-size:.8rem">Complete Address</label>
          <input class="input" name="address" required>

          <div class="grid" style="grid-template-columns:1fr 1fr">
            <div>
              <label style="font-weight:900;font-size:.8rem">Contact Number</label>
              <input class="input" name="contact">
            </div>
            <div>
              <label style="font-weight:900;font-size:.8rem">Email Address</label>
              <input class="input" type="email" name="email">
            </div>
          </div>

          <div class="grid" style="grid-template-columns:1fr 1fr">
            <div>
              <label style="font-weight:900;font-size:.8rem">Program Applied For</label>
              <select class="select" name="program">
                <option value="">Select...</option>
                <option>Kindergarten</option>
                <option>Elementary</option>
                <option>Junior High School</option>
                <option>Senior High School</option>
                <option>Integrated School</option>
              </select>
            </div>
            <div>
              <label style="font-weight:900;font-size:.8rem">School Year</label>
              <input class="input" name="school_year" placeholder="e.g. 2026-2027">
            </div>
          </div>

          <hr>
          <h4 style="margin:0;color:var(--primary);font-weight:900">Findings & Observations</h4>

          <label style="font-weight:800">1. Educational gaps / access issues</label>
          <textarea class="input" name="q1" required></textarea>

          <label style="font-weight:800">2. Ensuring equitable access</label>
          <textarea class="input" name="q2" required></textarea>

          <label style="font-weight:800">3. Community/LGU support evidence</label>
          <textarea class="input" name="q3" required></textarea>

          <label style="font-weight:800">4. Site compliance (area, safety, ownership)</label>
          <textarea class="input" name="q4" required></textarea>

          <label style="font-weight:800">5. Facilities available + needed</label>
          <textarea class="input" name="q5" required></textarea>

          <label style="font-weight:800">6. Projected enrollment</label>
          <textarea class="input" name="q6" required></textarea>

          <label style="font-weight:800">7. Personnel requirements</label>
          <textarea class="input" name="q7" required></textarea>

          <label style="font-weight:800">8. Resources + sources of support</label>
          <textarea class="input" name="q8" required></textarea>

          <label style="font-weight:800">9. Sustainability + compliance plan</label>
          <textarea class="input" name="q9" required></textarea>

          <hr>
          <h4 style="margin:0;color:var(--primary);font-weight:900">Recommendations</h4>
          <textarea class="input" name="recommendations" required placeholder="1. ..."></textarea>

          <div style="display:flex;justify-content:flex-end;margin-top:14px;">
            <button class="btn btn-primary" type="submit"><i class="ph-bold ph-paper-plane-right"></i> Submit Evaluation</button>
          </div>
        </form>
      </div>
    </section>

    <!-- USERS -->
    <section id="view-users" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
          <h3 style="margin:0;color:var(--primary);font-weight:900">User Accounts</h3>
          <button class="btn btn-primary" onclick="UI.openModal('user-modal')"><i class="ph-bold ph-plus"></i> Add User</button>
        </div>
        <hr>
        <div style="overflow:auto">
          <table class="table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>SDO / Division</th></tr></thead>
            <tbody id="users-body"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- MAP -->
    <section id="view-map" class="view hidden">
      <div class="card" style="padding:0;height:75vh;overflow:hidden">
        <div id="map" style="width:100%;height:100%"></div>
      </div>
    </section>

    <!-- SCHOOLS -->
    <section id="view-schools" class="view hidden">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
          <h3 style="margin:0;color:var(--primary);font-weight:900">School Database</h3>
          <input class="input" id="school-search" placeholder="Search schools..." style="max-width:320px">
        </div>
        <hr>
        <div style="overflow:auto">
          <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>Level</th><th>Division</th></tr></thead>
            <tbody id="school-body"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ✅ ANALYTICS -->
    <section id="view-analytics" class="view hidden">
      <div class="card">
        <h3 style="margin:0;color:var(--primary);font-weight:900">Analytics / Reports</h3>
        <div style="color:var(--muted);font-size:.86rem;margin-top:4px">Computed from your current dataset</div>
        <hr>

        <div class="kpi-grid" id="analytics-kpis"></div>
        <hr>

        <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div class="card" style="margin:0">
            <h4 style="margin:0;color:var(--primary);font-weight:1000">Approvals per Month</h4>
            <div style="color:var(--muted);font-size:.82rem;margin-top:4px">Approved + Disapproved counts</div>
            <hr>
            <div style="overflow:auto">
              <table class="table">
                <thead><tr><th>Month</th><th>Approved</th><th>Disapproved</th></tr></thead>
                <tbody id="analytics-months"></tbody>
              </table>
            </div>
          </div>

          <div class="card" style="margin:0">
            <h4 style="margin:0;color:var(--primary);font-weight:1000">Per Division Summary</h4>
            <div style="color:var(--muted);font-size:.82rem;margin-top:4px">Total and active workload</div>
            <hr>
            <div style="overflow:auto">
              <table class="table">
                <thead><tr><th>Division</th><th>Total</th><th>Active</th><th>Approved</th><th>Disapproved</th></tr></thead>
                <tbody id="analytics-divisions"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ✅ AUDIT LOG (placeholder unless backend exists) -->
    <section id="view-audit" class="view hidden">
      <div class="card">
        <h3 style="margin:0;color:var(--primary);font-weight:900">Audit Log</h3>
        <div style="color:var(--muted);font-size:.86rem;margin-top:4px">
          This view needs backend support (e.g., api.php?action=get_audit_log) to show “who changed status / assigned / deleted”.
        </div>
        <hr>
        <div style="color:var(--muted);font-weight:900">✅ UI ready — add API when you’re ready.</div>
      </div>
    </section>

    <!-- ✅ TEMPLATES / DOWNLOADS -->
    <section id="view-templates" class="view hidden">
      <div class="card">
        <h3 style="margin:0;color:var(--primary);font-weight:900">Templates / Downloads</h3>
        <div style="color:var(--muted);font-size:.86rem;margin-top:4px">Put your PDF/Doc files inside a folder like <b>/templates</b></div>
        <hr>

        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
          <div class="card" style="margin:0">
            <h4 style="margin:0;font-weight:1000">C1 Checklist</h4>
            <div style="color:var(--muted);font-size:.86rem;margin-top:6px">Download the latest checklist</div>
            <hr>
            <a class="btn btn-primary" href="templates/C1_Checklist.pdf" target="_blank"><i class="ph-bold ph-download-simple"></i> Download</a>
          </div>

          <div class="card" style="margin:0">
            <h4 style="margin:0;font-weight:1000">Requirements List</h4>
            <div style="color:var(--muted);font-size:.86rem;margin-top:6px">Standard lacking requirements sheet</div>
            <hr>
            <a class="btn btn-primary" href="templates/Requirements_List.pdf" target="_blank"><i class="ph-bold ph-download-simple"></i> Download</a>
          </div>

          <div class="card" style="margin:0">
            <h4 style="margin:0;font-weight:1000">Ocular Inspection Form</h4>
            <div style="color:var(--muted);font-size:.86rem;margin-top:6px">Printable ocular form</div>
            <hr>
            <a class="btn btn-primary" href="templates/Ocular_Form.pdf" target="_blank"><i class="ph-bold ph-download-simple"></i> Download</a>
          </div>

          <div class="card" style="margin:0">
            <h4 style="margin:0;font-weight:1000">Memo Template</h4>
            <div style="color:var(--muted);font-size:.86rem;margin-top:6px">For approvals/decisions</div>
            <hr>
            <a class="btn btn-primary" href="templates/Memo_Template.docx" target="_blank"><i class="ph-bold ph-download-simple"></i> Download</a>
          </div>
        </div>
      </div>
    </section>

    <!-- ✅ HELP / SUPPORT -->
    <section id="view-help" class="view hidden">
      <div class="card" style="max-width:900px;margin:0 auto">
        <h3 style="margin:0;color:var(--primary);font-weight:900">Help / SOP</h3>
        <div style="color:var(--muted);font-size:.86rem;margin-top:4px">Short guide per workflow step</div>
        <hr>

        <h4 style="margin:0;font-weight:1000">Workflow</h4>
        <ul style="margin-top:8px;color:#0f172a;font-weight:700;line-height:1.6">
          <li><b>Pending Review</b> → initial screening (admin/admin_aide)</li>
          <li><b>Processing</b> → under evaluation / checking documents</li>
          <li><b>Compliance</b> → waiting for lacking requirements submission</li>
          <li><b>Ocular</b> → schedule on-site inspection and record notes</li>
          <li><b>Forwarded to RD</b> → RD approves/disapproves with remarks</li>
        </ul>

        <hr>
        <h4 style="margin:0;font-weight:1000">Quick Tips</h4>
        <ul style="margin-top:8px;color:#0f172a;font-weight:700;line-height:1.6">
          <li>Use <b>Applications</b> tabs to find reports fast.</li>
          <li>Use <b>My Tasks</b> to focus on assigned workload and overdue items.</li>
          <li>Use <b>Ocular Schedule</b> to track inspection schedules.</li>
        </ul>

        <hr>
        <h4 style="margin:0;font-weight:1000">Contact Admin</h4>
        <div style="color:var(--muted);font-weight:900;margin-top:8px">
          Add your contact details here (email/phone) or link to a helpdesk page.
        </div>
      </div>
    </section>

    <!-- SETTINGS -->
    <section id="view-settings" class="view hidden">
      <div class="grid" style="max-width:720px;margin:0 auto">
        <div class="card">
          <h3 style="margin:0;color:var(--primary);font-weight:900">Profile</h3>
          <hr>
          <form onsubmit="App.updateProfile(event)">
            <label style="font-weight:900;font-size:.8rem">Display Name</label>
            <input class="input" id="set-name" required>

            <label style="font-weight:900;font-size:.8rem">Division (SDO)</label>
            <input class="input" id="set-sdo" disabled style="background:#f1f5f9">

            <button class="btn btn-primary" type="submit" style="margin-top:10px">Save Changes</button>
          </form>
        </div>

        <div class="card">
          <h3 style="margin:0;color:var(--primary);font-weight:900">Security</h3>
          <hr>
          <form onsubmit="App.updatePassword(event)">
            <label style="font-weight:900;font-size:.8rem">New Password (min 6)</label>
            <input class="input" type="password" name="new_password" minlength="6" required>

            <label style="font-weight:900;font-size:.8rem">Confirm Password</label>
            <input class="input" type="password" name="confirm_password" minlength="6" required>

            <button class="btn btn-primary" type="submit" style="margin-top:10px">Update Password</button>
          </form>
        </div>
      </div>
    </section>

  </div>
</main>

<div class="toastbox" id="toast-box"></div>

<!-- UPLOAD MODAL -->
<div class="modal" id="upload-modal">
  <div class="box">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;color:var(--primary);font-weight:900">Upload Report</h3>
      <button class="btn btn-ghost btn-sm" onclick="UI.closeModal('upload-modal')"><i class="ph-bold ph-x"></i></button>
    </div>
    <hr>
    <form onsubmit="App.handleUpload(event)">
      <label style="font-weight:900;font-size:.8rem">Document Title</label>
      <input class="input" name="title" required>

      <label style="font-weight:900;font-size:.8rem">Type</label>
      <select class="select" name="type">
        <option>General Report</option>
        <option>WINS Validation</option>
        <option>Incident Report</option>
        <option>Quarterly Report</option>
      </select>

      <label style="font-weight:900;font-size:.8rem">File</label>
      <input class="input" type="file" name="file" required>

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="btn btn-ghost" type="button" style="flex:1;justify-content:center" onclick="UI.closeModal('upload-modal')">Cancel</button>
        <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- USER MODAL -->
<div class="modal" id="user-modal">
  <div class="box">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;color:var(--primary);font-weight:900">Create New User</h3>
      <button class="btn btn-ghost btn-sm" onclick="UI.closeModal('user-modal')"><i class="ph-bold ph-x"></i></button>
    </div>
    <hr>
    <form onsubmit="App.handleCreateUser(event)">
      <label style="font-weight:900;font-size:.8rem">Full Name</label>
      <input class="input" name="fullname" required>

      <label style="font-weight:900;font-size:.8rem">Email</label>
      <input class="input" type="email" name="email" required>

      <label style="font-weight:900;font-size:.8rem">Password</label>
      <input class="input" type="password" name="password" minlength="6" required>

      <label style="font-weight:900;font-size:.8rem">Role</label>
      <select class="select" name="role">
        <option value="school">School User</option>
        <option value="sdo">SDO Evaluator</option>
        <option value="admin_aide">QAD Admin Aide</option>
        <option value="admin">QAD Admin</option>
        <option value="rd">RD</option>
      </select>

      <label style="font-weight:900;font-size:.8rem">SDO / Division</label>
      <select class="select" name="sdo">
        <option value="Unassigned">Select Division...</option>
        <option>SDO BAYBAY CITY</option><option>SDO BILIRAN</option>
        <option>SDO BORONGAN CITY</option><option>SDO CALBAYOG CITY</option>
        <option>SDO CATBALOGAN CITY</option><option>SDO EASTERN SAMAR</option>
        <option>SDO LEYTE</option><option>SDO MAASIN CITY</option>
        <option>SDO NORTHERN SAMAR</option><option>SDO ORMOC CITY</option>
        <option>SDO SAMAR</option><option>SDO SOUTHERN LEYTE</option>
        <option>SDO TACLOBAN CITY</option>
      </select>

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="btn btn-ghost" type="button" style="flex:1;justify-content:center" onclick="UI.closeModal('user-modal')">Cancel</button>
        <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- ✅ OCULAR SCHEDULE MODAL -->
<div class="modal" id="ocular-modal">
  <div class="box">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;color:var(--primary);font-weight:900">Set Ocular Schedule</h3>
      <button class="btn btn-ghost btn-sm" onclick="UI.closeModal('ocular-modal')"><i class="ph-bold ph-x"></i></button>
    </div>
    <hr>
    <form onsubmit="App.saveOcularSchedule(event)">
      <input type="hidden" id="ocular-report-id">
      <label style="font-weight:900;font-size:.8rem">Schedule Date/Time</label>
      <input class="input" type="datetime-local" id="ocular-dt" required>

      <label style="font-weight:900;font-size:.8rem">Notes</label>
      <textarea class="input" id="ocular-notes" placeholder="Team, location notes, reminders..."></textarea>

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="btn btn-ghost" type="button" style="flex:1;justify-content:center" onclick="UI.closeModal('ocular-modal')">Cancel</button>
        <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center">Save</button>
      </div>

      <div style="margin-top:10px;color:var(--muted);font-size:.82rem;font-weight:900">
        NOTE: This needs backend support (api.php?action=set_ocular_schedule). If you don’t have it yet, tell me and I’ll write api.php + SQL.
      </div>
    </form>
  </div>
</div>

<!-- ✅ RD REMARKS MODAL -->
<div class="modal" id="rd-remarks-modal">
  <div class="box">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;color:var(--primary);font-weight:900">RD Decision Remarks</h3>
      <button class="btn btn-ghost btn-sm" onclick="UI.closeModal('rd-remarks-modal')"><i class="ph-bold ph-x"></i></button>
    </div>
    <hr>
    <form onsubmit="App.submitRDRemarks(event)">
      <input type="hidden" id="rd-report-id">
      <input type="hidden" id="rd-decision">
      <label style="font-weight:900;font-size:.8rem">Remarks (optional)</label>
      <textarea class="input" id="rd-remarks" placeholder="Reason / notes for the decision..."></textarea>

      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="btn btn-ghost" type="button" style="flex:1;justify-content:center" onclick="UI.closeModal('rd-remarks-modal')">Cancel</button>
        <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center">Submit</button>
      </div>

      <div style="margin-top:10px;color:var(--muted);font-size:.82rem;font-weight:900">
        If your backend doesn’t store remarks yet, I can add `rd_remarks` column and update api.php.
      </div>
    </form>
  </div>
</div>

<!-- 🔔 NOTIFICATION PANEL (popup remains) -->
<div id="notif-panel">
  <div style="padding:14px 16px;border-bottom:1px solid var(--border);font-weight:900;color:var(--primary)">
    Notifications
  </div>
  <div id="notif-body"></div>
</div>

<!-- ✅ CHAT BUTTON + PANEL -->
<button id="chat-fab" onclick="Chat.toggle()" title="Messaging">
  <i class="ph-bold ph-chat-circle-dots"></i>
  <span id="chat-badge">0</span>
</button>

<div id="chat-panel">
  <div class="chat-top">
    <div class="title">
      <span class="dot"></span>
      Messaging
    </div>
    <div class="actions">
      <button onclick="Chat.refresh()" title="Refresh"><i class="ph-bold ph-arrow-clockwise"></i></button>
      <button onclick="Chat.toggle()" title="Close"><i class="ph-bold ph-x"></i></button>
    </div>
  </div>

  <div class="chat-body">
    <div class="chat-left">
      <div class="tabs">
        <button class="tab active" id="tab-chats" onclick="Chat.setMode('chats')">Chats</button>
        <button class="tab" id="tab-users" onclick="Chat.setMode('users')">Users</button>
      </div>
      <div class="search">
        <input id="chat-search" placeholder="Search..." oninput="Chat.applySearch(this.value)">
      </div>
      <div class="chat-list" id="chat-list"></div>
    </div>

    <div class="chat-right">
      <div class="chat-peerbar" id="chat-peerbar">
        <div class="peer">
          Select a user
          <small>Choose from Users tab or existing chats</small>
        </div>
      </div>

      <div class="chat-messages" id="chat-messages">
        <div class="chat-empty">👋 This is your system messaging. Select a user to start chatting.</div>
      </div>

      <div class="chat-compose">
        <textarea id="chat-input" placeholder="Type a message... (Enter=send, Shift+Enter=new line)" onkeydown="Chat.onKey(event)"></textarea>
        <button id="chat-send-btn" onclick="Chat.send()" disabled>Send</button>
      </div>
    </div>
  </div>
</div>

<script>
const Config = {
  api: 'api.php',
  chat_api: 'api.php',
  user: <?php echo $userPayload; ?>,
  workflow: {
    pending:     { label:'PENDING REVIEW', color:'#f59e0b' },
    received:    { label:'RECEIVED', color:'#64748b' },
    forwarded:   { label:'FORWARDED TO RD', color:'#3b82f6' },
    processing:  { label:'PROCESSING', color:'#3b82f6' },
    compliance:  { label:'PENDING FOR COMPLIANCE OF LACKING REQUIREMENT', color:'#f59e0b' },
    inspection:  { label:'FOR OCULAR INSPECTION', color:'#8b5cf6' },
    approved:    { label:'APPROVED', color:'#10b981' },
    disapproved: { label:'DISAPPROVED', color:'#ef4444' }
  },
  statusOrder: ['pending','processing','compliance','inspection','forwarded','approved','disapproved','received'],
  locations: {
    'SDO BAYBAY CITY': [10.6765,124.7985], 'SDO BILIRAN':[11.5228,124.4746],
    'SDO BORONGAN CITY':[11.6067,125.4339], 'SDO CALBAYOG CITY':[12.0676,124.5932],
    'SDO CATBALOGAN CITY':[11.7760,124.8860], 'SDO EASTERN SAMAR':[11.6067,125.4339],
    'SDO LEYTE':[11.2443,125.0039], 'SDO MAASIN CITY':[10.1362,124.8462],
    'SDO NORTHERN SAMAR':[12.4990,124.6405], 'SDO ORMOC CITY':[11.0050,124.6075],
    'SDO SAMAR':[11.7760,124.8860], 'SDO SOUTHERN LEYTE':[10.1362,124.8462],
    'SDO TACLOBAN CITY':[11.2443,125.0039]
  }
};

const State = {
  reports:[],
  schools:[],
  filteredSchools:[],
  users:[],
  filteredReports:[],
  appsStatus:'pending', // active tab for Applications
  notifItems:[]
};

const UI = {
  toast: (msg, type='success') => {
    const box=document.getElementById('toast-box');
    const el=document.createElement('div');
    el.className='toast';
    el.style.borderLeftColor = (type==='error') ? 'var(--danger)' : 'var(--success)';
    el.innerHTML=`<b>${type.toUpperCase()}</b>${UI.escape(msg)}`;
    box.appendChild(el);
    setTimeout(()=>el.remove(), 3200);
  },

  // ✅ D1 BUTTON (ADMIN ONLY)
  d1Btn: (reportId) => {
    if(String(Config.user.role) !== 'admin') return '';
    return `
      <a class="btn btn-primary btn-sm" style="text-decoration:none"
         href="d1.php?report_id=${Number(reportId)}">
        📝 D1
      </a>
    `;
  },

  escape: (s)=> String(s||'')
    .replaceAll('& ياد','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;"),
  openModal: (id)=> document.getElementById(id).style.display='grid',
  closeModal: (id)=> document.getElementById(id).style.display='none',

  switchView: (view)=>{
    document.querySelectorAll('.view').forEach(v=>v.classList.add('hidden'));
    document.getElementById(`view-${view}`).classList.remove('hidden');

    document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
    const nav = document.getElementById(`nav-${view}`);
    if(nav) nav.classList.add('active');

    const titles = {
      dashboard:'Dashboard',
      applications:'Applications / Reports',
      tasks:'My Tasks',
      ocular:'Ocular Schedule',
      rd:'RD Decisions',
      notifications:'Notifications',
      eval:'New Application (SDO)',
      users:'User Management',
      map:'GIS Map',
      schools:'Schools Database',
      analytics:'Analytics',
      audit:'Audit Log',
      templates:'Templates',
      help:'Help',
      settings:'Settings'
    };
    document.getElementById('page-title').innerText = titles[view] || 'Dashboard';

    if(view==='map') Map.render();
    if(view==='users') App.fetchUsers();
    if(view==='settings') App.loadSettings();

    if(view==='applications') App.renderApplications();
    if(view==='tasks') App.renderTasks();
    if(view==='ocular') App.renderOcular();
    if(view==='rd') App.renderRD();
    if(view==='notifications') Notifications.loadFull();
    if(view==='analytics') App.renderAnalytics();
  },

  renderHeader: ()=>{
    const el=document.getElementById('header-actions');
    el.innerHTML = `
      <button class="btn btn-ghost" onclick="Notifications.toggle()" style="position:relative" title="Notifications">
        <i class="ph-bold ph-bell"></i>
        <span id="notif-count" style="
          position:absolute; top:-4px; right:-4px;
          background:#ef4444; color:white; font-size:.7rem;
          padding:2px 6px; border-radius:999px; display:none;
        ">0</span>
      </button>
      <button class="btn btn-ghost" onclick="Chat.toggle()" title="Messages">
        <i class="ph-bold ph-chat-circle-dots"></i>
      </button>
    `;
  }
};

const Notifications = {
  open:false,
  toggle: ()=>{
    Notifications.open = !Notifications.open;
    document.getElementById('notif-panel').style.display = Notifications.open ? 'block' : 'none';
    if(Notifications.open) Notifications.load();
  },
  load: async ()=>{
    const body = document.getElementById('notif-body');
    body.innerHTML = `<div style="padding:16px;color:var(--muted)">Loading...</div>`;

    const res = await fetch(`${Config.api}?action=get_notifications`, { credentials:'same-origin' });
    const d = await res.json();

    if(!d.success){
      body.innerHTML = `<div style="padding:16px;color:var(--danger)">Failed to load notifications</div>`;
      return;
    }

    Notifications.render(d.items || []);
  },
  render: (items)=>{
    const body = document.getElementById('notif-body');
    const badge = document.getElementById('notif-count');

    State.notifItems = items || [];
    document.getElementById('nav-badge-notif').innerText = String((items||[]).length);

    if(items.length === 0){
      badge.style.display = 'none';
      body.innerHTML = `<div style="padding:16px;color:var(--muted)">No notifications</div>`;
      return;
    }

    badge.style.display = 'block';
    badge.innerText = items.length;

    body.innerHTML = items.slice(0, 10).map(r=>{
      const flow = Config.workflow[r.status] || Config.workflow.pending;

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      let sdoBtn = '';
      if(Config.user.role === 'sdo'){
        sdoBtn = `
          <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-primary btn-sm" onclick="Notifications.goToRequest(${r.id})">View Request</button>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${r.id}">Open C1</a>
          </div>
        `;
      }

      let rdBtn = '';
      if(Config.user.role === 'rd'){
        rdBtn = `
          <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank">View</a>
          </div>
        `;
      }

      // ✅ admin can still access D1 quickly from notifications popup if you want
      let d1 = (String(Config.user.role)==='admin')
        ? `<div style="margin-top:10px">${UI.d1Btn(r.id)}</div>`
        : ``;

      return `
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
          <div style="font-weight:900">${UI.escape(r.title || '')}</div>
          <div style="font-size:.85rem;color:var(--muted)">
            SDO: <b>${UI.escape(r.sdo || '-')}</b><br>
            Status: <b style="color:${flow.color}">${UI.escape(flow.label)}</b><br>
            Date: ${UI.escape(String(r.updated_at || r.created_at || '').split(' ')[0])}
          </div>
          ${sdoBtn}
          ${rdBtn}
          ${d1}
        </div>
      `;
    }).join('');
  },

  // ✅ NEW: full page
  loadFull: async ()=>{
    const wrap = document.getElementById('notif-full-body');
    wrap.innerHTML = `<div style="padding:16px;color:var(--muted);font-weight:900">Loading...</div>`;

    const res = await fetch(`${Config.api}?action=get_notifications`, { credentials:'same-origin' });
    const d = await res.json();

    if(!d.success){
      wrap.innerHTML = `<div style="padding:16px;color:var(--danger);font-weight:900">Failed to load notifications</div>`;
      return;
    }

    State.notifItems = d.items || [];
    Notifications.renderFull();
  },

  renderFull: ()=>{
    const wrap = document.getElementById('notif-full-body');
    const filter = (document.getElementById('notif-filter')?.value || 'all');
    let items = (State.notifItems || []);

    if(filter !== 'all'){
      items = items.filter(x => String(x.status||'') === filter);
    }

    if(items.length === 0){
      wrap.innerHTML = `<div style="padding:16px;color:var(--muted);font-weight:900">No notifications.</div>`;
      return;
    }

    wrap.innerHTML = items.map(r=>{
      const flow = Config.workflow[r.status] || Config.workflow.pending;

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      return `
        <div style="padding:14px 16px;border-bottom:1px solid var(--border)">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap">
            <div>
              <div style="font-weight:1000">${UI.escape(r.title || '')}</div>
              <div style="font-size:.85rem;color:var(--muted);margin-top:3px">
                SDO: <b>${UI.escape(r.sdo || '-')}</b> •
                Date: ${UI.escape(String(r.updated_at || r.created_at || '').split(' ')[0])}
              </div>
              <div style="margin-top:8px">
                <span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span>
              </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${Number(r.id||0)}">C1</a>
              ${UI.d1Btn(r.id)}
              ${Config.user.role === 'rd' && String(r.status)==='forwarded' ? `
                <button class="btn btn-primary btn-sm" onclick="App.openRDRemarks(${Number(r.id)}, 'approved')">Approve</button>
                <button class="btn btn-danger btn-sm" onclick="App.openRDRemarks(${Number(r.id)}, 'disapproved')">Disapprove</button>
              `:``}
            </div>
          </div>
        </div>
      `;
    }).join('');
  },

  goToRequest: (reportId)=>{
    Notifications.toggle();
    UI.switchView('dashboard');
  }
};

const Map = {
  instance:null,
  render: ()=>{
    setTimeout(()=>{
      if(!Map.instance){
        Map.instance = L.map('map').setView([11.2443, 125.0039], 8);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png')
          .addTo(Map.instance);
      }
      Map.instance.invalidateSize();
      Map.plot();
    }, 120);
  },
  plot: ()=>{
    Map.instance.eachLayer(l=>{ if(l instanceof L.Marker) l.remove(); });

    const counts={};
    State.reports.forEach(r=>{
      const sdo=r.sdo || 'Unknown';
      if(!counts[sdo]) counts[sdo]={pending:0,total:0};
      counts[sdo].total++;
      if(['pending','received','forwarded','processing','compliance','inspection'].includes(r.status)) counts[sdo].pending++;
    });

    for(const [sdo,coords] of Object.entries(Config.locations)){
      const data = counts[sdo] || {pending:0,total:0};
      const color = data.pending > 0 ? '#ef4444' : '#10b981';
      const icon = L.divIcon({
        className:'custom-pin',
        html:`<div style="width:14px;height:14px;background:${color};border-radius:50%;border:2px solid white;"></div>`
      });
      L.marker(coords,{icon})
        .bindPopup(`<b>${UI.escape(sdo)}</b><br>Active: ${data.pending}<br>Total: ${data.total}`)
        .addTo(Map.instance);
    }
  }
};

const App = {
  init: async ()=>{
    document.getElementById('u-name').innerText = Config.user.name;
    document.getElementById('u-role').innerText = String(Config.user.role||'').toUpperCase();
    document.getElementById('u-initial').innerText = (Config.user.name||'U').charAt(0).toUpperCase();

    const r = Config.user.role;

    if(r==='school' || r==='sdo') document.getElementById('nav-upload').classList.remove('hidden');
    if(r==='sdo') document.getElementById('nav-eval').classList.remove('hidden');
    if(r==='admin' || r==='admin_aide') document.getElementById('nav-users').classList.remove('hidden');

    // ✅ show tasks for admin/admin_aide
    if(['admin','admin_aide'].includes(r)) document.getElementById('nav-tasks').classList.remove('hidden');

    // ✅ RD decisions menu
    if(r==='rd') document.getElementById('nav-rd').classList.remove('hidden');

    // ✅ Audit log menu (optional; show only admin)
    if(r==='admin') document.getElementById('nav-audit').classList.remove('hidden');

    const div = document.getElementById('eval-division');
    if(div) div.value = Config.user.sdo || 'Unassigned';

    UI.renderHeader();

    if(['admin','admin_aide'].includes(Config.user.role)){
      await App.fetchUsers();
    }

    document.getElementById('school-search').addEventListener('input', (e)=>{
      const term = e.target.value.toLowerCase();
      State.filteredSchools = State.schools.filter(s => (s.name||'').toLowerCase().includes(term));
      App.renderSchools();
    });

    document.getElementById('report-search').addEventListener('input', (e)=>{
      const term = e.target.value.toLowerCase();
      State.filteredReports = State.reports.filter(x =>
        (x.title||'').toLowerCase().includes(term) ||
        (x.sdo||'').toLowerCase().includes(term) ||
        (x.status||'').toLowerCase().includes(term)
      );
      App.renderReports();
    });

    document.getElementById('apps-search').addEventListener('input', ()=> App.renderApplications());
    document.getElementById('ocular-search').addEventListener('input', ()=> App.renderOcular());
    document.getElementById('rd-search').addEventListener('input', ()=> App.renderRD());
    document.getElementById('tasks-filter').addEventListener('change', ()=> App.renderTasks());
    document.getElementById('notif-filter').addEventListener('change', ()=> Notifications.renderFull());

    await App.fetchData();
    await Notifications.loadFull(); // prime counts

    Chat.init();
  },

  // helper: safe date parse
  _parseDate: (s)=>{
    const str = String(s||'').replace('T',' ').trim();
    if(!str) return null;
    // handle "YYYY-MM-DD ..." or ISO
    const d = new Date(str);
    if(String(d) === 'Invalid Date'){
      // fallback
      const p = str.split(' ')[0];
      const d2 = new Date(p + 'T00:00:00');
      return (String(d2) === 'Invalid Date') ? null : d2;
    }
    return d;
  },

  _daysBetween: (a,b)=>{
    if(!a || !b) return null;
    const ms = b.getTime() - a.getTime();
    return Math.round(ms / (1000*60*60*24));
  },

  _counts: ()=>{
    const c = {};
    Object.keys(Config.workflow).forEach(k=>c[k]=0);
    (State.reports||[]).forEach(r=>{
      const s = String(r.status||'pending');
      if(c[s] === undefined) c[s]=0;
      c[s]++;
    });
    return c;
  },

  fetchData: async ()=>{
    try{
      const res = await fetch(`${Config.api}?action=get_data`, { credentials:'same-origin' });
      const data = await res.json();
      if(!data.success) throw data.message || 'Failed';

      State.reports = data.reports || [];
      State.filteredReports = State.reports;

      State.schools = data.schools || [];
      State.filteredSchools = State.schools;

      const countStatus = (key)=> State.reports.filter(r => r.status === key).length;
      const counts = App._counts();

      // dashboard stats
      document.getElementById('stat-pending').innerText = countStatus('pending');
      document.getElementById('stat-received').innerText = countStatus('received');
      document.getElementById('stat-forwarded').innerText = countStatus('forwarded');
      document.getElementById('stat-processing').innerText = countStatus('processing');
      document.getElementById('stat-compliance').innerText = countStatus('compliance');
      document.getElementById('stat-inspection').innerText = countStatus('inspection');
      document.getElementById('stat-approved').innerText = countStatus('approved');
      document.getElementById('stat-disapproved').innerText = countStatus('disapproved');
      document.getElementById('stat-schools').innerText = State.schools.length;

      // nav badges
      const active = (counts.pending||0)+(counts.processing||0)+(counts.compliance||0)+(counts.inspection||0)+(counts.forwarded||0);
      document.getElementById('nav-badge-active').innerText = String(active);
      document.getElementById('nav-badge-forwarded').innerText = String(counts.forwarded||0);
      document.getElementById('nav-badge-ocular').innerText = String(counts.inspection||0);

      // my tasks badge
      const my = State.reports.filter(r => String(r.assigned_to||'') === String(Config.user.id)).length;
      const myBadge = document.getElementById('nav-badge-my');
      if(myBadge) myBadge.innerText = String(my);

      App.renderReports();
      App.renderSchools();

      // refresh any open views
      if(!document.getElementById('view-map').classList.contains('hidden')) Map.render();
      if(!document.getElementById('view-applications').classList.contains('hidden')) App.renderApplications();
      if(!document.getElementById('view-tasks').classList.contains('hidden')) App.renderTasks();
      if(!document.getElementById('view-ocular').classList.contains('hidden')) App.renderOcular();
      if(!document.getElementById('view-rd').classList.contains('hidden')) App.renderRD();
      if(!document.getElementById('view-analytics').classList.contains('hidden')) App.renderAnalytics();

    }catch(e){
      console.error(e);
      UI.toast('Failed to load data', 'error');
    }
  },

  /* ===== existing dashboard table ===== */
  renderReports: ()=>{
    const tbody = document.getElementById('table-body');
    tbody.innerHTML='';

    const list = State.filteredReports || [];
    if(list.length === 0){
      document.getElementById('empty-state').classList.remove('hidden');
      return;
    }
    document.getElementById('empty-state').classList.add('hidden');

    const role = Config.user.role;

    list.forEach(r=>{
      const flow = Config.workflow[r.status] || Config.workflow['pending'];

      let appType = String(r.application_type || '').trim();
      if(!appType){
        const t = String(r.type || '');
        const m = t.match(/\(([^)]+)\)/);
        if(m && m[1]) appType = String(m[1]).trim();
      }

      const typeParam = appType ? `&application_type=${encodeURIComponent(appType)}` : '';

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}${typeParam}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      const c1Link = `c1_electronic.php?report_id=${r.id}${typeParam}`;

      const c1btn = `
        <a class="btn btn-ghost btn-sm"
           style="background:#e0f2fe;color:#0369a1;text-decoration:none"
           href="${c1Link}">
          C1
        </a>
      `;

      let action = `
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank">
            <i class="ph-bold ph-eye"></i> View
          </a>
          ${c1btn}
          ${UI.d1Btn(r.id)}
        </div>
      `;

      if(['admin','admin_aide'].includes(role)){
        const options = Object.entries(Config.workflow).map(([key,val]) =>
          `<option value="${key}" ${r.status===key?'selected':''}>${UI.escape(val.label)}</option>`
        ).join('');

        const assignable = (State.users || []).filter(u => ['admin','admin_aide'].includes(u.role));
        const assignOptions = [
          `<option value="">Assign to...</option>`,
          ...assignable.map(u => `
            <option value="${u.id}" ${String(r.assigned_to||'')===String(u.id)?'selected':''}>
              ${UI.escape(u.name)} (${UI.escape(u.role)})
            </option>
          `)
        ].join('');

        action = `
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <select class="select" style="max-width:240px" onchange="App.setStatus(${r.id}, this.value)">
              ${options}
            </select>

            <select class="select" style="max-width:240px" onchange="App.assignReport(${r.id}, this.value)">
              ${assignOptions}
            </select>

            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank">
              <i class="ph-bold ph-eye"></i>
            </a>

            ${c1btn}
            ${UI.d1Btn(r.id)}

            ${role === 'admin' ? `
              <button class="btn btn-primary btn-sm" onclick="App.forwardToRD(${r.id})">
                Forward to RD
              </button>

              <button class="btn btn-danger btn-sm" onclick="App.deleteReport(${r.id})">
                <i class="ph-bold ph-trash"></i> Delete
              </button>
            ` : ''}
          </div>
        `;
      }

      if(role === 'rd' && r.status === 'forwarded'){
        action = `
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank">
              <i class="ph-bold ph-eye"></i> View
            </a>
            ${c1btn}
            ${UI.d1Btn(r.id)}
            <button class="btn btn-primary btn-sm" onclick="App.openRDRemarks(${r.id}, 'approved')">Approve</button>
            <button class="btn btn-danger btn-sm" onclick="App.openRDRemarks(${r.id}, 'disapproved')">Disapprove</button>
          </div>
        `;
      }

      const owner = r.sdo
        ? `<b>${UI.escape(r.sdo)}</b><br><small style="color:var(--muted)">${UI.escape(r.owner_name || '')}</small>`
        : `<b>${UI.escape(r.owner_name || '-')}</b>`;

      const appTypeBadge = appType
        ? `<div class="app-type"><span>Type of Application:</span> <b>${UI.escape(appType)}</b></div>`
        : '';

      tbody.innerHTML += `
        <tr>
          <td>
            ${appTypeBadge}
            <b>${UI.escape(r.title || '')}</b><br>
            <small style="color:var(--muted)">${UI.escape(r.type || '')}</small>
          </td>
          <td>${owner}</td>
          <td>${UI.escape(String(r.created_at || '').split(' ')[0])}</td>
          <td><span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span></td>
          <td>${action}</td>
        </tr>
      `;
    });
  },

  /* ===== NEW: Applications view ===== */
  renderApplications: ()=>{
    const tabsEl = document.getElementById('apps-tabs');
    const body = document.getElementById('apps-body');
    const empty = document.getElementById('apps-empty');
    const term = (document.getElementById('apps-search')?.value || '').toLowerCase().trim();

    const counts = App._counts();

    // tabs (ordered)
    const tabs = [
      {k:'pending', t:'Pending Review'},
      {k:'processing', t:'Processing'},
      {k:'compliance', t:'Compliance'},
      {k:'inspection', t:'Ocular'},
      {k:'forwarded', t:'Forwarded to RD'},
      {k:'approved', t:'Approved'},
      {k:'disapproved', t:'Disapproved'},
    ];

    tabsEl.innerHTML = tabs.map(x=>{
      const active = (State.appsStatus === x.k) ? 'active' : '';
      return `
        <button class="tabbtn ${active}" onclick="App.setAppsTab('${x.k}')">
          ${UI.escape(x.t)}
          <span class="badge">${Number(counts[x.k]||0)}</span>
        </button>
      `;
    }).join('');

    // list
    let list = (State.reports||[]).filter(r => String(r.status||'pending') === String(State.appsStatus));
    if(term){
      list = list.filter(r =>
        String(r.title||'').toLowerCase().includes(term) ||
        String(r.sdo||'').toLowerCase().includes(term) ||
        String(r.owner_name||'').toLowerCase().includes(term)
      );
    }

    body.innerHTML = '';
    if(list.length === 0){
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');

    const role = Config.user.role;

    list.forEach(r=>{
      const flow = Config.workflow[r.status] || Config.workflow['pending'];

      let appType = String(r.application_type || '').trim();
      if(!appType){
        const t = String(r.type || '');
        const m = t.match(/\(([^)]+)\)/);
        if(m && m[1]) appType = String(m[1]).trim();
      }
      const typeParam = appType ? `&application_type=${encodeURIComponent(appType)}` : '';

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}${typeParam}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      const assignedName = (State.users||[]).find(u => String(u.id) === String(r.assigned_to||''))?.name || (r.assigned_to ? `ID:${r.assigned_to}` : '-');

      // actions
      let action = `
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
          <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${r.id}${typeParam}">C1</a>
          ${UI.d1Btn(r.id)}
        </div>
      `;

      if(['admin','admin_aide'].includes(role)){
        const options = Object.entries(Config.workflow).map(([key,val]) =>
          `<option value="${key}" ${r.status===key?'selected':''}>${UI.escape(val.label)}</option>`
        ).join('');

        const assignable = (State.users || []).filter(u => ['admin','admin_aide'].includes(u.role));
        const assignOptions = [
          `<option value="">Assign to...</option>`,
          ...assignable.map(u => `
            <option value="${u.id}" ${String(r.assigned_to||'')===String(u.id)?'selected':''}>
              ${UI.escape(u.name)} (${UI.escape(u.role)})
            </option>
          `)
        ].join('');

        action = `
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <select class="select" style="max-width:220px" onchange="App.setStatus(${r.id}, this.value)">
              ${options}
            </select>
            <select class="select" style="max-width:220px" onchange="App.assignReport(${r.id}, this.value)">
              ${assignOptions}
            </select>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i></a>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${r.id}${typeParam}">C1</a>
            ${UI.d1Btn(r.id)}
            ${role === 'admin' ? `
              <button class="btn btn-primary btn-sm" onclick="App.forwardToRD(${r.id})">Forward to RD</button>
            `:``}
          </div>
        `;
      }

      if(role === 'rd' && String(r.status)==='forwarded'){
        action = `
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${r.id}${typeParam}">C1</a>
            ${UI.d1Btn(r.id)}
            <button class="btn btn-primary btn-sm" onclick="App.openRDRemarks(${r.id}, 'approved')">Approve</button>
            <button class="btn btn-danger btn-sm" onclick="App.openRDRemarks(${r.id}, 'disapproved')">Disapprove</button>
          </div>
        `;
      }

      body.innerHTML += `
        <tr>
          <td>
            ${appType ? `<div class="app-type"><span>Type of Application:</span> <b>${UI.escape(appType)}</b></div>`:''}
            <b>${UI.escape(r.title||'')}</b><br>
            <small style="color:var(--muted)">${UI.escape(r.type||'')}</small>
          </td>
          <td><b>${UI.escape(r.sdo||'-')}</b><br><small style="color:var(--muted)">${UI.escape(r.owner_name||'')}</small></td>
          <td>${UI.escape(assignedName)}</td>
          <td>${UI.escape(String(r.created_at||'').split(' ')[0])}</td>
          <td><span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span></td>
          <td>${action}</td>
        </tr>
      `;
    });
  },

  setAppsTab: (k)=>{
    State.appsStatus = k;
    App.renderApplications();
  },

  /* ===== NEW: Tasks view (assigned_to = me) ===== */
  renderTasks: ()=>{
    const body = document.getElementById('tasks-body');
    const empty = document.getElementById('tasks-empty');
    const filter = (document.getElementById('tasks-filter')?.value || 'all');

    let list = (State.reports||[]).filter(r => String(r.assigned_to||'') === String(Config.user.id));

    const openStatuses = ['pending','processing','compliance','inspection','forwarded','received'];
    const today = new Date();

    // compute due date rule (simple default)
    const computeDue = (r)=>{
      const created = App._parseDate(r.created_at);
      if(!created) return null;
      // base days by status
      const s = String(r.status||'pending');
      let days = 7;
      if(s === 'compliance') days = 14;
      if(s === 'inspection') days = 10;
      if(s === 'forwarded') days = 5;
      const due = new Date(created.getTime() + days*24*60*60*1000);
      return due;
    };

    list = list.map(r=>{
      const due = computeDue(r);
      const overdue = due ? (today.getTime() > due.getTime()) : false;
      const open = openStatuses.includes(String(r.status||''));
      const age = App._daysBetween(App._parseDate(r.created_at), today);
      let priority = 'Normal';
      if(overdue) priority = 'Overdue';
      else if(age !== null && age >= 5) priority = 'High';

      return {...r, _due:due, _overdue:overdue, _open:open, _priority:priority};
    });

    if(filter === 'open') list = list.filter(x => x._open && !['approved','disapproved'].includes(String(x.status||'')));
    if(filter === 'overdue') list = list.filter(x => x._overdue);

    // sort overdue first then due soon
    list.sort((a,b)=>{
      const ao = a._overdue?1:0, bo = b._overdue?1:0;
      if(ao !== bo) return bo-ao;
      const ad = a._due ? a._due.getTime() : 9e18;
      const bd = b._due ? b._due.getTime() : 9e18;
      return ad-bd;
    });

    body.innerHTML = '';
    if(list.length === 0){
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');

    list.forEach(r=>{
      const flow = Config.workflow[r.status] || Config.workflow.pending;

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      const dueStr = r._due ? r._due.toISOString().slice(0,10) : '-';
      const duePill = r._overdue
        ? `<span class="pill" style="background:var(--danger)">OVERDUE</span>`
        : `<span class="pill" style="background:var(--info)">DUE ${UI.escape(dueStr)}</span>`;

      const pr = r._priority === 'Overdue'
        ? `<span class="pill" style="background:var(--danger)">Overdue</span>`
        : (r._priority === 'High'
          ? `<span class="pill" style="background:var(--warning)">High</span>`
          : `<span class="pill" style="background:#64748b">Normal</span>`);

      body.innerHTML += `
        <tr>
          <td><b>${UI.escape(r.title||'')}</b><br><small style="color:var(--muted)">${UI.escape(r.type||'')}</small></td>
          <td><b>${UI.escape(r.sdo||'-')}</b><br><small style="color:var(--muted)">${UI.escape(r.owner_name||'')}</small></td>
          <td>${duePill}</td>
          <td>${pr}</td>
          <td><span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span></td>
          <td>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${Number(r.id)}">C1</a>
              ${UI.d1Btn(r.id)}
            </div>
          </td>
        </tr>
      `;
    });
  },

  /* ===== NEW: Ocular view ===== */
  renderOcular: ()=>{
    const body = document.getElementById('ocular-body');
    const empty = document.getElementById('ocular-empty');
    const term = (document.getElementById('ocular-search')?.value || '').toLowerCase().trim();

    let list = (State.reports||[]).filter(r => String(r.status||'') === 'inspection');
    if(term){
      list = list.filter(r =>
        String(r.title||'').toLowerCase().includes(term) ||
        String(r.sdo||'').toLowerCase().includes(term)
      );
    }

    body.innerHTML = '';
    if(list.length === 0){
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');

    list.forEach(r=>{
      const flow = Config.workflow[r.status] || Config.workflow.pending;

      // These fields are optional (add in DB if you want):
      // r.ocular_schedule (datetime string), r.ocular_notes (text)
      const sched = r.ocular_schedule ? String(r.ocular_schedule).replace('T',' ').slice(0,16) : 'Not scheduled';
      const notes = r.ocular_notes ? String(r.ocular_notes).slice(0,60) : '';

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      body.innerHTML += `
        <tr>
          <td><b>${UI.escape(r.title||'')}</b><br><small style="color:var(--muted)">${UI.escape(r.type||'')}</small></td>
          <td><b>${UI.escape(r.sdo||'-')}</b><br><small style="color:var(--muted)">${UI.escape(r.owner_name||'')}</small></td>
          <td>${UI.escape(sched)}</td>
          <td style="max-width:240px">${UI.escape(notes)}</td>
          <td><span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span></td>
          <td>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
              <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${Number(r.id)}">C1</a>
              ${UI.d1Btn(r.id)}
              <button class="btn btn-primary btn-sm" onclick="App.openOcularModal(${Number(r.id)})">
                ${r.ocular_schedule ? 'Reschedule' : 'Schedule'}
              </button>
              <button class="btn btn-ghost btn-sm" onclick="App.markOcularCompleted(${Number(r.id)})">
                Mark Completed
              </button>
            </div>
          </td>
        </tr>
      `;
    });
  },

  openOcularModal: (reportId)=>{
    document.getElementById('ocular-report-id').value = String(reportId);
    document.getElementById('ocular-dt').value = '';
    document.getElementById('ocular-notes').value = '';
    UI.openModal('ocular-modal');
  },

  // ✅ needs backend: set_ocular_schedule
  saveOcularSchedule: async (e)=>{
    e.preventDefault();
    const report_id = Number(document.getElementById('ocular-report-id').value);
    const ocular_schedule = document.getElementById('ocular-dt').value;
    const ocular_notes = document.getElementById('ocular-notes').value;

    if(!report_id || !ocular_schedule) return UI.toast('Schedule required', 'error');

    try{
      const res = await fetch(`${Config.api}?action=set_ocular_schedule`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'same-origin',
        body: JSON.stringify({ report_id, ocular_schedule, ocular_notes })
      });
      const d = await res.json();
      if(d.success){
        UI.toast('Ocular schedule saved');
        UI.closeModal('ocular-modal');
        App.fetchData();
      } else {
        UI.toast(d.message || 'Failed (add api.php action set_ocular_schedule)', 'error');
      }
    }catch(err){
      console.error(err);
      UI.toast('Network error', 'error');
    }
  },

  markOcularCompleted: async (reportId)=>{
    // You can decide the next status. Common is back to processing or forward to RD.
    // Here: mark as processing.
    if(!confirm('Mark ocular inspection as completed and move to PROCESSING?')) return;
    await App.setStatus(reportId, 'processing', true);
  },

  /* ===== NEW: RD view ===== */
  renderRD: ()=>{
    const body = document.getElementById('rd-body');
    const empty = document.getElementById('rd-empty');
    const term = (document.getElementById('rd-search')?.value || '').toLowerCase().trim();

    let list = (State.reports||[]).filter(r => String(r.status||'') === 'forwarded');
    if(term){
      list = list.filter(r =>
        String(r.title||'').toLowerCase().includes(term) ||
        String(r.sdo||'').toLowerCase().includes(term)
      );
    }

    body.innerHTML = '';
    if(list.length === 0){
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');

    list.forEach(r=>{
      const flow = Config.workflow[r.status] || Config.workflow.pending;

      let link = '#';
      if(r.filename && String(r.filename).startsWith('EVAL:')){
        link = `view_evaluation.php?id=${String(r.filename).split(':')[1]}`;
      } else if(r.filename){
        link = `uploads/${encodeURIComponent(r.filename)}`;
      }

      const action = (Config.user.role === 'rd')
        ? `
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${Number(r.id)}">C1</a>
            ${UI.d1Btn(r.id)}
            <button class="btn btn-primary btn-sm" onclick="App.openRDRemarks(${Number(r.id)}, 'approved')">Approve</button>
            <button class="btn btn-danger btn-sm" onclick="App.openRDRemarks(${Number(r.id)}, 'disapproved')">Disapprove</button>
          </div>
        `
        : `
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="${link}" target="_blank"><i class="ph-bold ph-eye"></i> View</a>
            <a class="btn btn-ghost btn-sm" style="text-decoration:none" href="c1_electronic.php?report_id=${Number(r.id)}">C1</a>
            ${UI.d1Btn(r.id)}
          </div>
        `;

      body.innerHTML += `
        <tr>
          <td><b>${UI.escape(r.title||'')}</b><br><small style="color:var(--muted)">${UI.escape(r.type||'')}</small></td>
          <td><b>${UI.escape(r.sdo||'-')}</b><br><small style="color:var(--muted)">${UI.escape(r.owner_name||'')}</small></td>
          <td>${UI.escape(String(r.updated_at || r.created_at || '').split(' ')[0])}</td>
          <td><span class="pill" style="background:${flow.color}">${UI.escape(flow.label)}</span></td>
          <td>${action}</td>
        </tr>
      `;
    });
  },

  openRDRemarks: (reportId, decision)=>{
    document.getElementById('rd-report-id').value = String(reportId);
    document.getElementById('rd-decision').value = String(decision);
    document.getElementById('rd-remarks').value = '';
    UI.openModal('rd-remarks-modal');
  },

  submitRDRemarks: async (e)=>{
    e.preventDefault();
    const reportId = Number(document.getElementById('rd-report-id').value);
    const decision = String(document.getElementById('rd-decision').value);
    const remarks = String(document.getElementById('rd-remarks').value || '');

    UI.closeModal('rd-remarks-modal');
    await App.rdDecision(reportId, decision, remarks);
  },

  /* ===== Analytics ===== */
  renderAnalytics: ()=>{
    const kpi = document.getElementById('analytics-kpis');
    const monthsBody = document.getElementById('analytics-months');
    const divBody = document.getElementById('analytics-divisions');

    const reports = (State.reports||[]);
    const counts = App._counts();

    // turnaround time (created_at -> updated_at) for closed
    const closed = reports.filter(r => ['approved','disapproved'].includes(String(r.status||'')));
    const tts = closed.map(r=>{
      const a = App._parseDate(r.created_at);
      const b = App._parseDate(r.updated_at || r.created_at);
      const d = App._daysBetween(a,b);
      return (d===null?null:d);
    }).filter(x=>x!==null);
    const avg = tts.length ? (tts.reduce((p,c)=>p+c,0)/tts.length) : 0;

    const active = (counts.pending||0)+(counts.processing||0)+(counts.compliance||0)+(counts.inspection||0)+(counts.forwarded||0);

    kpi.innerHTML = `
      <div class="kpi">
        <div class="k">Total Applications</div>
        <div class="v">${reports.length}</div>
      </div>
      <div class="kpi">
        <div class="k">Active Workload</div>
        <div class="v">${active}</div>
      </div>
      <div class="kpi">
        <div class="k">Approved</div>
        <div class="v">${counts.approved||0}</div>
      </div>
      <div class="kpi">
        <div class="k">Disapproved</div>
        <div class="v">${counts.disapproved||0}</div>
      </div>
      <div class="kpi">
        <div class="k">Avg Turnaround (days)</div>
        <div class="v">${avg ? avg.toFixed(1) : '—'}</div>
      </div>
    `;

    // approvals per month
    const byMonth = {};
    reports.forEach(r=>{
      const st = String(r.status||'');
      if(!['approved','disapproved'].includes(st)) return;
      const dt = App._parseDate(r.updated_at || r.created_at);
      if(!dt) return;
      const key = `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}`;
      if(!byMonth[key]) byMonth[key] = {approved:0, disapproved:0};
      byMonth[key][st]++;
    });

    const monthKeys = Object.keys(byMonth).sort().reverse().slice(0, 12);
    monthsBody.innerHTML = monthKeys.map(k=>`
      <tr>
        <td><b>${UI.escape(k)}</b></td>
        <td>${Number(byMonth[k].approved||0)}</td>
        <td>${Number(byMonth[k].disapproved||0)}</td>
      </tr>
    `).join('') || `<tr><td colspan="3" style="color:var(--muted);font-weight:900">No data yet.</td></tr>`;

    // division summary
    const divs = {};
    reports.forEach(r=>{
      const d = String(r.sdo||'Unknown');
      if(!divs[d]) divs[d] = {total:0, active:0, approved:0, disapproved:0};
      divs[d].total++;
      const st = String(r.status||'pending');
      if(['pending','processing','compliance','inspection','forwarded','received'].includes(st)) divs[d].active++;
      if(st==='approved') divs[d].approved++;
      if(st==='disapproved') divs[d].disapproved++;
    });

    const divKeys = Object.keys(divs).sort((a,b)=> divs[b].active - divs[a].active);
    divBody.innerHTML = divKeys.map(k=>`
      <tr>
        <td><b>${UI.escape(k)}</b></td>
        <td>${divs[k].total}</td>
        <td>${divs[k].active}</td>
        <td>${divs[k].approved}</td>
        <td>${divs[k].disapproved}</td>
      </tr>
    `).join('') || `<tr><td colspan="5" style="color:var(--muted);font-weight:900">No data yet.</td></tr>`;
  },

  renderSchools: ()=>{
    const body = document.getElementById('school-body');
    body.innerHTML = (State.filteredSchools || []).slice(0, 80).map(s =>
      `<tr>
        <td>${UI.escape(s.school_id || '')}</td>
        <td><b>${UI.escape(s.name || '')}</b></td>
        <td>${UI.escape(s.level || '')}</td>
        <td>${UI.escape(s.division || '')}</td>
      </tr>`
    ).join('');
  },

  setStatus: async (id, status, skipConfirm=false)=>{
    if(!skipConfirm && !confirm('Update status?')) { App.renderReports(); return; }
    await fetch(`${Config.api}?action=update_status`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({id,status})
    });
    App.fetchData();
  },

  assignReport: async (reportId, personnelId)=>{
    if(!personnelId) return;
    if(!confirm('Assign this report?')) return;

    const res = await fetch(`${Config.api}?action=assign_report`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({report_id:reportId, personnel_id:Number(personnelId)})
    });
    const d = await res.json();
    if(d.success){
      UI.toast('Assigned successfully');
      App.fetchData();
    } else UI.toast(d.message || 'Failed', 'error');
  },

  forwardToRD: async (reportId)=>{
    if(!confirm('Forward this to RD?')) return;

    const res = await fetch(`${Config.api}?action=forward_to_rd`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({report_id:reportId})
    });
    const d = await res.json();
    if(d.success){
      UI.toast('Forwarded to RD');
      App.fetchData();
    } else UI.toast(d.message || 'Failed', 'error');
  },

  deleteReport: async (reportId)=>{
    if(!confirm('DELETE this record permanently?')) return;

    try{
      const res = await fetch(`${Config.api}?action=delete_report`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'same-origin',
        body: JSON.stringify({ report_id: reportId })
      });

      const d = await res.json();
      if(d.success){
        UI.toast('Deleted successfully');
        App.fetchData();
        if(Notifications.open) Notifications.load();
      }else{
        UI.toast(d.message || 'Delete failed', 'error');
      }
    }catch(e){
      console.error(e);
      UI.toast('Network error', 'error');
    }
  },

  // ✅ updated to accept remarks (backend optional; it will still work if ignored)
  rdDecision: async (reportId, decision, remarks='')=>{
    if(!confirm(`Confirm ${decision.toUpperCase()}?`)) return;

    const res = await fetch(`${Config.api}?action=rd_decision`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({report_id:reportId, decision, remarks})
    });
    const d = await res.json();
    if(d.success){
      UI.toast(`Marked as ${decision}`);
      App.fetchData();
    } else UI.toast(d.message || 'Failed', 'error');
  },

  fetchUsers: async ()=>{
    if(!['admin','admin_aide','rd'].includes(Config.user.role)) return;
    const res = await fetch(`${Config.api}?action=get_users`, { credentials:'same-origin' });
    const d = await res.json();
    if(!d.success) return UI.toast(d.message || 'Failed', 'error');

    State.users = d.users || [];

    const table = document.getElementById('users-body');
    if(table && ['admin','admin_aide'].includes(Config.user.role)){
      table.innerHTML = State.users.map(u=>`
        <tr>
          <td><b>${UI.escape(u.name)}</b></td>
          <td>${UI.escape(u.email)}</td>
          <td><span class="pill" style="background:#64748b">${UI.escape(u.role)}</span></td>
          <td>${UI.escape(u.sdo || '-')}</td>
        </tr>
      `).join('');
    }

    // refresh apps table assigned names
    if(!document.getElementById('view-applications').classList.contains('hidden')) App.renderApplications();
  },

  handleCreateUser: async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = {};
    fd.forEach((v,k)=>payload[k]=v);

    const res = await fetch(`${Config.api}?action=create_user`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify(payload)
    });
    const d = await res.json();
    if(d.success){
      UI.toast('User created!');
      UI.closeModal('user-modal');
      e.target.reset();
      App.fetchUsers();
    } else {
      UI.toast(d.message || 'Error', 'error');
    }
  },

  handleUpload: async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    try{
      const res = await fetch(`${Config.api}?action=upload_report`,{
        method:'POST',
        credentials:'same-origin',
        body:fd
      });
      const d = await res.json();
      if(d.success){
        UI.toast('Uploaded successfully');
        UI.closeModal('upload-modal');
        e.target.reset();
        App.fetchData();
      } else UI.toast(d.message || 'Upload failed', 'error');
    }catch(err){
      console.error(err);
      UI.toast('Network error', 'error');
    }
  },

  submitEvaluation: async (e)=>{
    e.preventDefault();
    if(!confirm('Submit this evaluation?')) return;

    const fd = new FormData(e.target);
    const payload = {};
    fd.forEach((v,k)=>payload[k]=v);

    try{
      const res = await fetch(`${Config.api}?action=create_evaluation`,{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        credentials:'same-origin',
        body:JSON.stringify(payload)
      });
      const d = await res.json();
      if(d.success){
        UI.toast('Submitted successfully');
        e.target.reset();
        UI.switchView('dashboard');
        App.fetchData();
      } else UI.toast(d.message || 'Error submitting', 'error');
    }catch(err){
      console.error(err);
      UI.toast('Error submitting', 'error');
    }
  },

  loadSettings: ()=>{
    document.getElementById('set-name').value = Config.user.name;
    document.getElementById('set-sdo').value = Config.user.sdo;
  },

  updateProfile: async (e)=>{
    e.preventDefault();
    const name = document.getElementById('set-name').value.trim();
    if(!name) return UI.toast('Name required', 'error');

    const res = await fetch(`${Config.api}?action=update_profile`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({name})
    });
    const d = await res.json();
    if(d.success){
      UI.toast('Saved');
      Config.user.name = name;
      document.getElementById('u-name').innerText = name;
      document.getElementById('u-initial').innerText = name.charAt(0).toUpperCase();
    } else UI.toast(d.message || 'Failed', 'error');
  },

  updatePassword: async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const p1 = fd.get('new_password');
    const p2 = fd.get('confirm_password');
    if(p1 !== p2) return UI.toast('Password mismatch', 'error');

    const res = await fetch(`${Config.api}?action=update_password`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({password:p1})
    });
    const d = await res.json();
    if(d.success){ UI.toast('Password updated'); e.target.reset(); }
    else UI.toast(d.message || 'Failed', 'error');
  },

  logout: async ()=>{
    try{ await fetch(`${Config.api}?action=logout`, { credentials:'same-origin' }); }catch(e){}
    window.location.href='login.php';
  }
};

/* =========================
   ✅ CHAT SYSTEM (your existing)
   ========================= */
const Chat = {
  open:false,
  mode:'chats',
  users:[],
  chats:[],
  filtered:[],
  peerId:0,
  peer:{},
  pollTimer:null,

  init: ()=>{
    Chat.updateUnreadBadge();
    setInterval(Chat.updateUnreadBadge, 4000);
    Chat.updateSendButton();
  },

  toggle: async ()=>{
    Chat.open = !Chat.open;
    document.getElementById('chat-panel').style.display = Chat.open ? 'block' : 'none';
    if(Chat.open){
      await Chat.refresh();
      Chat.startPolling();
      document.getElementById('chat-input').focus();
    } else {
      Chat.stopPolling();
    }
  },

  startPolling: ()=>{
    Chat.stopPolling();
    Chat.pollTimer = setInterval(async ()=>{
      if(!Chat.open) return;
      await Chat.updateUnreadBadge();
      await Chat.loadChats();
      if(Chat.peerId) await Chat.loadMessages(Chat.peerId, true);
      await Chat.renderList();
    }, 3500);
  },

  stopPolling: ()=>{
    if(Chat.pollTimer) clearInterval(Chat.pollTimer);
    Chat.pollTimer = null;
  },

  setMode: async (mode)=>{
    Chat.mode = mode;
    document.getElementById('tab-chats').classList.toggle('active', mode==='chats');
    document.getElementById('tab-users').classList.toggle('active', mode==='users');
    document.getElementById('chat-search').value = '';
    await Chat.renderList();
  },

  refresh: async ()=>{
    await Chat.loadUsers();
    await Chat.loadChats();
    await Chat.renderList();
    await Chat.updateUnreadBadge();
  },

  loadUsers: async ()=>{
    const res = await fetch(`${Config.chat_api}?action=get_chat_users`, { credentials:'same-origin' });
    const d = await res.json();
    Chat.users = (d.success ? (d.users || []) : []);
  },

  loadChats: async ()=>{
    const res = await fetch(`${Config.chat_api}?action=get_conversations`, { credentials:'same-origin' });
    const d = await res.json();
    Chat.chats = (d.success ? (d.items || []) : []);
  },

  applySearch: async (term)=>{
    term = (term||'').toLowerCase().trim();
    if(!term){
      await Chat.renderList();
      return;
    }
    if(Chat.mode === 'users'){
      Chat.filtered = Chat.users.filter(u =>
        (u.name||'').toLowerCase().includes(term) ||
        (u.role||'').toLowerCase().includes(term) ||
        (u.sdo||'').toLowerCase().includes(term)
      );
    } else {
      Chat.filtered = Chat.chats.filter(c =>
        (c.peer_name||'').toLowerCase().includes(term) ||
        (c.peer_role||'').toLowerCase().includes(term) ||
        (c.peer_sdo||'').toLowerCase().includes(term) ||
        (c.last_message||'').toLowerCase().includes(term)
      );
    }
    Chat.renderList(true);
  },

  renderList: async (useFiltered=false)=>{
    const listEl = document.getElementById('chat-list');

    let items = [];
    if(useFiltered){
      items = Chat.filtered || [];
    } else {
      items = (Chat.mode === 'users') ? (Chat.users || []) : (Chat.chats || []);
      Chat.filtered = items;
    }

    if(items.length === 0){
      listEl.innerHTML = `<div class="chat-empty">No results</div>`;
      return;
    }

    if(Chat.mode === 'users'){
      listEl.innerHTML = items.map(u=>{
        const active = (Chat.peerId === Number(u.id)) ? 'active' : '';
        const letter = (u.name || 'U').charAt(0).toUpperCase();
        return `
          <div class="chat-item ${active}" onclick="Chat.openPeer(${Number(u.id)})">
            <div class="chat-avatar">${UI.escape(letter)}</div>
            <div style="min-width:0">
              <b>${UI.escape(u.name || '')}</b>
              <small>${UI.escape((u.role||'').toUpperCase())}${u.sdo ? ' • '+UI.escape(u.sdo) : ''}</small>
            </div>
          </div>
        `;
      }).join('');
    } else {
      listEl.innerHTML = items.map(c=>{
        const active = (Chat.peerId === Number(c.peer_id)) ? 'active' : '';
        const unread = Number(c.unread || 0);
        const letter = (c.peer_name || 'U').charAt(0).toUpperCase();
        return `
          <div class="chat-item ${active}" onclick="Chat.openPeer(${Number(c.peer_id)})">
            <div class="chat-avatar">${UI.escape(letter)}</div>
            <div style="min-width:0">
              <b>${UI.escape(c.peer_name || '')}</b>
              <small>${UI.escape((c.peer_role||'').toUpperCase())}${c.peer_sdo ? ' • '+UI.escape(c.peer_sdo) : ''}</small>
            </div>
            ${unread>0 ? `<span class="unread">${unread} new</span>` : ``}
          </div>
        `;
      }).join('');
    }
  },

  openPeer: async (id)=>{
    Chat.peerId = Number(id);

    const u = (Chat.users || []).find(x => Number(x.id) === Chat.peerId);
    const c = (Chat.chats || []).find(x => Number(x.peer_id) === Chat.peerId);

    const name = u?.name || c?.peer_name || 'User';
    const role = u?.role || c?.peer_role || '';
    const sdo  = u?.sdo  || c?.peer_sdo  || '';

    Chat.peer = { id:Chat.peerId, name, role, sdo };

    document.getElementById('chat-peerbar').innerHTML = `
      <div class="peer">
        ${UI.escape(name)}
        <small>${UI.escape(String(role||'').toUpperCase())}${sdo ? ' • '+UI.escape(sdo) : ''}</small>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-ghost btn-sm" onclick="Chat.loadMessages(${Chat.peerId})" title="Reload">
          <i class="ph-bold ph-arrow-clockwise"></i>
        </button>
      </div>
    `;

    Chat.updateSendButton();

    await Chat.loadMessages(Chat.peerId);
    await Chat.markRead(Chat.peerId);
    await Chat.updateUnreadBadge();
    await Chat.loadChats();
    await Chat.renderList();
    document.getElementById('chat-input').focus();
  },

  loadMessages: async (peerId, silent=false)=>{
    if(!peerId) return;
    const res = await fetch(`${Config.chat_api}?action=get_messages&peer_id=${encodeURIComponent(peerId)}`, { credentials:'same-origin' });
    const d = await res.json();
    const rows = d.success ? (d.messages || []) : [];

    const box = document.getElementById('chat-messages');
    if(rows.length === 0){
      box.innerHTML = `<div class="chat-empty">No messages yet. Say hello 👋</div>`;
      return;
    }

    box.innerHTML = rows.map(m=>{
      const me = Number(m.from_id) === Number(Config.user.id);
      const t = String(m.created_at || '').replace('T',' ').slice(0,16);
      return `
        <div class="msg-row ${me?'me':''}">
          <div class="bubble">
            ${UI.escape(m.body || '')}
            <div class="meta">${UI.escape(t)}</div>
          </div>
        </div>
      `;
    }).join('');

    box.scrollTop = box.scrollHeight;
    if(!silent) setTimeout(()=>{ box.scrollTop = box.scrollHeight; }, 50);
  },

  markRead: async (peerId)=>{
    await fetch(`${Config.chat_api}?action=mark_read`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({peer_id: Number(peerId)})
    });
  },

  onKey: (e)=>{
    if(e.key === 'Enter' && !e.shiftKey){
      e.preventDefault();
      Chat.send();
    }
  },

  send: async ()=>{
    const input = document.getElementById('chat-input');
    const text = (input.value || '').trim();
    if(!Chat.peerId) return UI.toast('Select a user to chat', 'error');
    if(!text) return;

    input.value = '';
    Chat.autoResize();

    const res = await fetch(`${Config.chat_api}?action=send_message`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body:JSON.stringify({to_id: Chat.peerId, body: text})
    });
    const d = await res.json();
    if(!d.success){
      UI.toast(d.message || 'Failed to send', 'error');
      return;
    }

    await Chat.loadMessages(Chat.peerId, true);
    await Chat.loadChats();
    await Chat.renderList();
    await Chat.updateUnreadBadge();
  },

  updateUnreadBadge: async ()=>{
    try{
      const res = await fetch(`${Config.chat_api}?action=get_unread_messages_count`, { credentials:'same-origin' });
      const d = await res.json();
      const c = Number(d.count || 0);

      const badge = document.getElementById('chat-badge');
      badge.innerText = c;
      badge.style.display = c > 0 ? 'block' : 'none';
    }catch(e){}
  },

  updateSendButton: ()=>{
    const btn = document.getElementById('chat-send-btn');
    btn.disabled = !Chat.peerId;
  },

  autoResize: ()=>{
    const ta = document.getElementById('chat-input');
    if(!ta) return;
    ta.style.height = 'auto';
    ta.style.height = Math.min(120, ta.scrollHeight) + 'px';
  }
};

document.addEventListener('input', (e)=>{
  if(e.target && e.target.id === 'chat-input') Chat.autoResize();
});

App.init();
</script>
</body>
</html>

