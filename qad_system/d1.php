<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();
if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

$report_id = intval($_GET['report_id'] ?? 0);
if ($report_id <= 0) {
  die("Invalid Report ID");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>D1 Electronic - Evaluation Sheet</title>

<style>
  body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    margin: 20px;
    background-color: #f4f4f4;
  }
  .container {
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
  }
  h2, h3, h4 {
    text-align: center;
    margin: 5px 0;
  }
  .header-section {
    margin-bottom: 20px;
    text-align: center;
  }
  .header-inputs {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
  }

  /* General Input Styling */
  input[type="text"], input[type="date"], input[type="number"], textarea, select {
    width: 95%;
    padding: 4px;
    border: 1px solid #ccc;
    border-radius: 2px;
    font-family: inherit;
    font-size: inherit;
  }
  input[type="radio"], input[type="checkbox"] {
    margin: 0 4px;
    vertical-align: middle;
  }

  /* Main Table Styling */
  table.main-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }
  table.main-table th, table.main-table td {
    border: 1px solid black;
    padding: 5px;
    vertical-align: top;
  }

  /* Column Widths */
  .col-criteria { width: 20%; }
  .col-docs { width: 25%; }
  .col-eval { width: 45%; }
  .col-remarks { width: 10%; text-align: center; }

  /* Nested Tables */
  table.nested-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
    font-size: 10px;
  }
  table.nested-table th, table.nested-table td {
    border: 1px solid #666;
    padding: 3px;
  }

  /* Helpers */
  .full-width { width: 100%; box-sizing: border-box;}
  .label-row { display: flex; align-items: center; margin-bottom: 3px; flex-wrap: wrap; }
  .label-row label { flex-shrink: 0; margin-right: 5px; font-weight: bold;}
  .label-row input[type="text"], .label-row input[type="date"] { flex-grow: 1; }
  .section-header { background-color: #eee; font-weight: bold; }
  .center-text { text-align: center; }

  /* Signature Section Styling */
  .signature-section {
    display: flex;
    justify-content: space-between;
    margin-top: 40px;
    flex-wrap: wrap;
  }
  .sig-block {
    width: 30%;
    text-align: center;
    margin-bottom: 30px;
  }
  .sig-input {
    border: none;
    border-bottom: 1px solid black;
    text-align: center;
    width: 90%;
    margin-bottom: 5px;
    background: transparent;
    font-weight: bold;
    font-size: 12px;
    padding: 5px;
  }
  .sig-input:focus {
    outline: none;
    border-bottom: 2px solid #000;
    background-color: #f9f9f9;
  }
  .sig-label {
    font-size: 10px;
    color: #444;
  }

  /* Top Buttons */
  .top-actions{
    display:flex;
    gap:10px;
    justify-content:flex-end;
    margin-bottom:12px;
    flex-wrap:wrap;
  }
  .btn{
    border:0;
    cursor:pointer;
    padding:10px 14px;
    font-weight:bold;
    border-radius:10px;
  }
  .btn-save{ background:#0ea5e9; color:white; }
  .btn-submit{ background:#10b981; color:white; }
  .btn-back{ background:#64748b; color:white; text-decoration:none; display:inline-block; }

  .pill {
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    background:#e2e8f0;
    font-weight:bold;
    font-size: 11px;
  }

  @media print {
    body { background: white; margin: 0; }
    .container { box-shadow: none; width: 100%; padding: 0; }
    input, textarea, select { border: none; border-bottom: 1px solid #ccc; resize: none; }
    .top-actions{ display:none; }
    ::placeholder { color: transparent; }
  }
</style>
</head>

<body>
<div class="container">

  <div class="top-actions">
    <a class="btn btn-back" href="dashboard.php">⬅ Back</a>
    <span id="d1-status" class="pill">Loading…</span>
    <button class="btn btn-save" type="button" onclick="D1.save()">💾 Save Draft</button>
    <button class="btn btn-submit" type="button" onclick="D1.submit()">✅ Submit Final</button>
    <button class="btn" type="button" onclick="window.print()">🖨 Print</button>
  </div>

  <div class="header-section">
    <div>Department of Education</div>
    <div class="header-inputs">
      Division of <input type="text" placeholder="Division" />
      Region <input type="text" style="width: 80px;" placeholder="Region" />
    </div>
    <h2>APPLICATION FOR ESTABLISHMENT OF PUBLIC ELEMENTARY/SECONDARY SCHOOL</h2>
    <h3>EVALUATION SHEET</h3>
    <div style="text-align: right; font-weight: bold;">Annex D - 1</div>
  </div>

  <!-- IMPORTANT: ONLY ONE FORM -->
  <form id="d1-form">

    <!-- ✅ YOUR FULL ANNEX D-1 TABLE STARTS HERE -->
    <table class="main-table">
      <thead>
        <tr>
          <th class="col-criteria">CRITERIA</th>
          <th class="col-docs">REQUIRED DOCUMENTS</th>
          <th class="col-eval">PER EVALUATION</th>
          <th class="col-remarks">REMARKS<br><small>PASSED OR FAILED<br>(Please state reason if Failed)</small></th>
        </tr>
      </thead>
      <tbody>

        <tr>
          <td>
            <strong>1.</strong> School to be established is an urgent need in the area to be served as indicated in the project feasibility study.<br><br>
            &gt; Kindergarten to Grade 6 – at least one (1) school for every barangay<br>
            &gt; Grades 7 to 10 – at least one (1) for every municipality/city
          </td>
          <td>
            <strong>a.</strong> Letter request to open a school addressed to the Schools Division Superintendent (SDS), either from PTA or Barangay Council.<br><br>
            <strong>b.</strong> Feasibility study, duly recommended/ endorsed by the SDS, indicating the following:
            <ol>
              <li>Justification on the need to establish a school;</li>
              <li>Proposed Organizational Structure</li>
              <li>School Environment (environmental scanning/ situational analysis);</li>
              <li>Proposed School Development Plan; and</li>
              <li>Proposed Budget/Budgetary Requirements (to cover the proposed school's crucial resources).</li>
            </ol>
          </td>
          <td>
            <div class="label-row"><label>a. Requesting Officer:</label> <input type="text"></div>
            <div class="label-row"><label>Designation/Position:</label> <input type="text"></div>
            <div class="label-row"><label>Office/School:</label> <input type="text"></div>
            <div class="label-row"><label>Date of Request:</label> <input type="date"></div>
            <br>
            <div class="label-row"><label>b. Feasibility Study Prepared By:</label> <input type="text"></div>
            <div class="label-row"><label>Designation/Position:</label> <input type="text"></div>
            <div class="label-row"><label>Office/School:</label> <input type="text"></div>
            <br>
            <div class="label-row"><label>Proposed Name of School:</label> <input type="text"></div>
            <div class="label-row"><label>Address/Location:</label> <input type="text"></div>
            <br>
            <div class="label-row">
              <label>Recommended/endorsed by the SDS?</label>
              <label><input type="radio" name="sds_endorse" value="yes"> YES</label>
              <label><input type="radio" name="sds_endorse" value="no"> NO</label>
            </div>
            <div class="label-row"><label>Date Recommended:</label> <input type="date"></div>
            <br>
            <strong>b.1 Justification on the need for the establishment of school:</strong><br>
            <textarea class="full-width" rows="3"></textarea>
            <br><br>

            <strong>b.2 Organizational Structure (As proposed)</strong>
            <table class="nested-table">
              <thead>
                <tr>
                  <th>Position Title</th>
                  <th>No. of Positions<br>(Nationally-paid)</th>
                  <th>No. of Positions<br>(Locally-paid)</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>School Principal</td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
                <tr><td>Master Teacher I/II</td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
                <tr><td>Teacher I/II/III</td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
                <tr><td>Administrative Assistant</td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
                <tr><td>Others: <input type="text" placeholder="Specify"></td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
                <tr style="font-weight:bold;"><td>TOTAL</td><td><input type="number"></td><td><input type="number"></td><td><input type="number"></td></tr>
              </tbody>
            </table>
            <br>

            <strong>b.3 School Environment (environmental scanning/ situational analysis)</strong><br>
            <div class="label-row">
              <label>Location/Classification:</label>
              <label><input type="radio" name="loc_class" value="urban"> Urban Area</label>
              <label><input type="radio" name="loc_class" value="rural"> Rural Area</label>
            </div>
            <div class="label-row">
              <label>Topography/Geographical Condition:</label>
              <label><input type="checkbox"> Mountainous</label>
              <label><input type="checkbox"> Coastal</label>
              <label><input type="checkbox"> Plain</label>
            </div>
            <div class="label-row">
              <label>Catchment Area:</label> Within <input type="number" style="width: 50px;"> km. Radius from nearest school.
            </div>
            <br>

            <strong>Mode(s) of transportation in going from home to school and vice versa:</strong>
            <table class="nested-table">
              <thead>
                <tr>
                  <th>Mode</th>
                  <th>Frequency *</th>
                  <th>Travel Time (min/hr)</th>
                </tr>
              </thead>
              <tbody>
                <tr><td colspan="3"><strong>1. Land Transportation</strong></td></tr>
                <tr><td>a. Tricycle</td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td>b. Jeepney</td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td>c. Bus</td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td>d. Motorcycle</td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td>e. Habal-habal</td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td>f. Others: <input type="text"></td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td><strong>2. Banca Ride</strong></td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td><strong>3. Animal Ride</strong></td><td><input type="text"></td><td><input type="text"></td></tr>
                <tr><td><strong>4. Hiking</strong></td><td><input type="text"></td><td><input type="text"></td></tr>
              </tbody>
            </table>
            <small>* whether once a day, twice, every hour, etc.</small>
            <br><br>

            <strong>b.4 Proposed School Development Plan</strong>
            <div class="label-row"><label>&gt; Prepared By:</label> <input type="text"></div>
            <div class="label-row"><label>&gt; Position/Designation:</label> <input type="text"></div>
            <div class="label-row"><label>&gt; Office:</label> <input type="text"></div>
            <div class="label-row"><label>&gt; Date:</label> <input type="date"></div>
            <br>

            <strong>b.5 Proposed Budget/Budgetary Requirements</strong>
            <table class="nested-table">
              <thead>
                <tr>
                  <th rowspan="2">Particulars</th>
                  <th rowspan="2">Year 1</th>
                  <th rowspan="2">Year 2</th>
                  <th rowspan="2">Year 3</th>
                  <th rowspan="2">Year 4</th>
                  <th rowspan="2">Year 5</th>
                  <th colspan="2">Supported w/ Breakdown?</th>
                </tr>
                <tr><th>Yes</th><th>No</th></tr>
              </thead>
              <tbody>
                <tr>
                  <td>Personal Services</td>
                  <td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td>
                  <td class="center-text"><input type="radio" name="bdg_ps" value="yes"></td>
                  <td class="center-text"><input type="radio" name="bdg_ps" value="no"></td>
                </tr>
                <tr>
                  <td>MOOE</td>
                  <td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td>
                  <td class="center-text"><input type="radio" name="bdg_mooe" value="yes"></td>
                  <td class="center-text"><input type="radio" name="bdg_mooe" value="no"></td>
                </tr>
                <tr>
                  <td>Capital Outlay</td>
                  <td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td>
                  <td class="center-text"><input type="radio" name="bdg_co" value="yes"></td>
                  <td class="center-text"><input type="radio" name="bdg_co" value="no"></td>
                </tr>
                <tr>
                  <td><strong>Total</strong></td>
                  <td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td>
                  <td></td><td></td>
                </tr>
              </tbody>
            </table>
            <br>

            <div class="label-row">
              <label>Budget Proposal to be Allocated in Multi-Year?</label>
              <label><input type="radio" name="multi_year" value="yes"> YES</label>
              <label><input type="radio" name="multi_year" value="no"> NO</label>
            </div>
            <div class="label-row"><label>If yes, how many years?</label> <input type="number" style="width: 60px;"></div>
            <div class="label-row">
              <label>Source of Fund for initial operation:</label>
              <label><input type="radio" name="fund_source" value="deped"> DepED</label>
              <label><input type="radio" name="fund_source" value="lgu"> LGU</label>
            </div>
          </td>
          <td><textarea class="full-width" rows="15" placeholder="PASSED or FAILED"></textarea></td>
        </tr>

        <tr>
          <td></td>
          <td><strong>c.</strong> Division Inspection Report signed by the SDS</td>
          <td>
            <strong>c. DREC Inspection In Order?</strong>
            <table class="nested-table">
              <thead>
                <tr>
                  <th rowspan="2">PARTICULARS</th>
                  <th colspan="2">COMPLIANT TO STANDARDS?</th>
                </tr>
                <tr><th>YES</th><th>NO</th></tr>
              </thead>
              <tbody>
                <tr><td>1. School Building/Classroom</td><td class="center-text"><input type="radio" name="insp_1" value="yes"></td><td class="center-text"><input type="radio" name="insp_1" value="no"></td></tr>
                <tr><td>2. No. of available classrooms, if any</td><td class="center-text"><input type="radio" name="insp_2" value="yes"></td><td class="center-text"><input type="radio" name="insp_2" value="no"></td></tr>
                <tr><td>3. Size of school site in square meters</td><td class="center-text"><input type="radio" name="insp_3" value="yes"></td><td class="center-text"><input type="radio" name="insp_3" value="no"></td></tr>
              </tbody>
            </table>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td><strong>2.</strong> The proposed establishment of school must be supported by the LGU</td>
          <td>Sangguniang Bayan/Panglungsod Resolution supporting the establishment of school, duly approved by the Municipal/City Mayor, indicating therein the proposed name of the school.</td>
          <td>
            <div class="label-row"><label>Sangguniang Bayan/Panglungsod Resolution No.</label> <input type="text"></div>
            <div class="label-row"><label>Approved By:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
            <div class="label-row"><label>Position/Designation:</label> <input type="text"></div>
            <div class="label-row"><label>Proposed Name of School:</label> <input type="text"></div>

            <div style="margin-top: 10px; margin-bottom: 5px;">
              <label style="font-weight: bold;">Resolution stipulates willingness of LGU to provide financial support?</label>
              <div style="display: inline-block; margin-left: 10px;">
                <label style="margin-right: 15px; cursor: pointer;">
                  <input type="radio" name="lgu_fin" value="yes"> YES
                </label>
                <label style="cursor: pointer;">
                  <input type="radio" name="lgu_fin" value="no"> NO
                </label>
              </div>
            </div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td>
            <strong>3.</strong> The proposed school must have at least 100 pupils/students composed of one or more grade levels.<br><br>
            <small>In case the criterion is not met, the SDS shall make necessary justification.</small>
          </td>
          <td>
            List of prospective enrollees per grade level, indicating their names, ages, addresses and/or school where they are currently or were enrolled.<br><br>
            Justification by the SDS on the need to establish a school, if necessary.
          </td>
          <td>
            <strong>No. of Prospective Enrollees:</strong>
            <table class="nested-table" style="width: 50%;">
              <tr><th>Grade Level</th><th>No. of Enrollees</th></tr>
              <tr><td>1</td><td><input type="number"></td></tr>
              <tr><td>2</td><td><input type="number"></td></tr>
              <tr><td>3</td><td><input type="number"></td></tr>
              <tr><td>4</td><td><input type="number"></td></tr>
              <tr><td>5</td><td><input type="number"></td></tr>
              <tr><td>6</td><td><input type="number"></td></tr>
              <tr><td>7</td><td><input type="number"></td></tr>
              <tr><td>8</td><td><input type="number"></td></tr>
              <tr><td>9</td><td><input type="number"></td></tr>
              <tr><td>10</td><td><input type="number"></td></tr>
              <tr><td><strong>TOTAL</strong></td><td><strong><input type="number"></strong></td></tr>
            </table>
            <br>

            <strong>List contains complete information as to the:</strong>
            <table class="nested-table">
              <tr><th>PARTICULARS</th><th>YES</th><th>NO</th></tr>
              <tr><td>1. Names of pupils/students</td><td class="center-text"><input type="radio" name="info_1" value="yes"></td><td class="center-text"><input type="radio" name="info_1" value="no"></td></tr>
              <tr><td>2. Ages</td><td class="center-text"><input type="radio" name="info_2" value="yes"></td><td class="center-text"><input type="radio" name="info_2" value="no"></td></tr>
              <tr><td>3. Addresses and/or school history</td><td class="center-text"><input type="radio" name="info_3" value="yes"></td><td class="center-text"><input type="radio" name="info_3" value="no"></td></tr>
              <tr><td>4. Bonafide residents of barangay/municipality</td><td class="center-text"><input type="radio" name="info_4" value="yes"></td><td class="center-text"><input type="radio" name="info_4" value="no"></td></tr>
            </table>
            <br>

            <div class="label-row"><label>Justification Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Position/Designation:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td><strong>4.</strong> There is no private high school participating in the Government Assistance to Students and Teachers in Private Education (GASTPE) Program of DepED; or the GASTPE recipient school(s) has reached its allocation or number of available slots.</td>
          <td>
            <strong>a.</strong> Certification from the SDS that no private high school within the Municipality/City is participating in the GASTPE Program, or that GASTPE school has reached allocation.
          </td>
          <td>
            <div class="label-row"><label>a. Certification Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Position/Designation:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
            <br>

            <strong>GASTPE Participating High School(s)</strong>
            <table class="nested-table">
              <tr><th>Name of School</th><th>No. of Slots Allocated</th><th>No of Slots Filled</th><th>Remarks</th></tr>
              <tr><td><input type="text"></td><td><input type="number"></td><td><input type="number"></td><td><input type="text"></td></tr>
              <tr><td><input type="text"></td><td><input type="number"></td><td><input type="number"></td><td><input type="text"></td></tr>
            </table>
            <br>

            <strong>b. Justification by SDS (if criteria not met):</strong>
            <div class="label-row"><label>Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Reasons:</label> <textarea rows="2" class="full-width"></textarea></div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td>
            <strong>5.</strong> The proposed school is not within the 2-km and 1 km radius from any existing public school in rural and urban areas, respectively.<br><br>
            <small>Exceptions apply for waived limitations.</small>
          </td>
          <td>
            <strong>a.</strong> Map, preferably drawn to scale, showing distances.<br><br>
            <strong>b.</strong> Certification from Municipal/City Engineer.<br><br>
            <strong>c.</strong> OR Justification by SDS for waiver.
          </td>
          <td>
            <div class="label-row">
              <label>Map shows distances?</label>
              <label><input type="radio" name="map_dist" value="yes"> YES</label>
              <label><input type="radio" name="map_dist" value="no"> NO</label>
            </div>

            <table class="nested-table">
              <tr><th>Name of Nearest School</th><th>Distance to Proposed School (km)</th><th>Address</th></tr>
              <tr><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td></tr>
              <tr><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td></tr>
              <tr><td><input type="text"></td><td><input type="text"></td><td><input type="text"></td></tr>
            </table>
            <br>

            <div class="label-row"><label>b. Certification Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Position/Designation:</label> <input type="text"></div>
            <div class="label-row"><label>Office:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>c. Justification Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Reasons:</label> <textarea rows="2" class="full-width"></textarea></div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td>
            <strong>6.</strong> Existence and availability of a school site of at least 5,000 sqm (rural) or 2,500 sqm (urban).<br><br>
            <small>Justification required if not met.</small>
          </td>
          <td>
            <strong>a.</strong> Any document (Deed of Donation, Sale, Usufruct, OCT/TCT) in name of DepED.<br><br>
            <strong>b.</strong> Justification from SDS if size not met.
          </td>
          <td>
            <div class="label-row"><label>a. Document Submitted:</label> <input type="text" placeholder="e.g., Deed of Donation"></div>
            <div class="label-row">
              <label>In favor/name of DepED?</label>
              <label><input type="radio" name="deed_deped" value="yes"> YES</label>
              <label><input type="radio" name="deed_deped" value="no"> NO</label>
            </div>
            <div class="label-row"><label>Address/Location:</label> <input type="text"></div>
            <div class="label-row"><label>Size (sqm):</label> <input type="number"></div>
            <br>

            <div class="label-row"><label>b. Justification Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Reasons:</label> <textarea rows="2" class="full-width"></textarea></div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td><strong>7.</strong> School site must not be a high-risk area (safe/potable water, no flooding/erosion).</td>
          <td>Clearance/permit from MGB and DENR stating site is not high-risk.</td>
          <td>
            <div class="label-row"><label>a. Clearance from MGB Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Position:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>b. Certification from DENR Signed By:</label> <input type="text"></div>
            <div class="label-row"><label>Position:</label> <input type="text"></div>
            <div class="label-row"><label>Date:</label> <input type="date"></div>
            <div class="label-row">
              <label>Declared Safe/Not High Risk?</label>
              <label><input type="radio" name="safe_risk" value="yes"> YES</label>
              <label><input type="radio" name="safe_risk" value="no"> NO</label>
            </div>
          </td>
          <td><textarea class="full-width" rows="5"></textarea></td>
        </tr>

        <tr>
          <td><strong>8.</strong> Must have at least two (2) classrooms for initial operation (standard 7m x 9m).</td>
          <td>
            a. School site development plan<br>
            b. School Building plan<br>
            c. Design approved by DepED<br>
            d. Building Permit<br>
            e. BFP Certificate<br>
            f. Inspection Report
          </td>
          <td>
            <div class="label-row"><label>a. Plan Prepared By:</label> <input type="text"></div>
            <div class="label-row"><label>Date Prepared:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>b. Building Plan Prepared By:</label> <input type="text"></div>
            <div class="label-row"><label>No. of Classrooms:</label> <input type="number"></div>
            <div class="label-row">
              <label>Tech Specs Attached?</label>
              <label><input type="radio" name="specs_att" value="yes"> YES</label>
              <label><input type="radio" name="specs_att" value="no"> NO</label>
            </div>
            <div class="label-row">
              <label>Compliant with DepED Stds?</label>
              <label><input type="radio" name="comp_std" value="yes"> YES</label>
              <label><input type="radio" name="comp_std" value="no"> NO</label>
            </div>
            <div class="label-row"><label>If no, state deviation:</label> <input type="text"></div>
            <br>

            <div class="label-row"><label>c. Design Approved By:</label> <input type="text"></div>
            <div class="label-row"><label>Date Approved:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>d. Permit Issued By:</label> <input type="text"></div>
            <div class="label-row"><label>Date Issued:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>e. BFP Cert Issued By:</label> <input type="text"></div>
            <div class="label-row"><label>Date Issued:</label> <input type="date"></div>
            <br>

            <div class="label-row"><label>f. Inspection Report By:</label> <input type="text"></div>
            <div class="label-row"><label>Date Issued:</label> <input type="date"></div>
          </td>
          <td><textarea class="full-width" rows="10"></textarea></td>
        </tr>

        <tr>
          <td><strong>9.</strong> The LGU or DepED Division Office has adequate funds for initial operation (salaries, allowances, maintenance).</td>
          <td>
            a. Duly notarized MOA by and between DepED and LGU.<br>
            1. Construction<br>
            2. Procurement<br>
            3. Operation/Maintenance (5 yrs)<br>
            4. Salaries<br><br>
            b. Certification from SDS on sufficient funds.
            c. List of personnel to be borrowed.
          </td>
          <td>
            <strong>a. Signatories of Contracting Parties</strong>
            <div class="label-row"><label>1. For DepED (SDS):</label> <input type="text"></div>
            <div class="label-row"><label>2. For LGU:</label> <input type="text"></div>
            <div class="label-row"><label>Date Notarized:</label> <input type="date"></div>
            <br>

            <strong>LGU Support Specified in MOA:</strong>
            <table class="nested-table">
              <tr><th>Particulars</th><th>Amount</th></tr>
              <tr><td>a. Construction of new building(s)</td><td>PhP <input type="number"></td></tr>
              <tr><td>b. Procurement (facilities, furniture, materials)</td><td>PhP <input type="number"></td></tr>
              <tr><td>c. Operation/Maintenance (5 years)</td><td>PhP <input type="number"></td></tr>
              <tr><td>d. Salaries (Teaching/Non-teaching)</td><td>PhP <input type="number"></td></tr>
              <tr><td>e. Other LGU Support</td><td>PhP <input type="number"></td></tr>
              <tr><td><strong>TOTAL</strong></td><td><strong>PhP <input type="number"></strong></td></tr>
            </table>
            <br>

            <div class="label-row"><label>Availability of Funds Certified by:</label> <input type="text"></div>
            <div class="label-row"><label>Position:</label> <input type="text"></div>
            <br>

            <strong>b. Certification Signed By (SDS):</strong> <input type="text">
            <br><br>

            <strong>c. Personnel Borrowing</strong>
            <div class="label-row"><label>No. of Teachers to be Borrowed:</label> <input type="number"></div>
            <div class="label-row"><label>No. of Non-Teaching Personnel:</label> <input type="number"></div>

            <table class="nested-table">
              <tr><th>PARTICULARS</th><th>YES</th><th>NO</th></tr>
              <tr><td>1. Position Titles</td><td class="center-text"><input type="radio" name="borrow_1" value="yes"></td><td class="center-text"><input type="radio" name="borrow_1" value="no"></td></tr>
              <tr><td>2. Item Number per latest PSIPOP</td><td class="center-text"><input type="radio" name="borrow_2" value="yes"></td><td class="center-text"><input type="radio" name="borrow_2" value="no"></td></tr>
              <tr><td>3. Name of Lending School</td><td class="center-text"><input type="radio" name="borrow_3" value="yes"></td><td class="center-text"><input type="radio" name="borrow_3" value="no"></td></tr>
            </table>
          </td>
          <td><textarea class="full-width" rows="10"></textarea></td>
        </tr>

      </tbody>
    </table>

    <!-- SIGNATURES -->
    <div class="signature-section">
      <div class="full-width"><strong>EVALUATED BY DIVISION REVIEW AND EVALUATION COMMITTEE (DREC):</strong></div>
      <br><br><br>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="sig-block" style="width: 100%; margin-top: 20px;">
        <strong>RECOMMENDED BY:</strong><br><br><br>
        <input type="text" class="sig-input" placeholder="Type SDS Name Here" style="width: 40%;">
        <br><strong>Schools Division Superintendent</strong>
      </div>
    </div>

    <div class="signature-section">
      <div class="full-width"><strong>VALIDATED BY REGIONAL INSPECTORATE TEAM (RIT):</strong></div>
      <br><br><br>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="sig-block">
        <input type="text" class="sig-input" placeholder="Type Name Here">
        <span class="sig-label">Signature Over Printed Name</span><br>
        <input type="text" class="sig-input" placeholder="Type Position Here">
        <span class="sig-label">Position/Designation</span>
      </div>

      <div class="full-width" style="margin-top:20px;"><strong>APPROVED:</strong></div>
      <br><br><br>

      <div class="sig-block" style="width: 100%;">
        <input type="text" class="sig-input" placeholder="Type Regional Director Name Here" style="width: 40%;">
        <br><strong>Regional Director</strong>
      </div>
    </div>
    <!-- ✅ YOUR FULL ANNEX D-1 TABLE ENDS HERE -->

  </form>
</div>

<script>
const D1 = {
  report_id: <?php echo (int)$report_id; ?>,

  setStatus(text){
    const el = document.getElementById('d1-status');
    if(el) el.textContent = text;
  },

  // ✅ Auto-serialize ALL fields
  serialize(){
    const form = document.getElementById('d1-form');
    const fields = form.querySelectorAll('input, textarea, select');

    const data = {};
    fields.forEach((el, idx) => {
      let key = el.name && el.name.trim() ? el.name.trim() : (el.getAttribute('data-key') || '');

      if(!key){
        key = `field_${idx}`;
        el.setAttribute('data-key', key);
      }

      if(el.type === 'radio'){
        if(el.checked) data[key] = el.value;
        else if(data[key] === undefined) data[key] = "";
      } else if(el.type === 'checkbox'){
        data[key] = el.checked ? "1" : "0";
      } else {
        data[key] = el.value;
      }
    });

    return data;
  },

  // ✅ Fill fields from saved JSON
  fill(saved){
    const form = document.getElementById('d1-form');
    const fields = form.querySelectorAll('input, textarea, select');

    fields.forEach((el, idx) => {
      let key = el.name && el.name.trim() ? el.name.trim() : (el.getAttribute('data-key') || '');
      if(!key){
        key = `field_${idx}`;
        el.setAttribute('data-key', key);
      }

      if(saved[key] === undefined) return;

      if(el.type === 'radio'){
        el.checked = (String(el.value) === String(saved[key]));
      } else if(el.type === 'checkbox'){
        el.checked = (String(saved[key]) === "1");
      } else {
        el.value = saved[key];
      }
    });
  },

  async load(){
    D1.setStatus("Loading…");
    const res = await fetch(`api.php?action=get_d1&report_id=${encodeURIComponent(D1.report_id)}`, {
      credentials: 'same-origin'
    });
    const d = await res.json();

    if(d.success){
      if(d.data) D1.fill(d.data);
      const st = d.status ? d.status.toUpperCase() : "NEW";
      D1.setStatus(`D1: ${st}`);
    } else {
      D1.setStatus("D1: ERROR");
      alert(d.message || "Failed to load D1");
    }
  },

  async save(){
    const payload = {
      report_id: D1.report_id,
      form_data: D1.serialize()
    };

    const res = await fetch('api.php?action=save_d1', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body: JSON.stringify(payload)
    });

    const d = await res.json();
    if(d.success){
      D1.setStatus("D1: DRAFT");
      alert("D1 saved (Draft) ✅");
    } else {
      alert(d.message || "Save failed ❌");
    }
  },

  async submit(){
    if(!confirm("Submit D1 FINAL?")) return;

    const payload = {
      report_id: D1.report_id,
      form_data: D1.serialize()
    };

    const res = await fetch('api.php?action=submit_d1', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      credentials:'same-origin',
      body: JSON.stringify(payload)
    });

    const d = await res.json();
    if(d.success){
      D1.setStatus("D1: FINAL");
      alert("D1 submitted successfully ✅");
      window.location.href = "dashboard.php";
    } else {
      alert(d.message || "Submit failed ❌");
    }
  }
};

D1.load();
</script>

</body>
</html>
