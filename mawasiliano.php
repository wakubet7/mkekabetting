<?php
/*
 * Fomu ya mawasiliano ya Mkeka Betting.
 * Inapokea ujumbe kutoka mawasiliano.html na kuutuma kwa barua pepe ya admin.
 * Ili kubadilisha anayepokea, badilisha $TO hapa chini.
 */
declare(strict_types=1);
date_default_timezone_set('Africa/Dar_es_Salaam');

$TO      = 'admin@mkekabetting.com';
$FROM    = 'admin@mkekabetting.com';   // lazima iwe barua pepe ya domain hii (Hostinger)
$SITE    = 'Mkeka Betting';
$BACK    = 'mawasiliano.html';

$wantsJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

function finish(bool $ok, string $error = ''): void {
    global $wantsJson, $BACK;
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        if (!$ok) { http_response_code(400); }
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . $BACK . '?imetumwa=' . ($ok ? '1' : '0'), true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $BACK, true, 302);
    exit;
}

// Ulinzi dhidi ya spam: sehemu iliyofichwa lazima iwe tupu, na fomu isitumwe haraka mno
if (trim((string)($_POST['tovuti'] ?? '')) !== '') { finish(true); }
$t = (int)($_POST['t'] ?? 0);
if ($t > 0 && (time() - $t) < 3) { finish(true); }

$clean = static function (string $key, int $max): string {
    $v = trim((string)($_POST[$key] ?? ''));
    $v = str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v);   // zuia header injection
    return mb_substr($v, 0, $max);
};

$jina   = $clean('jina', 80);
$email  = $clean('email', 120);
$simu   = preg_replace('/[^0-9+ ()-]/', '', $clean('simu', 20));
$mada   = $clean('mada', 60);
$ujumbe = mb_substr(trim((string)($_POST['ujumbe'] ?? '')), 0, 3000);
$umri   = isset($_POST['umri']);

$madaHalali = ['Swali la jumla', 'Matangazo na ushirikiano', 'Mikeka na code', 'Tatizo kwenye tovuti', 'Nyingine'];
if (!in_array($mada, $madaHalali, true)) { $mada = 'Nyingine'; }

if ($jina === '')                                   { finish(false, 'Tafadhali andika jina lako.'); }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { finish(false, 'Tafadhali andika barua pepe sahihi.'); }
if (mb_strlen($ujumbe) < 10)                        { finish(false, 'Ujumbe ni mfupi sana. Andika angalau herufi 10.'); }
if (!$umri)                                         { finish(false, 'Tafadhali thibitisha kuwa una miaka 18 au zaidi.'); }

$subject = '[' . $SITE . '] ' . $mada . ' - ' . $jina;
$body  = "Ujumbe mpya kutoka fomu ya mawasiliano ya mkekabetting.com\n";
$body .= str_repeat('-', 50) . "\n";
$body .= "Jina:        $jina\n";
$body .= "Barua pepe:  $email\n";
$body .= "Simu:        " . ($simu !== '' ? $simu : '-') . "\n";
$body .= "Mada:        $mada\n";
$body .= "Tarehe:      " . date('Y-m-d H:i') . "\n";
$body .= "IP:          " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";
$body .= str_repeat('-', 50) . "\n\n";
$body .= $ujumbe . "\n";

$headers  = 'From: ' . mb_encode_mimeheader($SITE . ' - Fomu', 'UTF-8') . " <$FROM>\r\n";
$headers .= 'Reply-To: ' . mb_encode_mimeheader($jina, 'UTF-8') . " <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = mail($TO, mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers, '-f' . $FROM);

if (!$sent) {
    finish(false, 'Samahani, ujumbe haukutumwa. Jaribu tena au tuandikie admin@mkekabetting.com.');
}
finish(true);
