<?php
/**
 * Vision Global — Send Enquiry Handler
 * Place this file in the same folder as index.html on your server.
 * Update $to_email below with your real recipient address.
 */

/* ── CONFIG ─────────────────────────────────────── */
$to_email    = 'enquiries@visionglobal.in';   // ← change to your inbox
$from_name   = 'Vision Global Website';
$from_email  = 'noreply@visionglobal.in';     // ← must be on same domain as server
$subject_pfx = '[VG Enquiry]';
/* ────────────────────────────────────────────────── */

/* Only allow POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* ── SANITIZE & VALIDATE ───────────────────────── */
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$first_name = clean($_POST['first_name'] ?? '');
$last_name  = clean($_POST['last_name']  ?? '');
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$company    = clean($_POST['company']    ?? '');
$service    = clean($_POST['service']    ?? '');
$message    = clean($_POST['message']    ?? '');

/* Required fields */
$errors = [];
if (empty($first_name))                      $errors[] = 'First name is required.';
if (empty($last_name))                       $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (empty($service))                         $errors[] = 'Please select a service.';

if (!empty($errors)) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

/* ── BUILD EMAIL ───────────────────────────────── */
$full_name = $first_name . ' ' . $last_name;
$subject   = $subject_pfx . ' ' . $service . ' — ' . $full_name;

$body  = "You have received a new enquiry from the Vision Global website.\n";
$body .= str_repeat('─', 52) . "\n\n";
$body .= "Name    : $full_name\n";
$body .= "Email   : $email\n";
$body .= "Company : " . ($company ?: '—') . "\n";
$body .= "Service : $service\n";
$body .= "\nMessage:\n$message\n\n";
$body .= str_repeat('─', 52) . "\n";
$body .= "Submitted: " . date('Y-m-d H:i:s T') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

/* HTML version */
$html  = '<!DOCTYPE html><html><head><meta charset="UTF-8"/>';
$html .= '<style>body{font-family:Arial,sans-serif;font-size:14px;color:#222;background:#f5f5f5;margin:0;padding:0}';
$html .= '.wrap{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}';
$html .= '.hdr{background:#0F111E;padding:28px 32px;text-align:center}';
$html .= '.hdr h1{color:#02A384;font-size:22px;margin:0;letter-spacing:1px}';
$html .= '.hdr p{color:rgba(255,255,255,.5);font-size:12px;margin:6px 0 0}';
$html .= '.body{padding:32px}';
$html .= '.row{display:flex;border-bottom:1px solid #eee;padding:10px 0}';
$html .= '.lbl{width:120px;font-weight:bold;color:#888;font-size:12px;text-transform:uppercase;letter-spacing:.5px;flex-shrink:0}';
$html .= '.val{color:#222}';
$html .= '.msg{background:#f9f9f9;border-left:3px solid #02A384;padding:14px 18px;margin-top:20px;border-radius:0 4px 4px 0;line-height:1.7}';
$html .= '.ftr{background:#f0f0f0;padding:16px 32px;text-align:center;font-size:11px;color:#aaa}';
$html .= '</style></head><body><div class="wrap">';
$html .= '<div class="hdr"><h1>VISION GLOBAL</h1><p>New Enquiry Received</p></div>';
$html .= '<div class="body">';
$html .= '<div class="row"><div class="lbl">Name</div><div class="val">' . $full_name . '</div></div>';
$html .= '<div class="row"><div class="lbl">Email</div><div class="val"><a href="mailto:' . $email . '" style="color:#02A384">' . $email . '</a></div></div>';
$html .= '<div class="row"><div class="lbl">Company</div><div class="val">' . ($company ?: '—') . '</div></div>';
$html .= '<div class="row"><div class="lbl">Service</div><div class="val"><strong>' . $service . '</strong></div></div>';
if (!empty($message)) {
    $html .= '<p style="margin:20px 0 6px;font-weight:bold;color:#555;font-size:12px;text-transform:uppercase;letter-spacing:.5px">Message</p>';
    $html .= '<div class="msg">' . nl2br($message) . '</div>';
}
$html .= '</div>';
$html .= '<div class="ftr">Submitted ' . date('Y-m-d H:i:s T') . ' · IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '—') . '</div>';
$html .= '</div></body></html>';

/* ── HEADERS ───────────────────────────────────── */
$boundary = md5(uniqid('', true));

$headers  = "From: $from_name <$from_email>\r\n";
$headers .= "Reply-To: $full_name <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$mime  = "--$boundary\r\n";
$mime .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$mime .= $body . "\r\n";
$mime .= "--$boundary\r\n";
$mime .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
$mime .= $html . "\r\n";
$mime .= "--$boundary--";

/* ── SEND ──────────────────────────────────────── */
$sent = mail($to_email, $subject, $mime, $headers);

header('Content-Type: application/json');

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Your enquiry has been sent. We will respond within 24 hours.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Mail could not be sent. Please try again or email us directly.'
    ]);
}
exit;
