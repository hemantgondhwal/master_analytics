<?php
define('NOTIFY_TO',   'masteranalytics.india@gmail.com');
$NOTIFY_EMAILS = array(
    'masteranalytics.india@gmail.com',
    'Analyticsproschool@gmail.com',
    'support@thexlacademy.com',
);
define('SITE_NAME',   'Master Analytic');
define('PHONE',       '+91 74287 03467');
define('APPS_SCRIPT', 'https://script.google.com/macros/s/AKfycbxVL7AnbhP-IqDZytTudemR_c4Omp-uGs7U5KfNzeN5EraZlAZ4sqrYLU75annkc30wEg/exec');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

function clean($v) {
    $v = isset($v) ? $v : '';
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

$name   = clean(isset($_POST['name'])   ? $_POST['name']   : '');
$phone  = clean(isset($_POST['phone'])  ? $_POST['phone']  : '');
$email  = clean(isset($_POST['email'])  ? $_POST['email']  : '');
$city   = clean(isset($_POST['city'])   ? $_POST['city']   : '');
$course = clean(isset($_POST['course']) ? $_POST['course'] : '');
$source = clean(isset($_POST['source']) ? $_POST['source'] : '');
$action = clean(isset($_POST['action_type']) ? $_POST['action_type'] : 'Demo Enquiry');

if (!$name || !$phone || !$email) {
    header('HTTP/1.1 400 Bad Request');
    exit('Missing required fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid email address');
}

// ── 1. Save to Google Sheet via curl ─────────────────────────────────────────
$sheetSource = ($action !== 'Demo Enquiry' ? '[' . $action . '] ' : '') . ($course ? 'Course: ' . $course . ' | ' : '') . ($source ? $source : 'Website');
$gsParams = http_build_query(array(
    'name'   => $name,
    'phone'  => $phone,
    'email'  => $email,
    'city'   => $city,
    'source' => $sheetSource,
));

if (function_exists('curl_init')) {
    $ch = curl_init(APPS_SCRIPT . '?' . $gsParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_exec($ch);
    curl_close($ch);
}

// ── 2. Notification email ─────────────────────────────────────────────────────
$ts      = date('d M Y, h:i A');
$subject = 'New Lead (' . $action . '): ' . $name . ' - ' . SITE_NAME;
$cityVal = $city ? $city : '-';

$html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto">'
      . '<div style="background:#06142E;padding:24px 28px;border-radius:10px 10px 0 0">'
      . '<h2 style="color:#fff;margin:0;font-size:18px">New Enquiry (' . $action . ') - ' . SITE_NAME . '</h2>'
      . '<p style="color:rgba(255,255,255,.5);margin:6px 0 0;font-size:12px">' . $ts . ' IST</p></div>'
      . '<div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none">'
      . pf('Enquiry Type', $action)
      . pf('Name',   $name)
      . pf('Phone',  '+91 ' . $phone)
      . pf('Email',  $email)
      . pf('Course', $course ? $course : 'Not Specified')
      . pf('City / Center', $cityVal)
      . pf('Source URL', $source ? $source : '-')
      . '</div>'
      . '<div style="background:#f3f4f6;padding:12px 28px;border-radius:0 0 10px 10px;font-size:11px;color:#9ca3af;border:1px solid #e5e7eb;border-top:none">' . SITE_NAME . ' - Automated lead alert</div>'
      . '</div>';

$headers = 'MIME-Version: 1.0' . "\r\n"
         . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
         . 'From: ' . SITE_NAME . ' <noreply@masteranalytics.in>' . "\r\n"
         . 'Reply-To: ' . $email . "\r\n"
         . 'X-Mailer: PHP/' . phpversion();

foreach ($NOTIFY_EMAILS as $recipient) {
    @mail($recipient, $subject, $html, $headers);
}

// ── 3. Auto-reply to student ──────────────────────────────────────────────────
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $autoSubject = 'Thank you for contacting ' . SITE_NAME;
    $autoHtml = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto">'
              . '<div style="background:#06142E;padding:24px 28px;border-radius:10px 10px 0 0;text-align:center">'
              . '<h2 style="color:#fff;margin:0;font-size:20px">Thank You, ' . $name . '!</h2>'
              . '<p style="color:rgba(255,255,255,.5);margin:8px 0 0;font-size:13px">Your enquiry for ' . ($course ? htmlspecialchars($course) : 'Data Analytics Training') . ' has been received.</p></div>'
              . '<div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none;font-size:15px;color:#374151;line-height:1.7">'
              . '<p>Hi <strong>' . $name . '</strong>, our Senior Career Counsellor will call you on <strong>+91 ' . $phone . '</strong> within 2 hours.</p>'
              . '<div style="background:#eff6ff;border-left:4px solid #1447E6;padding:12px 16px;border-radius:6px;margin:16px 0">'
              . ($course ? '<b>Course:</b> ' . htmlspecialchars($course) . '<br>' : '')
              . '<b>Preferred Center:</b> ' . $cityVal . '</div>'
              . '<a href="tel:+917428703467" style="display:inline-block;background:#1447E6;color:#fff;text-decoration:none;padding:11px 26px;border-radius:50px;font-weight:700;font-size:14px">Call Us Directly: ' . PHONE . '</a>'
              . '</div>'
              . '<div style="background:#f3f4f6;padding:12px 28px;border-radius:0 0 10px 10px;font-size:11px;color:#9ca3af;border:1px solid #e5e7eb;border-top:none">' . SITE_NAME . ' - Delhi &amp; Mumbai</div>'
              . '</div>';

    $autoHeaders = 'MIME-Version: 1.0' . "\r\n"
                 . 'Content-Type: text/html; charset=UTF-8' . "\r\n"
                 . 'From: ' . SITE_NAME . ' <noreply@masteranalytics.in>' . "\r\n"
                 . 'Reply-To: ' . NOTIFY_TO . "\r\n"
                 . 'X-Mailer: PHP/' . phpversion();

    @mail($email, $autoSubject, $autoHtml, $autoHeaders);
}

header('Location: thankyou.html');
exit;

function pf($label, $value) {
    return '<div style="margin-bottom:14px">'
         . '<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#1447E6;margin-bottom:3px">' . $label . '</div>'
         . '<div style="font-size:14px;color:#111827;font-weight:600;padding:9px 12px;background:#f9fafb;border-left:3px solid #1447E6;border-radius:4px">' . $value . '</div>'
         . '</div>';
}
