<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
if (isset($_SESSION['user'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>i-QAD | Region VIII</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
:root{
  --primary:#0f4c81;
  --primary-dark:#0a355c;
  --accent:#fbbf24;
  --bg:radial-gradient(circle at top right,#0b2a4a,#020617);
  --glass:rgba(255,255,255,0.14);
  --border:rgba(255,255,255,0.22);
  --text:#f8fafc;
  --muted:#c7d2fe;
}

*{box-sizing:border-box}
body{
  margin:0;
  height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:var(--bg);
  font-family:'Plus Jakarta Sans',sans-serif;
  color:var(--text);
}

.auth-card{
  width:100%;
  max-width:460px;
  padding:2.6rem;
  border-radius:28px;
  background:linear-gradient(180deg,rgba(255,255,255,.18),rgba(255,255,255,.05));
  backdrop-filter:blur(18px);
  border:1px solid var(--border);
  box-shadow:0 40px 120px rgba(0,0,0,.55);
}

.brand{text-align:center;margin-bottom:2rem}
.brand img{width:90px;margin-bottom:.6rem}

.tabs{
  display:flex;
  background:rgba(255,255,255,.08);
  padding:4px;
  border-radius:14px;
  margin-bottom:1.6rem;
}

.tab{
  flex:1;
  padding:10px;
  border:none;
  background:transparent;
  border-radius:12px;
  color:var(--muted);
  font-weight:800;
  cursor:pointer;
  transition:.3s;
}

.tab.active{
  background:rgba(255,255,255,.2);
  color:white;
}

.form-group{position:relative;margin-bottom:1rem}

.label{
  display:block;
  font-size:.78rem;
  font-weight:800;
  letter-spacing:.02em;
  margin:10px 0 6px;
  color:#e2e8f0;
}

.form-control{
  width:100%;
  padding:14px 14px 14px 42px;
  border-radius:14px;
  border:1px solid var(--border);
  background:rgba(255,255,255,.14);
  color:white;
  font-weight:600;
}

.input-icon{
  position:absolute;
  left:14px;
  top:50%;
  transform:translateY(-50%);
  color:#c7d2fe;
}

/* ✅ White modern selects */
.form-control.select-white{
  background:#ffffff !important;
  color:#0f172a !important;
  border:1px solid rgba(255,255,255,0.35);
  box-shadow:0 8px 30px rgba(0,0,0,.12);
  padding-left:14px !important;
}
.form-control.select-white option{ color:#0f172a; }
.form-control.select-white:focus{
  outline:none;
  border-color:rgba(251,191,36,.9);
  box-shadow:0 0 0 4px rgba(251,191,36,.25);
}

.btn{
  width:100%;
  padding:14px;
  border:none;
  border-radius:14px;
  font-weight:900;
  cursor:pointer;
  transition:all .25s ease;
}

.btn-primary{
  background:linear-gradient(135deg,var(--primary),var(--primary-dark));
  color:white;
}

.btn-accent{
  background:linear-gradient(135deg,#fbbf24,#f59e0b);
  color:#111827;
}

.btn:hover{
  transform:translateY(-2px);
  box-shadow:0 10px 25px rgba(0,0,0,.3);
}

.btn:active{ transform:scale(.97); }

.hidden{display:none}

.msg{
  margin-top:1rem;
  padding:12px;
  border-radius:12px;
  font-size:.85rem;
  text-align:center;
  display:none;
}
.msg.error{background:#7f1d1d;color:#fee2e2}
.msg.success{background:#064e3b;color:#dcfce7}

.small{opacity:.8;font-size:.8rem;text-align:center;margin-top:12px}
</style>
</head>
<body>

<div class="auth-card">

  <div class="brand">
    <img src="assests/RO-VIII.png" onerror="this.style.display='none'">
    <h2 style="margin:0">i-QAD</h2>
    <p style="margin:.2rem 0 0;opacity:.8">• integrated Quality Assurance Digital System</p>
  </div>

  <div class="tabs">
    <button class="tab active" id="tab-login" onclick="toggleTab('login')">Sign In</button>
    <button class="tab" id="tab-register" onclick="toggleTab('register')">New Account</button>
  </div>

  <!-- LOGIN -->
  <form id="loginForm" onsubmit="handleAuth(event,'login')">
    <div class="form-group">
      <i class="ph-bold ph-envelope-simple input-icon"></i>
      <input id="l_email" type="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="form-group">
      <i class="ph-bold ph-lock-key input-icon"></i>
      <input id="l_pass" type="password" class="form-control" placeholder="Password" required>
    </div>
    <button class="btn btn-primary" type="submit">Sign In</button>
  </form>

  <!-- REGISTER -->
  <form id="registerForm" class="hidden" onsubmit="handleAuth(event,'register')">

    <label class="label">Account Type</label>
    <div class="form-group">
      <select id="r_role" class="form-control select-white" onchange="toggleAdminCode(this.value)">
        <option value="school">School User</option>
        <option value="sdo">SDO Evaluator</option>
        <option value="admin_aide">QAD Admin Aide</option>
        <option value="admin">QAD Admin</option>
        <option value="rd">Regional Director (RD)</option>
      </select>
    </div>

    <div class="form-group">
      <i class="ph-bold ph-user input-icon"></i>
      <input id="r_name" class="form-control" placeholder="Full Name" required>
    </div>

    <div class="form-group">
      <i class="ph-bold ph-envelope-simple input-icon"></i>
      <input id="r_email" type="email" class="form-control" placeholder="Email" required>
    </div>

    <div class="form-group">
      <i class="ph-bold ph-lock-key input-icon"></i>
      <input id="r_pass" type="password" class="form-control" placeholder="Password (min 6)" minlength="6" required>
    </div>

    <!-- ✅ Admin Code (HIDDEN unless admin_aide/admin/rd) -->
    <div class="form-group hidden" id="adminCodeGroup">
      <i class="ph-bold ph-shield-check input-icon"></i>
      <input id="r_code" class="form-control" placeholder="Admin Code">
    </div>

    <label class="label">SDO / Division</label>
    <div class="form-group">
      <select id="r_sdo" class="form-control select-white">
        <option value="Unassigned">Select SDO / Division (optional)</option>
        <option>SDO BAYBAY CITY</option><option>SDO BILIRAN</option>
        <option>SDO BORONGAN CITY</option><option>SDO CALBAYOG CITY</option>
        <option>SDO CATBALOGAN CITY</option><option>SDO EASTERN SAMAR</option>
        <option>SDO LEYTE</option><option>SDO MAASIN CITY</option>
        <option>SDO NORTHERN SAMAR</option><option>SDO ORMOC CITY</option>
        <option>SDO SAMAR</option><option>SDO SOUTHERN LEYTE</option>
        <option>SDO TACLOBAN CITY</option>
      </select>
    </div>

    <button class="btn btn-accent" type="submit">Create Account</button>
  </form>

  <div id="msg" class="msg"></div>
  <div class="small">Use official DepEd email when possible.</div>
</div>

<script>
function toggleTab(mode){
  document.getElementById('loginForm').classList.toggle('hidden',mode!=='login');
  document.getElementById('registerForm').classList.toggle('hidden',mode!=='register');
  document.getElementById('tab-login').classList.toggle('active',mode==='login');
  document.getElementById('tab-register').classList.toggle('active',mode==='register');
  document.getElementById('msg').style.display='none';
}

function toggleAdminCode(role){
  const group = document.getElementById('adminCodeGroup');
  const code  = document.getElementById('r_code');

  const needsCode = ['admin_aide','admin','rd'].includes(role);

  group.classList.toggle('hidden', !needsCode);
  code.required = needsCode;

  if(!needsCode) code.value = '';
}

async function handleAuth(e,type){
  e.preventDefault();
  const btn=e.target.querySelector('button');
  btn.disabled=true;
  const old=btn.innerText;
  btn.innerText='Please wait...';

  const payload = type==='login'
    ? { email:l_email.value, password:l_pass.value }
    : {
        role:r_role.value,
        fullname:r_name.value,
        email:r_email.value,
        password:r_pass.value,
        sdo:r_sdo.value,
        admin_code: (typeof r_code !== 'undefined') ? r_code.value : ''
      };

  try{
    const r=await fetch(`api.php?action=${type}`,{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify(payload)
    });

    const d=await r.json();
    if(!d.success) throw (d.message || 'Request failed');

    if(type==='login'){
      location.href='index.php';
      return;
    }

    msg.className='msg success';
    msg.innerHTML='Account created successfully. Please login.';
    msg.style.display='block';
    setTimeout(()=>toggleTab('login'),1200);

  }catch(err){
    msg.className='msg error';
    msg.innerHTML=err;
    msg.style.display='block';
  }finally{
    btn.disabled=false;
    btn.innerText=old;
  }
}
</script>

</body>
</html>
