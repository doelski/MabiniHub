<?php
require_once __DIR__ . '/../auth_guard.php';
// Allow Department Head, Employee, HR, and Municipal Admin to access
if (function_exists('is_municipal_admin') && is_municipal_admin()) {
  // Municipal admin is authenticated via a separate mechanism; allow through
} else {
  require_role(['department_head', 'employee', 'hr']);
}
require_once '../db.php';
// Get leave request ID from URL
$leave_id = $_GET['id'] ?? null;
$leave = null;
$user = null;
if ($leave_id) {
    // Fetch leave request
    $stmt = $pdo->prepare('SELECT * FROM leave_requests WHERE id = ?');
    $stmt->execute([$leave_id]);
    $leave = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($leave) {
        // Fetch user info by employee_email
        $stmt2 = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt2->execute([$leave['employee_email']]);
        $user = $stmt2->fetch(PDO::FETCH_ASSOC);
    // Try to fetch salary from employees table if available
    try {
      $stmt3 = $pdo->prepare('SELECT salary FROM employees WHERE email = ?');
      $stmt3->execute([$leave['employee_email']]);
      $empSalaryRow = $stmt3->fetch(PDO::FETCH_ASSOC);
      if ($empSalaryRow && isset($empSalaryRow['salary'])) {
        $user['salary'] = $empSalaryRow['salary'];
      }
    } catch (PDOException $e) { /* ignore */ }
    }
}

function field($arr, $key) {
    return isset($arr[$key]) ? $arr[$key] : '';
}
// Decode structured details JSON
$details = [];
if (!empty($leave['details'])) {
  $d = json_decode($leave['details'], true);
  if (is_array($d)) $details = $d;
}

// Resolve leave credit display values using multiple fallbacks so that
// values stored either in top-level columns or inside the details JSON
// (including recommendation_fallback or hr.section7) will be shown.
$certification_date_display = '';
$vl_total_earned_display = '';
$vl_less_display = '';
$vl_balance_display = '';
$sl_total_display = '';
$sl_less_display = '';
$sl_balance_display = '';

// Helper to read a nested key from possible places
$readFromDetails = function($keys) use ($leave, $details) {
  // 1) top-level $leave (use isset/array_key_exists to avoid PHP notices)
  foreach ($keys as $k) {
    if (isset($leave[$k]) && ($leave[$k] !== '' || $leave[$k] === '0' || $leave[$k] === 0)) {
      return $leave[$k];
    }
  }
  // 2) recommendation_fallback -> leave_credits (safely check existence)
  if (isset($details['recommendation_fallback']) && isset($details['recommendation_fallback']['leave_credits']) && is_array($details['recommendation_fallback']['leave_credits'])) {
    $lc = $details['recommendation_fallback']['leave_credits'];
    foreach ($keys as $k) {
      if (isset($lc[$k]) && ($lc[$k] !== '')) return $lc[$k];
    }
  }
  // 3) hr.section7 (safely check existence)
  if (isset($details['hr']) && isset($details['hr']['section7']) && is_array($details['hr']['section7'])) {
    $s7 = $details['hr']['section7'];
    foreach ($keys as $k) {
      if (isset($s7[$k]) && ($s7[$k] !== '')) return $s7[$k];
    }
  }
  return '';
};

$certification_date_display = $readFromDetails(['certification_date', 'certificationDate', 'certification_date']);
$vl_total_earned_display = $readFromDetails(['vl_total_earned', 'vl_total_earned']);
$vl_less_display = $readFromDetails(['vl_less_this_application', 'vl_less_this_application']);
$vl_balance_display = $readFromDetails(['vl_balance', 'vl_balance']);
$sl_total_display = $readFromDetails(['sl_total_earned', 'sl_total_earned']);
$sl_less_display = $readFromDetails(['sl_less_this_application', 'sl_less_this_application']);
$sl_balance_display = $readFromDetails(['sl_balance', 'sl_balance']);
// Prefer the salary the employee actually entered on the form
$displaySalary = '';
// Prefer exactly what the employee typed.
if (!empty($details['snapshot']['salary']['value'])) {
  $displaySalary = $details['snapshot']['salary']['value'];
} elseif (!empty($details['salary'])) {
  // Legacy path (submitted from Apply Leave page): salary saved directly in details
  $displaySalary = $details['salary'];
} elseif (!empty($user['salary'])) {
  // Fallback to DB salary if nothing was supplied in the form
  $displaySalary = $user['salary'];
}

// Helper: format name as "firstname MI. lastname" (uppercase)
function format_dept_head_name($firstname, $mi, $lastname) {
  $firstname = trim((string)$firstname);
  $mi = trim((string)$mi);
  $lastname = trim((string)$lastname);
  
  $parts = [];
  if ($firstname !== '') $parts[] = $firstname;
  if ($mi !== '') {
    // Take first character and add dot
    $parts[] = strtoupper(mb_substr($mi, 0, 1, 'UTF-8')) . '.';
  }
  if ($lastname !== '') $parts[] = $lastname;
  
  $name = implode(' ', $parts);
  return mb_strtoupper($name, 'UTF-8');
}

// Hardcoded signatories and department head resolution
$adminAideName = 'ALMA D. ILAO';
$municipalAdminName = 'ATTY. MARIA CONCEPCION R. HERNANDEZ-BELOSO';

$deptHeadName = 'Department Head';
$deptHeadSig = '';
// Primary: try a number of possible leave fields that may contain the assigned dept head
$possibleDeptHeadFields = [
  'dept_head_email', 'deptHead', 'dept_head', 'deptHeadEmail', 'dept_head_email', 'dept_head_name', 'deptHeadName'
];
$resolved = false;
foreach ($possibleDeptHeadFields as $f) {
  if ($resolved) break;
  if (!empty($leave[$f])) {
    $val = trim($leave[$f]);
    // If it looks like an email, fetch the user by email
    if (strpos($val, '@') !== false) {
      try {
        $stmtAssigned = $pdo->prepare('SELECT firstname, lastname, mi, department, signature_path, signature, sig_path FROM users WHERE email = ? LIMIT 1');
        $stmtAssigned->execute([$val]);
        $assigned = $stmtAssigned->fetch(PDO::FETCH_ASSOC);
        if ($assigned) {
          $deptHeadName = format_dept_head_name($assigned['firstname'] ?? '', $assigned['mi'] ?? '', $assigned['lastname'] ?? '');
          if (!empty($assigned['signature_path'])) $deptHeadSig = $assigned['signature_path'];
          elseif (!empty($assigned['signature'])) $deptHeadSig = $assigned['signature'];
          elseif (!empty($assigned['sig_path'])) $deptHeadSig = $assigned['sig_path'];
          $resolved = true;
          break;
        }
      } catch (PDOException $e) { /* ignore */ }
      // Fallback: if user table has no signature, try employee_signatures table
      if (!$deptHeadSig) {
        try {
          $stEmpSig = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ? LIMIT 1');
          $stEmpSig->execute([$val]);
          $sigRow = $stEmpSig->fetch(PDO::FETCH_ASSOC);
          if ($sigRow && !empty($sigRow['file_path'])) {
            $deptHeadSig = $sigRow['file_path'];
          }
        } catch (PDOException $e) { /* ignore */ }
      }
    }
    // If it's not an email but looks like a name, use it directly
    // NOTE: avoid treating raw email strings as names — require the value
    // to NOT contain an '@' before accepting it as a name.
    if (!$resolved && strpos($val, '@') === false && preg_match('/[A-Za-z]/', $val)) {
      $deptHeadName = $val;
      $resolved = true;
      break;
    }
  }
}

// Secondary: if no explicit assigned dept head, try to resolve by the employee's department
if ($deptHeadName === 'Department Head' && !empty($user['department'])) {
  try {
    // Try to find a department head by common role keywords within the employee's department
    $stmtDH = $pdo->prepare("SELECT firstname, lastname, mi, position, signature_path, signature, sig_path FROM users WHERE department = ? AND (position LIKE '%Head%' OR position LIKE '%head%' OR position LIKE '%Chief%' OR position LIKE '%chief%' OR position LIKE '%Officer%' OR position LIKE '%officer%') LIMIT 1");
    $stmtDH->execute([$user['department']]);
    $dh = $stmtDH->fetch(PDO::FETCH_ASSOC);
    if ($dh) {
      $deptHeadName = format_dept_head_name($dh['firstname'] ?? '', $dh['mi'] ?? '', $dh['lastname'] ?? '');
      if (!empty($dh['signature_path'])) $deptHeadSig = $dh['signature_path'];
      elseif (!empty($dh['signature'])) $deptHeadSig = $dh['signature'];
      elseif (!empty($dh['sig_path'])) $deptHeadSig = $dh['sig_path'];
    } else {
      // fallback: first user in the same department
      $stmtDH2 = $pdo->prepare("SELECT firstname, lastname, mi, signature_path, signature, sig_path FROM users WHERE department = ? LIMIT 1");
      $stmtDH2->execute([$user['department']]);
      $dh2 = $stmtDH2->fetch(PDO::FETCH_ASSOC);
      if ($dh2) {
        $deptHeadName = format_dept_head_name($dh2['firstname'] ?? '', $dh2['mi'] ?? '', $dh2['lastname'] ?? '');
        if (!empty($dh2['signature_path'])) $deptHeadSig = $dh2['signature_path'];
        elseif (!empty($dh2['signature'])) $deptHeadSig = $dh2['signature'];
        elseif (!empty($dh2['sig_path'])) $deptHeadSig = $dh2['sig_path'];
      }
    }
  } catch (PDOException $e) { /* ignore */ }
}

// If still not resolved and someone is logged in, prefer showing the
// logged-in user's name/signature. This guarantees that when a dept head
// is viewing the form in their portal, their real-time name shows up.
if ($deptHeadName === 'Department Head') {
  $sessEmail = $_SESSION['email'] ?? null;
  $sessUserId = $_SESSION['user_id'] ?? null;
  if ($sessEmail || $sessUserId) {
    try {
      if (!empty($sessUserId) && preg_match('/^\d+$/', (string)$sessUserId)) {
        $stMe = $pdo->prepare('SELECT firstname, lastname, mi, position, department, signature_path, signature, sig_path FROM users WHERE id = ? LIMIT 1');
        $stMe->execute([$sessUserId]);
      } else {
        $stMe = $pdo->prepare('SELECT firstname, lastname, mi, position, department, signature_path, signature, sig_path FROM users WHERE email = ? LIMIT 1');
        $stMe->execute([$sessEmail]);
      }
      $me = $stMe->fetch(PDO::FETCH_ASSOC);
      if ($me) {
        $deptHeadName = format_dept_head_name($me['firstname'] ?? '', $me['mi'] ?? '', $me['lastname'] ?? '');
        if (!empty($me['signature_path'])) $deptHeadSig = $me['signature_path'];
        elseif (!empty($me['signature'])) $deptHeadSig = $me['signature'];
        elseif (!empty($me['sig_path'])) $deptHeadSig = $me['sig_path'];
        // Extra fallback: try employee_signatures table for the logged-in dept head
        if (!$deptHeadSig && $sessEmail) {
          try {
            $stEmpSig = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ? LIMIT 1');
            $stEmpSig->execute([$sessEmail]);
            $sigRow = $stEmpSig->fetch(PDO::FETCH_ASSOC);
            if ($sigRow && !empty($sigRow['file_path'])) {
              $deptHeadSig = $sigRow['file_path'];
            }
          } catch (PDOException $e) { /* ignore */ }
        }
        $resolved = true;
      }
    } catch (PDOException $e) { /* ignore */ }
  }
}

// Final: if still unresolved, try a robust department-head lookup using
// multiple possible department sources and return the current user in the
// `users` table whose position suggests leadership in that department.
if ($deptHeadName === 'Department Head') {
  // Try to determine the employee's department from several places
  $deptCandidates = [];
  if (!empty($user['department'])) $deptCandidates[] = $user['department'];
  if (!empty($leave['department'])) $deptCandidates[] = $leave['department'];
  if (!empty($leave['dept'])) $deptCandidates[] = $leave['dept'];
  if (!empty($leave['department_name'])) $deptCandidates[] = $leave['department_name'];
  if (!empty($details['department'])) $deptCandidates[] = $details['department'];
  if (!empty($details['snapshot']['department']['value'])) $deptCandidates[] = $details['snapshot']['department']['value'];
  // normalize and pick first non-empty
  $dept = null;
  foreach ($deptCandidates as $dc) {
    $dc = trim((string)$dc);
    if ($dc !== '') { $dept = $dc; break; }
  }

  if (!empty($dept)) {
    try {
      // Prefer users whose position explicitly contains Head/Chief/Officer
      // Order so strong matches come first. Limit 1.
      $q = "SELECT firstname, lastname, mi, position, signature_path, signature, sig_path FROM users WHERE department = ? AND (position LIKE '%Head%' OR position LIKE '%head%' OR position LIKE '%Chief%' OR position LIKE '%chief%' OR position LIKE '%Officer%' OR position LIKE '%officer%') ORDER BY CASE WHEN position LIKE '%Head%' THEN 0 WHEN position LIKE '%Chief%' THEN 1 WHEN position LIKE '%Officer%' THEN 2 ELSE 3 END LIMIT 1";
      $st = $pdo->prepare($q);
      $st->execute([$dept]);
      $found = $st->fetch(PDO::FETCH_ASSOC);
      if ($found) {
        $deptHeadName = format_dept_head_name($found['firstname'] ?? '', $found['mi'] ?? '', $found['lastname'] ?? '');
        if (!empty($found['signature_path'])) $deptHeadSig = $found['signature_path'];
        elseif (!empty($found['signature'])) $deptHeadSig = $found['signature'];
        elseif (!empty($found['sig_path'])) $deptHeadSig = $found['sig_path'];
        $resolved = true;
      }
    } catch (PDOException $e) { /* ignore */ }
  }
}

// If still unresolved, allow forcing via GET params: dept_head_email or dept
if ($deptHeadName === 'Department Head') {
  $forcedEmail = $_GET['dept_head_email'] ?? $_GET['dept_email'] ?? null;
  $forcedDept = $_GET['dept'] ?? null;
  if ($forcedEmail) {
    try {
      $st = $pdo->prepare('SELECT firstname, lastname, mi, signature_path, signature, sig_path FROM users WHERE email = ? LIMIT 1');
      $st->execute([$forcedEmail]);
      $f = $st->fetch(PDO::FETCH_ASSOC);
      if ($f) {
        $deptHeadName = format_dept_head_name($f['firstname'] ?? '', $f['mi'] ?? '', $f['lastname'] ?? '');
        if (!empty($f['signature_path'])) $deptHeadSig = $f['signature_path'];
        elseif (!empty($f['signature'])) $deptHeadSig = $f['signature'];
        elseif (!empty($f['sig_path'])) $deptHeadSig = $f['sig_path'];
      }
    } catch (PDOException $e) { }
  } elseif ($forcedDept) {
    try {
      $st2 = $pdo->prepare("SELECT firstname, lastname, mi, signature_path, signature, sig_path FROM users WHERE department = ? AND (position LIKE '%Head%' OR position LIKE '%head%' OR position LIKE '%Chief%' OR position LIKE '%chief%') LIMIT 1");
      $st2->execute([$forcedDept]);
      $f2 = $st2->fetch(PDO::FETCH_ASSOC);
      if ($f2) {
        $deptHeadName = format_dept_head_name($f2['firstname'] ?? '', $f2['mi'] ?? '', $f2['lastname'] ?? '');
        if (!empty($f2['signature_path'])) $deptHeadSig = $f2['signature_path'];
        elseif (!empty($f2['signature'])) $deptHeadSig = $f2['signature'];
        elseif (!empty($f2['sig_path'])) $deptHeadSig = $f2['sig_path'];
      }
    } catch (PDOException $e) { }
  }
}

// If the value we resolved (or a fallback value) still looks like an email
// (for example the leave stored an email string), try a final lookup so
// the displayed value is the real-time full name from the users table.
try {
  if (strpos($deptHeadName, '@') !== false) {
    $stEmail = $pdo->prepare('SELECT firstname, lastname, mi, signature_path, signature, sig_path FROM users WHERE email = ? LIMIT 1');
    $stEmail->execute([$deptHeadName]);
    $fe = $stEmail->fetch(PDO::FETCH_ASSOC);
    if ($fe) {
      $deptHeadName = format_dept_head_name($fe['firstname'] ?? '', $fe['mi'] ?? '', $fe['lastname'] ?? '');
      if (!empty($fe['signature_path'])) $deptHeadSig = $fe['signature_path'];
      elseif (!empty($fe['signature'])) $deptHeadSig = $fe['signature'];
      elseif (!empty($fe['sig_path'])) $deptHeadSig = $fe['sig_path'];
      // Fallback to employee_signatures if still missing
      if (!$deptHeadSig && !empty($leave['dept_head_email'])) {
        try {
          $stEmpSig = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ? LIMIT 1');
          $stEmpSig->execute([$leave['dept_head_email']]);
          $sigRow = $stEmpSig->fetch(PDO::FETCH_ASSOC);
          if ($sigRow && !empty($sigRow['file_path'])) {
            $deptHeadSig = $sigRow['file_path'];
          }
        } catch (PDOException $e) { /* ignore */ }
      }
    }
  }
  // Also handle the case where the leave stored a numeric user id for the dept head
  // (try to resolve that to a name). Guard against missing array key.
  $maybeIdRaw = isset($leave['dept_head']) ? $leave['dept_head'] : null;
  if (!$deptHeadSig && $maybeIdRaw !== null) {
    $maybeId = trim((string)$maybeIdRaw);
    if (!preg_match('/^\d+$/', $maybeId)) {
      $maybeId = '';
    }
  }
  if (!$deptHeadSig && !empty($maybeId)) {
    $stId = $pdo->prepare('SELECT firstname, lastname, mi, signature_path, signature, sig_path FROM users WHERE id = ? LIMIT 1');
    $stId->execute([$maybeId]);
    $fi = $stId->fetch(PDO::FETCH_ASSOC);
    if ($fi) {
      $deptHeadName = format_dept_head_name($fi['firstname'] ?? '', $fi['mi'] ?? '', $fi['lastname'] ?? '');
      if (!empty($fi['signature_path'])) $deptHeadSig = $fi['signature_path'];
      elseif (!empty($fi['signature'])) $deptHeadSig = $fi['signature'];
      elseif (!empty($fi['sig_path'])) $deptHeadSig = $fi['sig_path'];
    }
  }
} catch (PDOException $e) { /* ignore final resolution errors */ }

// If still unresolved, try to use the name captured in details.hr.section7.authorized_officer_7b
// which the dept head page saves when they review/submit.
if ($deptHeadName === 'Department Head') {
  $dhFromDetails = $details['hr']['section7']['authorized_officer_7b'] ?? '';
  if (!empty($dhFromDetails) && is_string($dhFromDetails)) {
    $deptHeadName = trim($dhFromDetails);
  }
}

// Ensure the department head name is uppercase like the other signatories
$deptHeadName = mb_strtoupper($deptHeadName, 'UTF-8');

// Resolve recommendation and disapproval reasons using fallbacks so that values
// saved either in top-level columns or in details JSON are shown here.
$recommendation_display = '';
$disapproval_reason1_display = '';
$disapproval_reason2_display = '';
$disapproval_reason3_display = '';

// Top-level recommendation
if (!empty($leave['recommendation'])) {
  $recommendation_display = $leave['recommendation'];
} elseif (isset($details['recommendation_fallback']) && !empty($details['recommendation_fallback']['recommendation'])) {
  $recommendation_display = $details['recommendation_fallback']['recommendation'];
} elseif (!empty($details['recommendation'])) {
  $recommendation_display = $details['recommendation'];
} elseif (!empty($details['hr']['section7']['recommendation'])) {
  $recommendation_display = $details['hr']['section7']['recommendation'];
}

// Disapproval reasons: prefer top-level columns, then recommendation_fallback, then hr.section7
if (isset($leave['disapproval_reason1']) && $leave['disapproval_reason1'] !== '') {
  $disapproval_reason1_display = $leave['disapproval_reason1'];
} elseif (isset($details['recommendation_fallback']['disapproval_reason1'])) {
  $disapproval_reason1_display = $details['recommendation_fallback']['disapproval_reason1'];
} elseif (!empty($details['hr']['section7']['disapproval_reason1'])) {
  $disapproval_reason1_display = $details['hr']['section7']['disapproval_reason1'];
}

if (isset($leave['disapproval_reason2']) && $leave['disapproval_reason2'] !== '') {
  $disapproval_reason2_display = $leave['disapproval_reason2'];
} elseif (isset($details['recommendation_fallback']['disapproval_reason2'])) {
  $disapproval_reason2_display = $details['recommendation_fallback']['disapproval_reason2'];
} elseif (!empty($details['hr']['section7']['disapproval_reason2'])) {
  $disapproval_reason2_display = $details['hr']['section7']['disapproval_reason2'];
}

if (isset($leave['disapproval_reason3']) && $leave['disapproval_reason3'] !== '') {
  $disapproval_reason3_display = $leave['disapproval_reason3'];
} elseif (isset($details['recommendation_fallback']['disapproval_reason3'])) {
  $disapproval_reason3_display = $details['recommendation_fallback']['disapproval_reason3'];
} elseif (!empty($details['hr']['section7']['disapproval_reason3'])) {
  $disapproval_reason3_display = $details['hr']['section7']['disapproval_reason3'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CS Form No. 6 - Application for Leave</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");
    body { font-family: "Inter", sans-serif; background-color: #f3f4f6; }
    .form-container { max-width: 800px; margin: 2rem auto; background-color: white; padding: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .form-box { border: 1px solid #000; }
    .form-input { width: 100%; border: none; outline: none; padding: 0 4px; line-height: 1.25rem; }
    .form-line { border-bottom: 1px solid #000; }
    .form-underline { border-bottom: 1px solid #000; padding-bottom: 2px; }
    /* Signature / authorized-official name field: centered, wider, and spaced to leave room for
       an optional signature image above. Use this on the certifier / authorizing officer inputs
       so long names are visible and the underline aligns with the displayed name. */
    .signature-name {
      border-bottom: 1px solid #000;
      padding-top: 24px; /* same vertical spacing used by the signature img positioning */
      padding-bottom: 2px;
      display: block;
      margin: 0 auto;
      width: 60%;
      text-align: center;
      font-weight: 600;
      font-size: 0.95rem;
      background: transparent;
      color: #000;
    }
    .text-xxs { font-size: 0.65rem; }
    .checkbox-label { display: flex; align-items: flex-start; line-height: 1.1; }
    .checkbox-label input[type="checkbox"], .checkbox-label input[type="radio"] { margin-top: 2px; margin-right: 0.25rem; min-width: 1rem; min-height: 1rem; accent-color: #000; }
    /* Draw custom black checkbox/radio visuals to override browser defaults so marks are solid black
       and consistent across screen and print (works for disabled inputs too). */
    input[type="checkbox"], input[type="radio"] {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      width: 1rem;
      height: 1rem;
      border: 1.5px solid #000;
      display: inline-block;
      position: relative;
      vertical-align: middle;
      background: transparent;
      margin-top: 0.1rem;
    }
    /* Checkbox specifics */
    input[type="checkbox"] { border-radius: 3px; }
    input[type="checkbox"]:checked::after {
      content: "";
      position: absolute;
      left: 4px;
      top: 0px;
      width: 6px;
      height: 10px;
      border: solid #000;
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
    /* Radio specifics */
    input[type="radio"] { border-radius: 50%; }
    input[type="radio"]:checked::after {
      content: "";
      position: absolute;
      left: 3px;
      top: 3px;
      width: 6px;
      height: 6px;
      background: #000;
      border-radius: 50%;
    }
    /* Force all form text and disabled values to print/screen in solid black */
    input, textarea, select, .form-input, .form-underline, .signature-name { color: #000 !important; -webkit-text-fill-color: #000 !important; }
    /* Ensure readonly/disabled inputs remain fully opaque and black */
    input[readonly], input[disabled], textarea[readonly], textarea[disabled], select[readonly], select[disabled] { color: #000 !important; opacity: 1 !important; }
    /* Force the checkbox/radio mark color across browsers when possible */
    input[type="checkbox"], input[type="radio"] { accent-color: #000 !important; filter: none !important; }
    /* Print adjustments to ensure black colors preserved */
    @media print {
      input, textarea, select, .form-input, .form-underline, .signature-name { color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      input[readonly], input[disabled], textarea[readonly], textarea[disabled], select[readonly], select[disabled] { color: #000 !important; opacity: 1 !important; }
    }
    @media print {
      @page { 
        size: A4 portrait; 
        margin: 6mm 8mm; 
      }
      html, body { 
        width: 210mm; 
        height: 297mm;
        background: #fff !important; 
        -webkit-print-color-adjust: exact; 
        print-color-adjust: exact;
        margin: 0 !important;
        padding: 0 !important;
        overflow: hidden;
      }
      .form-container { 
        width: 194mm !important; 
        max-width: 194mm !important; 
        margin: 0 auto !important; 
        box-shadow: none !important; 
        background: #fff !important; 
        padding: 2mm 3mm !important;
        min-height: auto !important;
        page-break-inside: avoid;
        transform: scale(0.95);
        transform-origin: top center;
      }
      .no-print { display: none !important; }
      
      /* Aggressive spacing reduction for print */
      header { 
        margin-bottom: 0.25rem !important; 
        font-size: 0.7rem !important;
      }
      header img { 
        height: 32px !important; 
        width: auto !important; 
      }
      header h1 { 
        font-size: 1rem !important; 
        margin-top: 0.25rem !important; 
        margin-bottom: 0.25rem !important;
      }
      
      .form-box { 
        margin-top: 0.25rem !important; 
      }
      .text-xs { font-size: 0.65rem !important; }
      .text-sm { font-size: 0.7rem !important; }
      .text-xxs { font-size: 0.55rem !important; }
      .text-lg { font-size: 0.9rem !important; }
      
      .mt-1 { margin-top: 0.15rem !important; }
      .mt-2 { margin-top: 0.25rem !important; }
      .mt-3 { margin-top: 0.3rem !important; }
      .mt-4 { margin-top: 0.4rem !important; }
      .mt-6 { margin-top: 0.6rem !important; }
      .mt-8 { margin-top: 0.8rem !important; }
      .mt-12 { margin-top: 1rem !important; }
      
      .mb-1 { margin-bottom: 0.15rem !important; }
      .mb-2 { margin-bottom: 0.25rem !important; }
      .mb-3 { margin-bottom: 0.3rem !important; }
      .mb-4 { margin-bottom: 0.4rem !important; }
      
      .p-1 { padding: 0.15rem !important; }
      .p-2 { padding: 0.25rem !important; }
      .pt-1 { padding-top: 0.15rem !important; }
      .pt-2 { padding-top: 0.25rem !important; }
      .pb-1 { padding-bottom: 0.15rem !important; }
      
      .space-y-1 > * + * { margin-top: 0.15rem !important; }
      .space-y-2 > * + * { margin-top: 0.25rem !important; }
      
      /* Reduce checkbox/radio sizes */
      input[type="checkbox"], input[type="radio"] {
        width: 0.85rem !important;
        height: 0.85rem !important;
        min-width: 0.85rem !important;
        min-height: 0.85rem !important;
      }
      
      /* Reduce signature area padding */
      .signature-name {
        padding-top: 0 !important;
        margin-top: 0 !important;
        font-size: 0.75rem !important;
      }
      
      /* Signature container spacing */
      div[style*="padding-top:30px"] {
        padding-top: 28px !important;
        min-height: 40px !important;
      }
      
      /* Control signature image size for print */
      img[alt*="sig"], img[alt*="Signature"], img[src*="signature"] {
        max-height: 30px !important;
        max-width: 100px !important;
        height: auto !important;
        width: auto !important;
        object-fit: contain !important;
      }
      
      /* Fix signature container heights */
      div[style*="position:relative"] {
        min-height: 40px !important;
        max-height: 40px !important;
        overflow: visible !important;
        margin-bottom: 0.2rem !important;
      }
      
      /* Fix table in section 7.A */
      .border.border-black {
        border-collapse: collapse !important;
      }
      
      .border.border-black .flex {
        display: flex !important;
        border: 1px solid #000 !important;
      }
      
      .border-l.border-black,
      .border-r.border-black,
      .border-t.border-black {
        border-color: #000 !important;
      }
      
      /* Ensure content doesn't overflow */
      * {
        box-sizing: border-box !important;
      }
      
      /* Prevent page breaks */
      .form-box, .flex {
        page-break-inside: avoid !important;
      }
      
      /* Reduce table spacing */
      .border.border-black.mt-2 {
        margin-top: 0.25rem !important;
      }
      
      /* Compact input heights */
      .form-input, input, textarea {
        line-height: 1.1 !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
      }
      
      /* Fix spacing after signatures */
      .mt-0\.5 {
        margin-top: 0.1rem !important;
      }
    }
  </style>
</head>
<body>
<div class="form-container">
      <!-- Header Section (FIXED to match reference image) -->
      <header class="text-xs mb-2">
        <div class="flex justify-between items-start mb-1">
          <div class="text-left leading-tight">
            <p>Civil Service Form No. 6</p>
            <p>Revised 2020</p>
          </div>
          <div class="text-right leading-tight font-bold">
            <p>ANNEX A</p>
          </div>
        </div>

        <div class="flex justify-between items-end mb-4">
          <!-- AGENCY LOGO -->
          <div class="flex items-start">
            <img
              src="../assets/logo.png"
              alt="Agency Logo"
              class="h-10 w-20 object-contain"
              onerror="this.onerror=null; this.src='https://placehold.co/40x40/DDDDDD/000?text=AGENCY%20LOGO';"
            />
          </div>

          <!-- REPUBLIC OF THE PHILIPPINES / AGENCY INFO -->
          <div class="text-center leading-tight">
            <p class="font-bold">Republic of the Philippines</p>
            <p class="font-bold text-gray-900">MUNICIPAL OFFICE OF MABINI</p>
            <p class="text-xs font-normal">Poblacion, Mabini Batangas</p>
          </div>

          <!-- Stamp of Date of Receipt (UPDATED: Box is now blank with text above/below) -->
          <div class="text-right text-xxs pt-1 leading-snug w-20">
            <div class="border border-gray-400 border-dashed w-full h-8"></div>
          </div>
        </div>

        <h1 class="text-center text-lg font-extrabold text-gray-900 mt-2">
          APPLICATION FOR LEAVE
        </h1>
      </header>

      <!-- 1-5. Employee Information Section (PERFECT ALIGNMENT FIXED HERE) -->
      <div class="text-xs form-box">
        <!-- Row 1: 1. OFFICE/DEPARTMENT and 2. NAME (Last, First, Middle) -->
        <div class="flex form-line">
          <!-- 1. OFFICE/DEPARTMENT -->
          <div class="w-2/5 flex flex-col p-0 h-full">
            <div class="flex items-center px-1 pt-1 h-6">
              <span class="font-bold">1. OFFICE/DEPARTMENT</span>
            </div>
            <div class="flex-grow flex items-end px-1 pb-1 pt-0">
              <input type="text" id="office_dept" class="form-input h-4 p-0" value="<?= htmlspecialchars($user['department'] ?? '') ?>" readonly />
            </div>
          </div>
          <!-- 2. NAME -->
          <div class="w-3/5 flex border-l border-white">
            <div class="w-1/5 flex items-start pt-1 pl-1 pr-0 font-bold">2. NAME:</div>
            <div class="flex-grow flex justify-between">
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(Last)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_last" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" readonly />
                </div>
              </div>
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(First)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_first" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" readonly />
                </div>
              </div>
              <div class="w-1/3 flex flex-col border-l border-white">
                <div class="h-6 flex items-center justify-center pt-1">
                  <span class="text-center text-xxs">(Middle)</span>
                </div>
                <div class="flex-grow flex items-end px-1 pb-1 pt-0">
                  <input type="text" id="name_middle" class="form-input text-center h-4 p-0" value="<?= htmlspecialchars($user['mi'] ?? '') ?>" readonly />
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Row 2: 3. DATE OF FILING, 4. POSITION, 5. SALARY -->
        <div class="flex p-1">
          <div class="w-1/3 flex items-center">
            <span class="font-bold">3. DATE OF FILING</span>
            <input type="date" id="date_filing" class="form-underline inline-block w-auto h-4 p-0 ml-1 flex-grow text-xs" value="<?= htmlspecialchars(substr($leave['applied_at'] ?? '', 0, 10)) ?>" readonly />
          </div>
          <div class="w-1/3 flex items-center pl-2 border-l border-white">
            <span class="font-bold">4. POSITION</span>
            <input type="text" id="position" class="form-underline text-center w-auto ml-1 p-0 flex-grow text-xs" value="<?= htmlspecialchars($user['position'] ?? '') ?>" readonly />
          </div>
          <div class="w-1/3 flex items-center pl-2 border-l border-white">
            <span class="font-bold">5. SALARY</span>
            <input type="text" id="salary" class="form-underline text-center inline-block w-auto ml-1 p-0 flex-grow text-xs" value="<?= htmlspecialchars($displaySalary) ?>" readonly />
          </div>
        </div>
      </div>

      <!-- 6. DETAILS OF APPLICATION -->
      <div class="text-xs form-box mt-3">
        <div class="text-center font-bold form-line p-1">
          <span class="text-lg font-bold">6. DETAILS OF APPLICATION</span>
        </div>

        <div class="flex">
          <!-- 6.A TYPE OF LEAVE -->
          <div class="w-1/2 p-2 form-line border-r border-black">
            <div class="font-bold mb-1">6.A TYPE OF LEAVE TO BE AVAILED OF</div>
            <div class="space-y-1">
              <label class="checkbox-label" for="vl">
                <input type="checkbox" id="vl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Vacation Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="mfl">
                <input type="checkbox" id="mfl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Mandatory/Forced Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Mandatory/Forced Leave (Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="sl">
                <input type="checkbox" id="sl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Sick Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Sick Leave (Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="ml">
                <input type="checkbox" id="ml" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Maternity Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Maternity Leave (R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)</span>
              </label>
              <label class="checkbox-label" for="pl">
                <input type="checkbox" id="pl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Paternity Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Paternity Leave (R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)</span>
              </label>
              <label class="checkbox-label" for="spl">
                <input type="checkbox" id="spl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Privilege Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Privilege Leave (Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="solopl">
                <input type="checkbox" id="solopl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Solo Parent Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Solo Parent Leave (RA No. 8972 / CSC MC No. 8, s. 2004)</span>
              </label>
              <label class="checkbox-label" for="studyl">
                <input type="checkbox" id="studyl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Study Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Study Leave (Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="vawc">
                <input type="checkbox" id="vawc" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', '10-Day VAWC Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">10-Day VAWC Leave (RA No. 9262 / CSC MC No. 15, s. 2005)</span>
              </label>
              <label class="checkbox-label" for="rehab">
                <input type="checkbox" id="rehab" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Rehabilitation Privilege') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Rehabilitation Privilege (Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span>
              </label>
              <label class="checkbox-label" for="splwomen">
                <input type="checkbox" id="splwomen" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Leave Benefits for Women') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Leave Benefits for Women (RA No. 9710 / CSC MC No. 25, s. 2010)</span>
              </label>
              <label class="checkbox-label" for="secl">
                <input type="checkbox" id="secl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Special Emergency (Calamity) Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Special Emergency (Calamity) Leave (CSC MC No. 2, s. 2012, as amended)</span>
              </label>
              <label class="checkbox-label" for="adoptl">
                <input type="checkbox" id="adoptl" name="leave_type" <?= (strpos($leave['leave_type'] ?? '', 'Adoption Leave') !== false ? 'checked' : '') ?> disabled />
                <span class="ml-1 text-xxs">Adoption Leave (R.A. No. 8552)</span>
              </label>
              <div class="mt-2 text-xxs flex items-center">
                Others:
                <input type="text" id="other_leave_specify" class="form-underline w-2/3 ml-1 p-0 text-xs" value="<?= htmlspecialchars($details['snapshot']['other_leave_specify']['value'] ?? '') ?>" readonly />
              </div>
            </div>
          </div>

          <!-- 6.B DETAILS OF LEAVE -->
          <div class="w-1/2 p-2 form-line">
            <div class="font-bold mb-1">6.B DETAILS OF LEAVE</div>

            <!-- Vacation/Special Privilege Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">
                In case of Vacation/Special Privilege Leave:
              </p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="loc_ph">
                  <input type="checkbox" id="loc_ph" name="vl_loc" <?= (!empty($details['section6b']['vl']['withinPH']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex items-center"
                    >Within the Philippines
                    <input
                      type="text"
                      id="vl_loc_within_specify"
                      class="form-underline w-3/5 ml-1 p-0 text-xs"
                      value="<?= htmlspecialchars($details['section6b']['vl']['withinSpecify'] ?? '') ?>"
                    /></span>
                </label>
                <label class="checkbox-label" for="loc_ab">
                  <input type="checkbox" id="loc_ab" name="vl_loc" <?= (!empty($details['section6b']['vl']['abroad']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex items-center"
                    >Abroad (Specify)
                    <input
                      type="text"
                      id="vl_loc_abroad_specify"
                      class="form-underline w-3/5 ml-1 p-0 text-xs"
                      value="<?= htmlspecialchars($details['section6b']['vl']['abroadSpecify'] ?? '') ?>"
                    /></span>
                </label>
              </div>
            </div>

            <!-- Sick Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">In case of Sick Leave:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="sl_hosp">
                  <input type="checkbox" id="sl_hosp" name="sl_type" <?= (!empty($details['section6b']['sl']['inHospital']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex flex-col"
                    >In Hospital (Specify Illness)
                    <input
                      type="text"
                      id="sl_hosp_illness"
                      class="form-underline w-full p-0 text-xs mt-1"
                      value="<?= htmlspecialchars($details['section6b']['sl']['hospitalIllness'] ?? '') ?>"
                    /></span>
                </label>
                <label class="checkbox-label" for="sl_out">
                  <input type="checkbox" id="sl_out" name="sl_type" <?= (!empty($details['section6b']['sl']['outPatient']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs w-full flex flex-col"
                    >Out Patient (Specify Illness)
                    <input
                      type="text"
                      id="sl_out_illness"
                      class="form-underline w-full p-0 text-xs mt-1"
                      value="<?= htmlspecialchars($details['section6b']['sl']['outIllness'] ?? '') ?>"
                    /></span>
                </label>
              </div>
            </div>

            <!-- Special Leave Benefits for Women -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">
                In case of Special Leave Benefits for Women:
              </p>
              <div class="ml-3">
                <span class="text-xxs w-full flex flex-col"
                  >(Specify Illness)
                  <input
                    type="text"
                    id="splwomen_illness_input"
                    class="form-underline w-full ml-1 p-0 text-xs mt-1"
                    value="<?= htmlspecialchars($details['section6b']['splwomen']['illness'] ?? '') ?>"
                /></span>
              </div>
            </div>

            <!-- Study Leave -->
            <div class="mb-3">
              <p class="text-xxs font-semibold mb-1">In case of Study Leave:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="sl_md">
                  <input type="checkbox" id="sl_md" name="study_type" <?= (!empty($details['section6b']['study']['masters']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Completion of Master's Degree</span
                  >
                </label>
                <label class="checkbox-label" for="sl_bar">
                  <input type="checkbox" id="sl_bar" name="study_type" <?= (!empty($details['section6b']['study']['bar']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >BAR/Board Examination Review</span
                  >
                </label>
                <div class="text-xxs">
                  Other purpose:
                  <input
                    type="text"
                    id="study_other_input"
                    class="form-underline w-3/4 ml-1 p-0 text-xs"
                    value="<?= htmlspecialchars($details['section6b']['study']['other'] ?? '') ?>"
                  />
                </div>
              </div>
            </div>

            <!-- Others (Monetization/Terminal Leave) -->
            <div>
              <p class="text-xxs font-semibold mb-1">Others:</p>
              <div class="ml-3 space-y-1">
                <label class="checkbox-label" for="mlc">
                  <input type="checkbox" id="mlc" name="other_type" <?= (!empty($details['section6b']['others']['mlc']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Monetization of Leave Credits</span
                  >
                </label>
                <label class="checkbox-label" for="tl">
                  <input type="checkbox" id="tl" name="other_type" <?= (!empty($details['section6b']['others']['tl']) ? 'checked' : '') ?> disabled /><span
                    class="ml-1 text-xxs"
                    >Terminal Leave</span
                  >
                </label>
              </div>
            </div>
          </div>
        </div>

          <div class="flex">
          <!-- 6.C NUMBER OF WORKING DAYS APPLIED FOR -->
          <div
            class="w-2/4 p-2 form-line border-t border-black border-r border-black"
          >
          <!-- (HR section moved to the dedicated Section 7 block below) -->
            <div class="font-bold mb-1">6.C NUMBER OF WORKING DAYS APPLIED FOR</div>
            <!-- Line for number of days -->
            <input
              type="text"
              id="num_working_days"
              class="form-underline w-3/4 p-0 text-center text-sm mb-2"
              value="<?= htmlspecialchars($details['num_working_days'] ?? '') ?>"
              readonly
            />
            <div class="font-bold mb-1">INCLUSIVE DATES</div>
            <!-- Line for inclusive dates -->
            <input
              type="text"
              id="inclusive_dates"
              class="form-underline w-3/4 p-0 text-center text-sm"
              value="<?= htmlspecialchars($leave['dates'] ?? ($details['inclusive_dates'] ?? '')) ?>"
              readonly
            />
          </div>

          <!-- 6.D COMMUTATION -->
          <div class="w-2/4 p-2 form-line border-t border-black">
            <div class="font-bold mb-3">6.D COMMUTATION</div>
            <div class="space-y-2 text-sm">
              <label class="checkbox-label" for="comm_not_req">
                <input
                  type="radio"
                  id="comm_not_req"
                  name="commutation"
                  value="Not Requested"
                  class="mt-0.5"
                  <?= (($details['commutation'] ?? '') === 'Not Requested' ? 'checked' : '') ?> disabled
                /><span class="ml-1">Not Requested</span>
              </label>
              <label class="checkbox-label" for="comm_req">
                <input
                  type="radio"
                  id="comm_req"
                  name="commutation"
                  value="Requested"
                  class="mt-0.5"
                  <?= (($details['commutation'] ?? '') === 'Requested' ? 'checked' : '') ?> disabled
                /><span class="ml-1">Requested</span>
              </label>
            </div>
            <!-- Signature of Applicant -->
            <div class="mt-6 text-center pt-2 text-xs">
              <div id="signature_container" class="w-full" style="text-align:center;">
                <?php if (!empty($leave['signature_path'])): ?>
                  <img src="../<?= ltrim($leave['signature_path'], '/') ?>" alt="Signature" style="max-height:60px; object-fit:contain; display:block; margin:0 auto;" />
                <?php else: ?>
                  <input type="text" id="signature_text" class="form-underline w-full text-center text-sm" value="" readonly />
                <?php endif; ?>
              </div>
              <p class="mt-0.5 font-normal">(Signature of Applicant)</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 7. DETAILS OF ACTION ON APPLICATION (FIXED to match reference image) -->
      <div class="text-xs form-box mt-3">
        <div class="text-center font-bold form-line p-1">
          7. DETAILS OF ACTION ON APPLICATION
        </div>

        <div class="flex form-line">
          <!-- 7.A Certification of Leave Credits -->
          <div class="w-1/2 p-2 border-r border-black">
            <div class="font-bold mb-1">7.A CERTIFICATION OF LEAVE CREDITS</div>
            <p class="text-sm mb-2">
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;As
              of
              <input
                type="text"
                class="form-underline inline-block w-2/5 text-center p-0 h-4 ml-1"
                value="<?= htmlspecialchars($certification_date_display ?? '') ?>"
              />
            </p>

            <!-- Leave Credit Table (Fixed with explicit borders) -->
            <div class="border border-black mt-2">
              <!-- Header Row -->
              <div class="flex font-bold text-xxs">
                <div class="w-1/3 p-1"></div>
                <div class="w-1/3 p-1 text-center border-l border-black">
                  Vacation Leave
                </div>
                <div class="w-1/3 p-1 text-center border-l border-black">
                  Sick Leave
                </div>
              </div>
              <!-- Total Earned Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Total Earned
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($vl_total_earned_display ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($sl_total_display ?? '') ?>"
                />
              </div>
              <!-- Less this application Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Less this application
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($vl_less_display ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($sl_less_display ?? '') ?>"
                />
              </div>
              <!-- Balance Row -->
              <div class="flex text-xxs border-t border-black">
                <div
                  class="w-1/3 p-1 font-semibold text-right border-r border-black"
                >
                  Balance
                </div>
                <input
                  type="text"
                  class="w-1/3 p-1 text-center border-r border-black"
                  value="<?= htmlspecialchars($vl_balance_display ?? '') ?>"
                />
                <input
                  type="text"
                  class="w-1/3 p-1 text-center"
                  style="border: none; outline: none; padding: 1px"
                  value="<?= htmlspecialchars($sl_balance_display ?? '') ?>"
                />
              </div>
            </div>

            <?php
              $hr = $details['hr'] ?? null; $s = $hr['section7'] ?? []; $hsigs = $hr['signatures'] ?? [];
              if ($deptHeadName === 'DEPARTMENT HEAD' && !empty($s['authorized_officer_7b'])) {
                $deptHeadName = mb_strtoupper(trim($s['authorized_officer_7b']), 'UTF-8');
              }
            ?>
            <div class="mt-8 text-center text-xxs font-semibold pt-2">
              <?php $certSig = $hsigs['certifier'] ?? ($hsigs['7a'] ?? null); ?>
                <div style="position:relative; min-height:45px; padding-top:30px;">
                  <?php if (!empty($certSig)): ?>
                    <img src="../<?= ltrim($certSig, '/') ?>" alt="Certifier sig" style="position:absolute; left:50%; transform:translateX(-50%); top:0; max-height:28px; max-width:100px; object-fit:contain; pointer-events:none; z-index:1;" />
                  <?php endif; ?>
                  <input type="text" class="signature-name" style="padding-top:0; margin-top:0;" value="<?= htmlspecialchars($adminAideName) ?>" readonly />
                </div>
              <p class="mt-0.5 font-normal">Administrative Aide II</p>
            </div>
          </div>

          <!-- 7.B RECOMMENDATION -->
          <div class="w-1/2 p-2">
            <div class="font-bold mb-3">7.B RECOMMENDATION</div>
            <div class="space-y-2 text-sm">
              <label class="checkbox-label" for="rec_appr">
                <input
                  type="checkbox"
                  id="rec_appr"
                  name="recommendation"
                  <?= ($recommendation_display === 'For approval' ? 'checked' : '') ?> disabled
                /><span class="ml-1">For approval</span>
              </label>
              <label class="checkbox-label" for="rec_disappr">
                <input
                  type="checkbox"
                  id="rec_disappr"
                  name="recommendation"
                  class="mt-0.5"
                  <?= ($recommendation_display === 'For disapproval' ? 'checked' : '') ?> disabled
                /><span class="ml-1">For disapproval due to</span>
              </label>
            </div>
            <!-- Multiple lines for disapproval reason -->
            <div class="ml-5 mt-1 space-y-1">
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($disapproval_reason1_display ?? '') ?>"
              />
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($disapproval_reason2_display ?? '') ?>"
              />
              <input
                type="text"
                class="form-underline w-full p-0 text-xs"
                placeholder=""
                value="<?= htmlspecialchars($disapproval_reason3_display ?? '') ?>"
              />
            </div>

            <div class="mt-12 text-center text-xs font-semibold pt-2">
              <div style="position:relative; min-height:45px; padding-top:30px;">
                  <?php
                    // Show Dept Head signature ONLY if it was saved for this specific request (7B)
                    $deptSigToShow = $hsigs['7b'] ?? '';
                    // Show Dept Head name ONLY if it was explicitly saved for this request under authorized_officer_7b
                    $deptHeadNameFor7B = '';
                    if (!empty($s['authorized_officer_7b'])) {
                      $deptHeadNameFor7B = mb_strtoupper(trim($s['authorized_officer_7b']), 'UTF-8');
                    }
                  ?>
                  <?php if (!empty($deptSigToShow)): ?>
                    <img src="../<?= ltrim($deptSigToShow, '/') ?>" alt="7B sig" style="position:absolute; left:50%; transform:translateX(-50%); top:0; max-height:28px; max-width:100px; object-fit:contain; pointer-events:none; z-index:1;" />
                  <?php endif; ?>
                  <input type="text" class="signature-name" style="padding-top:0; margin-top:0;" value="<?= htmlspecialchars($deptHeadNameFor7B) ?>" readonly />
                </div>
              <p class="mt-0.5 font-normal"></p>
            </div>
          </div>
        </div>

        <div class="flex form-line border-t border-white">
          <!-- 7.C APPROVED FOR -->
          <div class="w-1/2 p-2 border-r border-white">
            <div class="font-bold mb-3">7.C APPROVED FOR:</div>
            <div class="space-y-2 text-sm">
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_days_with_pay'] ?? '') ?>"
                />
                days with pay
              </p>
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_days_without_pay'] ?? '') ?>"
                />
                days without pay
              </p>
              <p class="mb-1 flex items-center">
                <input
                  type="text"
                  class="form-underline w-8 text-center mr-1"
                  placeholder="___"
                  value="<?= htmlspecialchars($leave['approved_others'] ?? '') ?>"
                />
                others (Specify)
              </p>
            </div>
          </div>

          <!-- 7.D DISAPPROVED DUE TO -->
          <div class="w-1/2 p-2">
            <div class="font-bold mb-3">7.D DISAPPROVED DUE TO:</div>
            <input
              type="text"
              class="form-underline w-full p-0 text-sm mb-2"
              placeholder=""
              value="<?= htmlspecialchars($leave['disapproved_reason'] ?? '') ?>"
            />
            <input
              type="text"
              class="form-underline w-full p-0 text-sm mb-2"
              placeholder=""
            />
            <input
              type="text"
              class="form-underline w-full p-0 text-sm"
              placeholder=""
            />
          </div>
        </div>
        <div class="mt-4 text-center text-xs font-semibold p-2">
          <?php 
          // Check if municipal admin has signed (approved_by_municipal = 1)
          $municipalSig = null;
          if (!empty($leave['approved_by_municipal']) && $leave['approved_by_municipal'] == 1) {
            // Try to get from details JSON first
            if (!empty($details['municipal']['signature'])) {
              $municipalSig = $details['municipal']['signature'];
            }
          }
          // Fallback to HR final signature if no municipal signature
          if (!$municipalSig) {
            $municipalSig = $hsigs['final'] ?? ($hsigs['authorized'] ?? null);
          }
          ?>
          <div style="position:relative; min-height:45px; padding-top:30px;">
            <?php if (!empty($municipalSig)): ?>
              <img src="../<?= ltrim($municipalSig, '/') ?>" alt="municipal admin sig" style="position:absolute; left:50%; transform:translateX(-50%); top:0; max-height:28px; max-width:100px; object-fit:contain; pointer-events:none; z-index:1;" />
            <?php endif; ?>
            <input type="text" class="signature-name" style="padding-top:0; margin-top:0;" value="<?= htmlspecialchars($s['final_official'] ?? $leave['authorized_official'] ?? 'ATTY. MARIA CONCEPCION R. HERNANDEZ-BELOSO') ?>" readonly />
          </div>
          <p class="mt-0.5 font-normal">Municipal Administrator</p>
        </div>
      </div>

  <!-- Continue with the rest of the form: 6. DETAILS OF APPLICATION, all checkboxes, radios, grouped fields, etc. -->
  <!-- For each checkbox/radio, use: <input type="checkbox" <?= ($request['leave_type'] == 'Vacation Leave' ? 'checked' : '') ?> disabled /> -->
  <!-- For each text field, use: <input type="text" value="<?= htmlspecialchars($request['fieldname'] ?? '') ?>" readonly /> -->
  <!-- Repeat the structure and layout from employee/civil form.html for all sections. -->

  <!-- Print Button -->
  <div class="mt-4 text-center">
    <button onclick="window.print()" class="no-print bg-blue-500 text-white px-6 py-2 rounded-lg shadow-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">Print Form</button>
    <?php if (isset($_GET['live']) && $_GET['live'] === '1'): ?>
      <button onclick="doneAction()" class="no-print bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md ml-3 hover:bg-gray-700">Done</button>
    <?php endif; ?>
  </div>
</div>
</body>
<script>
  // Live-refresh when opened with ?live=1 — polls leave requests and reloads if details changed
  const initialDetails = <?= json_encode($details) ?>;
  const leaveId = <?= json_encode($leave_id) ?>;
  (function(){
    try{
      const params = new URLSearchParams(window.location.search);
      if (params.get('live') === '1'){
        setInterval(async ()=>{
          try{
            const res = await fetch('../api/get_leave_requests.php');
            const js = await res.json();
            if(js && js.success && Array.isArray(js.data)){
              const row = js.data.find(r => String(r.id) === String(leaveId));
              if(row){
                let newDetails = row.details;
                if (typeof newDetails === 'string'){
                  try{ newDetails = JSON.parse(newDetails); }catch(e){}
                }
                if (JSON.stringify(newDetails) !== JSON.stringify(initialDetails)){
                  location.reload();
                }
              }
            }
          }catch(e){}
        }, 2500);
      }
    }catch(e){}
  })();

  function doneAction(){
    try{
      // If this window was opened from the leave-request page, close it and focus the opener
      if (window.opener && !window.opener.closed){
        try{ window.opener.focus(); }catch(e){}
        window.close();
        return;
      }
    }catch(e){}
    // Otherwise navigate back to the dept head leave-request listing (relative path for hosting compatibility)
    window.location.href = 'leave-request.php';
  }
</script>
</html>
