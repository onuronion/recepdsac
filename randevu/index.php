<?php

declare(strict_types=1);

session_start();
date_default_timezone_set('Europe/Istanbul');

/* ---------- Veritabanı ---------- */
$DB_HOST = '';
$DB_NAME = '';
$DB_USER = '';
$DB_PASS = '';

/* ---------- Ayarlar ---------- */
$TEST_OTP     = '111111';
$phoneText    = '0551 692 19 17';
$phone        = '+905516921917';
$address      = 'Kayalık, 02030 Adıyaman Merkez/Adıyaman';
$instagramUrl = 'https://www.instagram.com/recepdundar.hairartist/';
$tiktokUrl    = 'https://www.tiktok.com/@recepdundar.hairartist';
$homeUrl      = '../';
$homePage     = '../index.php';

$trDays   = [1=>'Pazartesi',2=>'Salı',3=>'Çarşamba',4=>'Perşembe',5=>'Cuma',6=>'Cumartesi',7=>'Pazar'];
$trDaysShort = [1=>'Pzt',2=>'Sal',3=>'Çar',4=>'Per',5=>'Cum',6=>'Cmt',7=>'Paz'];
$trMonths = [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

function icon(string $name): string
{
    $paths = [
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'chat' => '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l-3 3V11.5A7.5 7.5 0 0 1 9.5 4h3a7.5 7.5 0 0 1 7.5 7.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/* ---------- Bağlantı ---------- */
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('Veritabanına bağlanılamadı.');
}

/* ---------- Tablolar ---------- */
$pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_time (appointment_date, appointment_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS time_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot_time TIME NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$defaults = [
    'days_ahead'      => '7',
    'slot_capacity'   => '1',
    'blocked_weekdays'=> '[]',
];
foreach ($defaults as $k => $v) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute([$k, $v]);
}

if ((int)$pdo->query("SELECT COUNT(*) FROM time_slots")->fetchColumn() === 0) {
    $seed = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];
    $ins = $pdo->prepare("INSERT INTO time_slots (slot_time, is_active, sort_order) VALUES (?, 1, ?)");
    foreach ($seed as $i => $t) $ins->execute([$t . ':00', $i]);
}

$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$daysAhead = (int)($settings['days_ahead'] ?? 7);
if ($daysAhead < 1 || $daysAhead > 90) $daysAhead = 7;
$slotCapacity = (int)($settings['slot_capacity'] ?? 1);
if ($slotCapacity < 1) $slotCapacity = 1;
$blockedWeekdays = json_decode($settings['blocked_weekdays'] ?? '[]', true);
if (!is_array($blockedWeekdays)) $blockedWeekdays = [];
$blockedWeekdays = array_map('intval', $blockedWeekdays);

$slotsActive = [];
foreach ($pdo->query("SELECT TIME_FORMAT(slot_time,'%H:%i') AS t FROM time_slots WHERE is_active=1 ORDER BY sort_order, slot_time") as $r) {
    $slotsActive[] = $r['t'];
}
if (!$slotsActive) $slotsActive = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];

$bookedCount = [];
$stmt = $pdo->prepare("SELECT appointment_date AS d, TIME_FORMAT(appointment_time,'%H:%i') AS t, COUNT(*) AS c
    FROM appointments
    WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :d DAY)
    GROUP BY appointment_date, appointment_time");
$stmt->bindValue(':d', $daysAhead, PDO::PARAM_INT);
$stmt->execute();
foreach ($stmt as $r) {
    $bookedCount[$r['d']][$r['t']] = (int)$r['c'];
}

$nowHm = date('H:i');
$days = [];
$today = new DateTime('today');
for ($i = 0; $i <= $daysAhead; $i++) {
    $d = (clone $today)->modify("+$i day");
    $iso = $d->format('Y-m-d');
    $wd  = (int)$d->format('N');

    if (in_array($wd, $blockedWeekdays, true)) continue;

    $slotList = [];
    $freeSlots = 0;
    foreach ($slotsActive as $t) {
        $count = $bookedCount[$iso][$t] ?? 0;
        $past  = ($i === 0) && ($t <= $nowHm);
        $full  = $count >= $slotCapacity;
        $avail = !$past && !$full;
        if ($avail) $freeSlots++;
        $slotList[] = [
            'time'    => $t,
            'booked'  => $count,
            'capacity'=> $slotCapacity,
            'past'    => $past,
            'full'    => $full,
            'available'=> $avail,
        ];
    }

    $days[] = [
        'iso'       => $iso,
        'dayName'   => $trDays[$wd],
        'dayShort'  => $trDaysShort[$wd],
        'dayNum'    => (int)$d->format('j'),
        'monthName' => $trMonths[(int)$d->format('n')],
        'monthShort'=> mb_substr($trMonths[(int)$d->format('n')], 0, 3),
        'freeCount' => $freeSlots,
        'totalSlots'=> count($slotList),
        'isToday'   => $i === 0,
        'isTomorrow'=> $i === 1,
        'slots'     => $slotList,
    ];
}

/* ---------- POST ---------- */
$step = 1;
$errors = [];
$successData = null;
$pending = $_SESSION['pending'] ?? null;

if (isset($_GET['iptal'])) {
    unset($_SESSION['pending']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_otp') {
        $name  = trim((string)($_POST['name']  ?? ''));
        $phoneRaw = preg_replace('/\D/', '', (string)($_POST['phone'] ?? ''));
        $date  = (string)($_POST['date'] ?? '');
        $time  = (string)($_POST['time'] ?? '');

        if (mb_strlen($name) < 3) $errors[] = 'Lütfen ad ve soyadınızı girin.';
        if (strlen($phoneRaw) !== 10 || $phoneRaw[0] !== '5') $errors[] = 'Geçerli bir telefon numarası girin.';

        $validDates = array_column($days, 'iso');
        if (!in_array($date, $validDates, true)) $errors[] = 'Lütfen bir gün seçin.';
        if (!in_array($time, $slotsActive, true)) $errors[] = 'Lütfen bir saat seçin.';

        if (empty($errors)) {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date=? AND appointment_time=?");
            $chk->execute([$date, $time . ':00']);
            if ((int)$chk->fetchColumn() >= $slotCapacity) {
                $errors[] = 'Seçtiğiniz saat doldu. Lütfen farklı bir saat seçin.';
            }
        }

        if (empty($errors)) {
            $_SESSION['pending'] = [
                'name'    => $name,
                'phone'   => '+90' . $phoneRaw,
                'date'    => $date,
                'time'    => $time,
                'created' => time(),
            ];
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    if ($action === 'verify_otp') {
        $code = trim((string)($_POST['code'] ?? ''));
        if (!$pending || (time() - $pending['created']) > 900) {
            $errors[] = 'Doğrulama süresi doldu. Lütfen tekrar deneyin.';
            unset($_SESSION['pending']);
        } elseif ($code !== $TEST_OTP) {
            $errors[] = 'Doğrulama kodu hatalı.';
        } else {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date=? AND appointment_time=?");
            $chk->execute([$pending['date'], $pending['time'] . ':00']);
            if ((int)$chk->fetchColumn() >= $slotCapacity) {
                $errors[] = 'Seçtiğiniz saat doldu. Lütfen baştan deneyin.';
                unset($_SESSION['pending']);
            } else {
                $ins = $pdo->prepare("INSERT INTO appointments (name, phone, appointment_date, appointment_time) VALUES (?,?,?,?)");
                $ins->execute([$pending['name'], $pending['phone'], $pending['date'], $pending['time'] . ':00']);
                $_SESSION['success'] = $pending;
                unset($_SESSION['pending']);
                header('Location: ' . $_SERVER['PHP_SELF'] . '?ok=1');
                exit;
            }
        }
    }
}

if (isset($_GET['ok']) && isset($_SESSION['success'])) {
    $successData = $_SESSION['success'];
    unset($_SESSION['success']);
    $step = 3;
    $pending = null;
} elseif ($pending && empty($errors) && (time() - $pending['created']) <= 900) {
    $step = 2;
} elseif ($pending && empty($errors)) {
    unset($_SESSION['pending']);
    $pending = null;
}

$successLabel = '';
if ($successData) {
    foreach ($days as $d) {
        if ($d['iso'] === $successData['date']) {
            $successLabel = $d['dayName'] . ', ' . $d['dayNum'] . ' ' . $d['monthName'];
            break;
        }
    }
    if ($successLabel === '') {
        $dt = new DateTime($successData['date']);
        $successLabel = $trDays[(int)$dt->format('N')] . ', ' . $dt->format('j') . ' ' . $trMonths[(int)$dt->format('n')];
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080808">
    <title>Randevu · Recep Dündar Saç Tasarım Merkezi</title>
    <meta name="description" content="Recep Dündar Saç Tasarım Merkezi için online randevu oluşturun.">
    <meta name="robots" content="index, follow">
    <link rel="icon" href="../logo.png" type="image/png">
    <link rel="apple-touch-icon" href="../logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root{--black:#080808;--black-2:#10100f;--panel:#171715;--yellow:#ffc21a;--white:#f7f7f4;--text:#d0d0ca;--muted:#aaa9a2;--line:rgba(255,255,255,.13);--danger:#ff5d5d;--ok:#4ade80;--sans:Arial,Helvetica,sans-serif;--serif:Georgia,'Times New Roman',serif}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth;scroll-padding-top:24px}
        body{margin:0;background:var(--black);color:var(--white);font-family:var(--sans);-webkit-font-smoothing:antialiased;overflow-x:hidden}
        a{color:inherit;text-decoration:none}
        img{display:block;max-width:100%}
        .container{width:min(1140px,calc(100% - 48px));margin:0 auto}
        .icon{width:18px;height:18px;flex:0 0 18px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round}

        .header{border-bottom:1px solid var(--line);background:var(--black)}
        .header-inner{min-height:88px;display:flex;align-items:center;justify-content:space-between;gap:25px}
        .brand{display:inline-flex;align-items:center;gap:13px;min-width:0}
        .brand img{width:62px;height:62px;flex:none;border-radius:50%;object-fit:cover;background:#000}
        .brand-copy{display:grid;gap:4px}
        .brand-copy strong{font-size:13px;letter-spacing:.04em;text-transform:uppercase;font-weight:700}
        .brand-copy span{color:var(--yellow);font-family:Georgia,'Times New Roman',serif;font-style:italic;font-weight:400;font-size:12.5px;letter-spacing:.02em;line-height:1.15}

        .nav{display:flex;align-items:center;gap:28px;color:#d5d5d0;font-size:13px}
        .nav>a:not(.button){position:relative;padding:4px 0}
        .nav>a:not(.button):after{content:'';position:absolute;left:0;bottom:0;width:0;height:1px;background:var(--yellow);transition:width .28s ease}
        .nav>a:not(.button):hover{color:var(--yellow)}
        .nav>a:not(.button):hover:after{width:100%}
        .header-social{display:flex;align-items:center;gap:14px;margin-left:8px;padding-left:18px;border-left:1px solid var(--line)}
        .header-social a{color:#c4c4bd;font-size:16px;transition:color .25s,transform .25s;display:inline-flex}
        .header-social a:hover{color:var(--yellow);transform:translateY(-2px)}

        .button,.button-secondary{min-height:48px;display:inline-flex;justify-content:center;align-items:center;gap:10px;padding:0 22px;font-size:12px;font-weight:700;transition:background .18s,border-color .18s,color .18s,transform .2s;position:relative;cursor:pointer;font-family:inherit;border-radius:0;letter-spacing:.04em}
        .button{border:1px solid var(--yellow);background:var(--yellow);color:#111}
        .button:hover:not(:disabled){background:#ffd34f;border-color:#ffd34f;transform:translateY(-1px)}
        .button:disabled{opacity:.45;cursor:not-allowed;transform:none}
        .button-secondary{border:1px solid rgba(255,255,255,.48);color:var(--white);background:transparent}
        .button-secondary:hover{border-color:var(--yellow);color:var(--yellow);transform:translateY(-1px)}
        .button i.fa-arrow-right,.button-secondary i.fa-arrow-right{font-size:12px;transition:transform .25s}
        .button:hover:not(:disabled) i.fa-arrow-right{transform:translateX(3px)}
        .button-ghost{background:transparent;border:1px solid var(--line);color:var(--muted);min-height:48px;padding:0 18px;font-size:12px;font-weight:700;letter-spacing:.04em;cursor:pointer;transition:color .2s,border-color .2s;font-family:inherit;border-radius:0;display:inline-flex;align-items:center;gap:8px}
        .button-ghost:hover{color:var(--white);border-color:rgba(255,255,255,.35)}
        .button-ghost i{font-size:11px}

        .booking-hero{position:relative;padding:64px 0 38px;overflow:hidden}
        .hero-particles{position:absolute;inset:0;pointer-events:none;z-index:0}
        .hero-particles span{position:absolute;width:3px;height:3px;border-radius:50%;background:var(--yellow);box-shadow:0 0 8px rgba(255,194,26,.7);animation:float linear infinite}
        @keyframes float{0%,100%{transform:translateY(0) scale(.5);opacity:0}15%{opacity:.85}85%{opacity:.85}50%{transform:translateY(-70px) scale(1.15);opacity:.9}}
        .booking-hero .container{position:relative;z-index:1;max-width:840px}
        .section-kicker{margin:0 0 12px;color:var(--yellow);font-size:10px;font-weight:700;letter-spacing:.17em;text-transform:uppercase}
        .booking-hero h1{margin:0 0 14px;font:400 clamp(34px,4.4vw,52px)/1.1 var(--serif);letter-spacing:-.03em}
        .booking-hero h1 em{font-style:italic;color:var(--yellow)}
        .booking-hero p{margin:0;color:var(--text);font-size:14px;line-height:1.85;max-width:600px}

        .booking-section{padding:0 0 90px}
        .booking-card{border:1px solid var(--line);background:var(--panel);padding:34px 40px;position:relative;overflow:hidden}
        .booking-card:before{content:'';position:absolute;top:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,var(--yellow),transparent)}

        .steps{display:flex;align-items:center;gap:14px;margin-bottom:30px}
        .step-item{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:11px;letter-spacing:.14em;text-transform:uppercase;font-weight:700}
        .step-num{width:26px;height:26px;display:grid;place-items:center;border:1px solid var(--line);font-size:11px;border-radius:50%;transition:all .3s}
        .step-item.active{color:var(--yellow)}
        .step-item.active .step-num{border-color:var(--yellow);background:var(--yellow);color:#111}
        .step-item.done{color:#fff}
        .step-item.done .step-num{border-color:var(--yellow);color:var(--yellow)}
        .step-sep{flex:1;height:1px;background:var(--line);max-width:60px}

        .wiz-progress{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:26px;padding-bottom:18px;border-bottom:1px solid var(--line)}
        .wiz-progress-label{color:var(--muted);font-size:10px;letter-spacing:.16em;text-transform:uppercase;font-weight:700}
        .wiz-progress-label b{color:var(--yellow);font-weight:700}
        .wiz-dots{display:flex;gap:6px}
        .wiz-dots i{display:block;width:22px;height:3px;background:rgba(255,255,255,.13);transition:background .3s}
        .wiz-dots i.on{background:var(--yellow)}

        .wizard{position:relative}
        .wiz-step{display:none}
        .wiz-step.is-active{display:block;animation:wizIn .45s cubic-bezier(.2,.75,.25,1)}
        .wiz-step.is-back{animation:wizInBack .45s cubic-bezier(.2,.75,.25,1)}
        @keyframes wizIn{from{opacity:0;transform:translateX(24px)}to{opacity:1;transform:none}}
        @keyframes wizInBack{from{opacity:0;transform:translateX(-24px)}to{opacity:1;transform:none}}

        .wiz-title{display:flex;align-items:center;gap:12px;margin:0 0 20px;font:400 clamp(24px,3vw,30px)/1.2 var(--serif);letter-spacing:-.02em}
        .wiz-title i{color:var(--yellow);font-size:20px}

        .form-row{margin-bottom:22px}
        .form-label{display:block;margin-bottom:10px;color:var(--muted);font-size:10px;letter-spacing:.16em;text-transform:uppercase;font-weight:700}
        .form-input{width:100%;background:rgba(255,255,255,.02);border:1px solid var(--line);color:var(--white);font-family:inherit;font-size:15px;padding:15px 16px;transition:border-color .25s,background .25s;border-radius:0}
        .form-input:focus{outline:none;border-color:var(--yellow);background:rgba(255,194,26,.03)}
        .form-input::placeholder{color:#666}

        .phone-wrap{display:flex;align-items:stretch;border:1px solid var(--line);background:rgba(255,255,255,.02);transition:border-color .25s,background .25s}
        .phone-wrap:focus-within{border-color:var(--yellow);background:rgba(255,194,26,.03)}
        .phone-prefix{display:flex;align-items:center;padding:0 14px;color:var(--yellow);font-weight:700;border-right:1px solid var(--line);font-size:15px}
        .phone-wrap input{flex:1;background:transparent;border:0;color:var(--white);font-family:inherit;font-size:15px;padding:15px 16px;outline:none;letter-spacing:.03em}
        .phone-wrap input::placeholder{color:#666;letter-spacing:normal}

        .day-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px}
        .day-card{position:relative;padding:16px 14px 14px;border:1px solid var(--line);background:rgba(255,255,255,.015);text-align:left;color:var(--white);cursor:pointer;font-family:inherit;transition:all .22s;overflow:hidden;display:flex;flex-direction:column;gap:2px}
        .day-card:hover:not(:disabled){border-color:rgba(255,194,26,.55);background:rgba(255,194,26,.04);transform:translateY(-2px)}
        .day-card:disabled{cursor:not-allowed;opacity:.42}
        .dc-top{font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);font-weight:700;margin-bottom:6px}
        .day-card.is-today .dc-top{color:var(--yellow)}
        .dc-num{font:400 26px/1 var(--serif);color:var(--white);letter-spacing:-.01em}
        .dc-month{font-size:11px;color:var(--muted);letter-spacing:.02em;margin-top:3px}
        .dc-status{margin-top:10px;font-size:10.5px;letter-spacing:.04em;color:var(--ok);font-weight:600}
        .day-card.is-full .dc-status{color:var(--danger)}
        .day-card.is-selected{border-color:var(--yellow);background:rgba(255,194,26,.09);transform:none}
        .day-card.is-selected:after{content:'';position:absolute;left:0;top:0;width:3px;height:100%;background:var(--yellow)}

        .time-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(92px,1fr));gap:8px}
        .time-chip{padding:14px 8px;border:1px solid var(--line);background:rgba(255,255,255,.015);color:var(--white);cursor:pointer;font-family:inherit;font-size:13.5px;font-weight:600;letter-spacing:.04em;transition:all .22s;position:relative;text-align:center}
        .time-chip:hover:not(:disabled){border-color:rgba(255,194,26,.6);background:rgba(255,194,26,.04);transform:translateY(-1px)}
        .time-chip.is-selected{border-color:var(--yellow);background:var(--yellow);color:#111}
        .time-chip:disabled{cursor:not-allowed;color:#555;border-style:dashed;background:transparent}
        .time-chip.is-full{color:var(--danger);text-decoration:line-through;opacity:.6}
        .time-chip.is-past{color:#4a4a4a;opacity:.55}
        .time-chip .tc-rem{display:block;font-size:9px;letter-spacing:.06em;font-weight:500;color:var(--muted);margin-top:3px;text-transform:uppercase}
        .time-chip.is-selected .tc-rem{color:rgba(0,0,0,.55)}
        .time-chip.is-full .tc-rem{color:var(--danger);text-decoration:none}
        .time-chip.is-past .tc-rem{color:#4a4a4a}

        .legend{display:flex;flex-wrap:wrap;gap:18px;margin-top:16px;color:var(--muted);font-size:11px}
        .legend span{display:inline-flex;align-items:center;gap:7px}
        .legend i{width:10px;height:10px;display:inline-block;border:1px solid var(--line)}
        .legend i.lg-free{background:rgba(255,255,255,.02)}
        .legend i.lg-sel{background:var(--yellow);border-color:var(--yellow)}
        .legend i.lg-book{background:transparent;border-style:dashed}
        .legend i.lg-past{background:#1a1a1a}

        .alert{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid;margin-bottom:24px;font-size:13px;line-height:1.6}
        .alert-error{border-color:rgba(255,93,93,.5);background:rgba(255,93,93,.06);color:#ffb2b2}
        .alert ul{margin:0;padding-left:18px}

        .wiz-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-top:28px;padding-top:22px;border-top:1px solid var(--line)}
        .wiz-actions .right{display:flex;gap:10px;margin-left:auto}

        .otp-block{max-width:480px}
        .otp-block .otp-info{margin:0 0 22px;color:var(--text);font-size:13.5px;line-height:1.8}
        .otp-block .otp-info strong{color:var(--yellow);font-weight:600}
        .otp-input{width:100%;background:rgba(255,255,255,.02);border:1px solid var(--line);color:var(--white);font-family:'Courier New',monospace;font-size:30px;letter-spacing:.6em;text-align:center;padding:20px 16px 20px 26px;transition:border-color .25s,background .25s;border-radius:0}
        .otp-input:focus{outline:none;border-color:var(--yellow);background:rgba(255,194,26,.03)}
        .otp-meta{display:flex;justify-content:flex-end;gap:14px;margin-top:12px;color:var(--muted);font-size:11px}
        .otp-meta a{color:var(--yellow)}
        .otp-meta a:hover{text-decoration:underline}

        .success-block{text-align:center;padding:20px 0 10px}
        .success-icon{width:76px;height:76px;display:grid;place-items:center;margin:0 auto 22px;border:1px solid var(--ok);color:var(--ok);border-radius:50%;font-size:32px;animation:okPop .55s cubic-bezier(.2,.9,.3,1.2)}
        @keyframes okPop{0%{transform:scale(.4);opacity:0}100%{transform:scale(1);opacity:1}}
        .success-block h2{margin:0 0 12px;font:400 clamp(28px,3.6vw,40px)/1.15 var(--serif)}
        .success-block p{max-width:520px;margin:0 auto 22px;color:var(--text);font-size:14px;line-height:1.85}
        .success-summary{display:inline-grid;grid-template-columns:auto auto;gap:10px 22px;padding:20px 26px;border:1px solid var(--line);background:rgba(255,255,255,.02);text-align:left;margin-bottom:26px;font-size:13px}
        .success-summary dt{color:var(--muted);font-size:10px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;align-self:center}
        .success-summary dd{margin:0;color:var(--white);font-weight:600}
        .success-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}

        .footer{padding:20px 0;border-top:1px solid var(--line);color:#92928c;font-size:11px}
        .footer .container{display:flex;justify-content:space-between;gap:15px}
        .footer-social{display:flex;gap:14px}
        .footer-social a{color:#92928c;font-size:15px;transition:color .25s,transform .25s;display:inline-flex}
        .footer-social a:hover{color:var(--yellow);transform:translateY(-2px)}

        .mobile-actions,.menu-toggle,.mobile-menu{display:none}

        .reveal{opacity:0;transform:translateY(30px);transition:opacity .85s ease,transform .85s cubic-bezier(.2,.75,.25,1)}
        .reveal.is-visible{opacity:1;transform:none}

        @media(max-width:800px){
            .container{width:min(100% - 36px,680px)}
            .header{position:relative;z-index:20}
            .header-inner{min-height:76px;position:relative}
            .brand img{width:53px;height:53px}
            .brand-copy strong{font-size:11px}
            .brand-copy span{font-size:11px}
            .nav{display:none}
            .mobile-actions{display:flex;align-items:center;gap:10px}
            .menu-toggle{width:43px;height:43px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;border:1px solid rgba(255,255,255,.42);background:transparent;cursor:pointer}
            .menu-toggle span{width:19px;height:2px;background:var(--white);transition:transform .28s ease,opacity .18s ease}
            .menu-toggle[aria-expanded=true] span:first-child{transform:translateY(7px) rotate(45deg)}
            .menu-toggle[aria-expanded=true] span:nth-child(2){opacity:0}
            .menu-toggle[aria-expanded=true] span:last-child{transform:translateY(-7px) rotate(-45deg)}

            .mobile-menu{position:fixed;inset:0;z-index:100;display:flex;flex-direction:column;height:100vh;height:100dvh;overflow-y:auto;padding:calc(18px + env(safe-area-inset-top)) max(24px,calc((100vw - 680px)/2)) calc(22px + env(safe-area-inset-bottom));background:#090909;opacity:0;visibility:hidden;pointer-events:none;transform:translateY(-14px) scale(.99);transition:opacity .35s ease,visibility .35s ease,transform .45s cubic-bezier(.2,.75,.25,1)}
            .mobile-menu.is-open{opacity:1;visibility:visible;pointer-events:auto;transform:translateY(0) scale(1)}
            .menu-top{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between}
            .menu-brand{display:flex;align-items:center;gap:12px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .menu-brand img{width:48px;height:48px;border-radius:50%;object-fit:cover}
            .menu-brand small{display:block;margin-top:4px;color:var(--yellow);font-family:Georgia,'Times New Roman',serif;font-style:italic;font-weight:400;font-size:11px;letter-spacing:.02em;text-transform:none}
            .menu-close{width:46px;height:46px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.35);background:transparent;color:var(--white);cursor:pointer;transition:border-color .25s,transform .25s}
            .menu-close:hover{border-color:var(--yellow);transform:rotate(90deg)}
            .menu-close svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.7;stroke-linecap:round}
            .menu-body{position:relative;z-index:1;display:flex;flex:1;flex-direction:column;justify-content:center;width:min(100%,650px);margin:0 auto;padding:26px 0}
            .mobile-nav{display:flex;flex-direction:column}
            .mobile-nav a{display:flex;align-items:center;gap:19px;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.14);font:400 clamp(34px,8vw,58px)/1.12 var(--serif);transition:color .25s,padding-left .3s;opacity:0;transform:translateY(20px)}
            .mobile-menu.is-open .mobile-nav a{animation:menuItemIn .55s cubic-bezier(.2,.75,.25,1) forwards}
            .mobile-menu.is-open .mobile-nav a:nth-child(1){animation-delay:.08s}
            .mobile-menu.is-open .mobile-nav a:nth-child(2){animation-delay:.14s}
            .mobile-menu.is-open .mobile-nav a:nth-child(3){animation-delay:.20s}
            .mobile-menu.is-open .mobile-nav a:nth-child(4){animation-delay:.26s}
            @keyframes menuItemIn{to{opacity:1;transform:none}}
            .mobile-nav a:hover{padding-left:8px;color:var(--yellow)}
            .mobile-nav a span{width:26px;color:var(--yellow);font:700 10px var(--sans);letter-spacing:.08em}
            .menu-social{display:flex;gap:16px;margin-top:22px}
            .menu-social a{color:var(--muted);font-size:20px;transition:color .25s,transform .25s}
            .menu-social a:hover{color:var(--yellow);transform:translateY(-3px)}
            .menu-orbit{position:absolute;right:-14vw;bottom:-35vw;width:min(82vw,680px);aspect-ratio:1;border:1px solid rgba(255,194,26,.12);border-radius:50%;pointer-events:none}
            body.menu-open{overflow:hidden}

            .booking-hero{padding:38px 0 24px}
            .booking-card{padding:24px 20px}
            .steps{gap:10px;margin-bottom:24px}
            .step-item span:not(.step-num){display:none}
            .step-sep{max-width:none}
            .day-grid{grid-template-columns:repeat(auto-fill,minmax(108px,1fr))}
            .time-grid{grid-template-columns:repeat(auto-fill,minmax(84px,1fr))}
            .booking-section{padding-bottom:60px}
        }

        @media(max-width:520px){
            .container{width:calc(100% - 32px)}
            .header-inner{min-height:69px;gap:8px}
            .brand{gap:8px}
            .brand img{width:45px;height:45px}
            .brand-copy{gap:3px}
            .brand-copy strong{font-size:9px}
            .brand-copy span{font-size:10px}
            .menu-toggle{width:41px;height:41px}
            .mobile-menu{padding-left:21px;padding-right:21px}
            .mobile-nav a{padding:12px 0;font-size:clamp(31px,9vw,45px)}

            .booking-hero{padding:26px 0 20px}
            .booking-hero h1{font-size:clamp(28px,8vw,40px)}
            .booking-card{padding:20px 16px}
            .day-grid{grid-template-columns:repeat(2,1fr)}
            .time-grid{grid-template-columns:repeat(3,1fr)}
            .wiz-actions{flex-direction:column-reverse;align-items:stretch}
            .wiz-actions .right{flex-direction:column;width:100%}
            .wiz-actions .button,.wiz-actions .button-secondary,.wiz-actions .button-ghost{width:100%}
            .wiz-title{font-size:22px}
            .wiz-title i{font-size:17px}
            .otp-input{font-size:24px;letter-spacing:.45em;padding-left:24px}
            .success-summary{grid-template-columns:1fr;text-align:left}
            .footer .container{flex-direction:column;gap:10px}
        }

        @media(prefers-reduced-motion:reduce){
            html{scroll-behavior:auto}
            .mobile-menu,.mobile-nav a,.menu-toggle span,.reveal,.wiz-step.is-active,.wiz-step.is-back{transition:none!important;animation:none!important}
            .reveal{opacity:1;transform:none}
            .hero-particles{display:none}
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a class="brand" href="<?= $homePage ?>" aria-label="Recep Dündar Saç Tasarım Merkezi ana sayfa">
                <img src="../logo.png" alt="Recep Dündar Saç Tasarım Merkezi logosu" width="62" height="62">
                <span class="brand-copy">
                    <strong>Recep Dündar</strong>
                    <span>Saç Tasarım Merkezi</span>
                </span>
            </a>
            <nav class="nav" aria-label="Ana menü">
                <a href="<?= $homePage ?>#hizmetler">Hizmetler</a>
                <a href="<?= $homePage ?>#salon">Salonumuz</a>
                <a href="<?= $homePage ?>#iletisim">İletişim</a>
                <span class="header-social">
                    <a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </span>
            </nav>
            <div class="mobile-actions">
                <button class="menu-toggle" type="button" aria-label="Menüyü aç" aria-expanded="false" aria-controls="mobile-menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Ana menü" aria-hidden="true" inert>
        <div class="menu-top">
            <a class="menu-brand" href="<?= $homePage ?>" aria-label="Ana sayfa">
                <img src="../logo.png" alt="" width="50" height="50">
                <span>Recep Dündar<small>Saç Tasarım Merkezi</small></span>
            </a>
            <button class="menu-close" type="button" aria-label="Menüyü kapat">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="menu-body">
            <nav class="mobile-nav" aria-label="Mobil menü">
                <a href="<?= $homePage ?>#hizmetler"><span>01</span>Hizmetler</a>
                <a href="<?= $homePage ?>#salon"><span>02</span>Salonumuz</a>
                <a href="<?= $homePage ?>#hakkimizda"><span>03</span>Hakkımızda</a>
                <a href="<?= $homePage ?>#iletisim"><span>04</span>İletişim</a>
            </nav>
            <div class="menu-social">
                <a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            </div>
        </div>
        <div class="menu-orbit" aria-hidden="true"></div>
    </div>

    <main>
        <section class="booking-hero">
            <div class="hero-particles" aria-hidden="true"></div>
            <div class="container">
                <p class="section-kicker">Online randevu</p>
                <h1>Size uygun <em>günü ve saati</em> birlikte planlayalım.</h1>
                <p>Adım adım ilerleyin: bilgilerinizi girin, uygun gün ve saati seçin, telefonunuzu doğrulayın.</p>
            </div>
        </section>

        <section class="booking-section">
            <div class="container">
                <div class="booking-card" data-reveal>

                    <div class="steps" aria-label="Adımlar">
                        <div class="step-item <?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>">
                            <span class="step-num">1</span><span>Bilgiler</span>
                        </div>
                        <div class="step-sep"></div>
                        <div class="step-item <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">
                            <span class="step-num">2</span><span>Doğrulama</span>
                        </div>
                        <div class="step-sep"></div>
                        <div class="step-item <?= $step === 3 ? 'active' : '' ?>">
                            <span class="step-num">3</span><span>Tamamlandı</span>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error" role="alert">
                            <?= icon('chat') ?>
                            <div>
                                <?php if (count($errors) === 1): ?>
                                    <?= htmlspecialchars($errors[0], ENT_QUOTES, 'UTF-8') ?>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ($errors as $e): ?>
                                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($step === 1): ?>

                        <form method="post" id="bookingForm" novalidate>
                            <input type="hidden" name="action" value="send_otp">
                            <input type="hidden" name="date" id="dateInput" value="">
                            <input type="hidden" name="time" id="timeInput" value="">

                            <div class="wiz-progress">
                                <span class="wiz-progress-label">Adım <b id="wizLabel">1 / 4</b></span>
                                <div class="wiz-dots" aria-hidden="true">
                                    <i class="on" data-dot="1"></i><i data-dot="2"></i><i data-dot="3"></i><i data-dot="4"></i>
                                </div>
                            </div>

                            <div class="wizard" id="wizard" data-step="1">

                                <div class="wiz-step is-active" data-wiz="1">
                                    <h2 class="wiz-title"><i class="fa-solid fa-user"></i>Ad Soyad</h2>
                                    <div class="form-row">
                                        <label class="form-label" for="nameInput">Adınız ve soyadınız</label>
                                        <input class="form-input" type="text" id="nameInput" name="name" autocomplete="name" required maxlength="120" placeholder="Örn. Ahmet Yılmaz" value="<?= htmlspecialchars((string)($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="wiz-actions">
                                        <span></span>
                                        <div class="right">
                                            <button type="button" class="button" data-next>Devam <i class="fa-solid fa-arrow-right"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="wiz-step" data-wiz="2">
                                    <h2 class="wiz-title"><i class="fa-solid fa-calendar"></i>Hangi gün?</h2>
                                    <div class="day-grid" id="dayGrid">
                                        <?php foreach ($days as $d): ?>
                                            <button type="button"
                                                class="day-card <?= $d['isToday'] ? 'is-today' : '' ?> <?= $d['freeCount'] <= 0 ? 'is-full' : '' ?>"
                                                data-date="<?= htmlspecialchars($d['iso'], ENT_QUOTES, 'UTF-8') ?>"
                                                <?= $d['freeCount'] <= 0 ? 'disabled' : '' ?>>
                                                <span class="dc-top"><?= $d['isToday'] ? 'Bugün' : ($d['isTomorrow'] ? 'Yarın' : htmlspecialchars($d['dayName'], ENT_QUOTES, 'UTF-8')) ?></span>
                                                <span class="dc-num"><?= $d['dayNum'] ?></span>
                                                <span class="dc-month"><?= htmlspecialchars($d['monthName'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($d['dayShort'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="dc-status"><?= $d['freeCount'] <= 0 ? 'Dolu' : $d['freeCount'] . ' saat müsait' ?></span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="wiz-actions">
                                        <button type="button" class="button-ghost" data-prev><i class="fa-solid fa-arrow-left"></i> Geri</button>
                                        <div class="right">
                                            <button type="button" class="button" data-next disabled data-gate="day">Devam <i class="fa-solid fa-arrow-right"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="wiz-step" data-wiz="3">
                                    <h2 class="wiz-title"><i class="fa-solid fa-clock"></i>Saat kaçta?</h2>
                                    <div class="time-grid" id="timeGrid"></div>
                                    <div class="legend">
                                        <span><i class="lg-free"></i> Müsait</span>
                                        <span><i class="lg-sel"></i> Seçili</span>
                                        <span><i class="lg-book"></i> Dolu</span>
                                        <span><i class="lg-past"></i> Geçmiş</span>
                                    </div>
                                    <div class="wiz-actions">
                                        <button type="button" class="button-ghost" data-prev><i class="fa-solid fa-arrow-left"></i> Geri</button>
                                        <div class="right">
                                            <button type="button" class="button" data-next disabled data-gate="time">Devam <i class="fa-solid fa-arrow-right"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="wiz-step" data-wiz="4">
                                    <h2 class="wiz-title"><i class="fa-solid fa-phone"></i>Telefon numarası</h2>
                                    <div class="form-row">
                                        <label class="form-label" for="phoneInput">Cep telefonu</label>
                                        <div class="phone-wrap">
                                            <span class="phone-prefix">+90</span>
                                            <input type="tel" id="phoneInput" name="phone" inputmode="numeric" autocomplete="tel-national" maxlength="14" placeholder="5XX XXX XX XX" value="<?= htmlspecialchars((string)($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <div class="wiz-actions">
                                        <button type="button" class="button-ghost" data-prev><i class="fa-solid fa-arrow-left"></i> Geri</button>
                                        <div class="right">
                                            <button type="submit" class="button" id="submitBtn" disabled>Kodu gönder <i class="fa-solid fa-arrow-right"></i></button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </form>

                    <?php elseif ($step === 2): ?>

                        <div class="otp-block">
                            <h2 class="wiz-title"><i class="fa-solid fa-shield-halved"></i>Doğrulama</h2>
                            <p class="otp-info">
                                <strong><?= htmlspecialchars($pending['phone'], ENT_QUOTES, 'UTF-8') ?></strong> numarasına gönderilen 6 haneli kodu girin.
                            </p>
                            <form method="post" id="otpForm">
                                <input type="hidden" name="action" value="verify_otp">
                                <div class="form-row">
                                    <label class="form-label" for="otpInput">Doğrulama kodu</label>
                                    <input class="otp-input" type="text" id="otpInput" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" placeholder="______" required autofocus>
                                </div>
                                <div class="otp-meta">
                                    <a href="?iptal=1">İptal et / baştan başla</a>
                                </div>
                                <div class="wiz-actions">
                                    <span></span>
                                    <div class="right">
                                        <button type="submit" class="button">Randevuyu onayla <i class="fa-solid fa-arrow-right"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    <?php else: ?>

                        <div class="success-block">
                            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
                            <h2>Randevunuz oluşturuldu.</h2>
                            <p>Randevu detaylarınız aşağıda. Belirtilen gün ve saatte salonumuzda sizi bekliyoruz.</p>
                            <dl class="success-summary">
                                <dt>Ad Soyad</dt><dd><?= htmlspecialchars($successData['name'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Telefon</dt><dd><?= htmlspecialchars($successData['phone'], ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Tarih</dt><dd><?= htmlspecialchars($successLabel, ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Saat</dt><dd><?= htmlspecialchars($successData['time'], ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                            <div class="success-actions">
                                <a class="button-secondary" href="<?= $homePage ?>">Ana sayfaya dön</a>
                                <a class="button" href="https://maps.app.goo.gl/ncCjh3JMye7J3ZQD8" target="_blank" rel="noopener noreferrer"><?= icon('pin') ?>Yol tarifi</a>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <span>© <?= date('Y') ?> Recep Dündar Saç Tasarım Merkezi</span>
            <div class="footer-social">
                <a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            </div>
        </div>
    </footer>

    <script>
        const DAYS = <?= json_encode($days, JSON_UNESCAPED_UNICODE) ?>;
        const SLOT_CAPACITY = <?= (int)$slotCapacity ?>;

        /* ---------- Partikül ---------- */
        (() => {
            const wrap = document.querySelector('.hero-particles');
            if (!wrap) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const count = window.innerWidth < 700 ? 12 : 20;
            for (let i = 0; i < count; i++) {
                const s = document.createElement('span');
                s.style.left = Math.random() * 100 + '%';
                s.style.top = (30 + Math.random() * 70) + '%';
                s.style.animationDuration = (7 + Math.random() * 7).toFixed(2) + 's';
                s.style.animationDelay = (-Math.random() * 10).toFixed(2) + 's';
                const size = (2 + Math.random() * 2.5).toFixed(2);
                s.style.width = size + 'px';
                s.style.height = size + 'px';
                wrap.appendChild(s);
            }
        })();

        /* ---------- Reveal ---------- */
        (() => {
            const el = document.querySelector('[data-reveal]');
            if (!el) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                el.classList.add('is-visible'); return;
            }
            el.classList.add('reveal');
            requestAnimationFrame(() => setTimeout(() => el.classList.add('is-visible'), 40));
        })();

        /* ---------- Wizard ---------- */
        (() => {
            const wizard = document.getElementById('wizard');
            if (!wizard) return;

            const steps = [...wizard.querySelectorAll('.wiz-step')];
            const dots = document.querySelectorAll('.wiz-dots i');
            const wizLabel = document.getElementById('wizLabel');

            const nameInput  = document.getElementById('nameInput');
            const phoneInput = document.getElementById('phoneInput');
            const dateInput  = document.getElementById('dateInput');
            const timeInput  = document.getElementById('timeInput');
            const submitBtn  = document.getElementById('submitBtn');
            const dayGrid    = document.getElementById('dayGrid');
            const timeGrid   = document.getElementById('timeGrid');

            let currentStep = 1;
            let selectedDate = '';
            let selectedTime = '';

            function setStep(n, back = false) {
                if (n < 1 || n > steps.length) return;
                currentStep = n;
                steps.forEach((el, idx) => {
                    el.classList.remove('is-active', 'is-back');
                    if (idx === n - 1) {
                        el.classList.add('is-active');
                        if (back) el.classList.add('is-back');
                    }
                });
                dots.forEach((d, i) => d.classList.toggle('on', i < n));
                if (wizLabel) wizLabel.textContent = n + ' / ' + steps.length;
                const focusTarget = steps[n-1].querySelector('input:not([type=hidden]), .day-card:not(:disabled), .time-chip:not(:disabled)');
                if (focusTarget) setTimeout(() => focusTarget.focus(), 120);
                const card = document.querySelector('.booking-card');
                if (card) {
                    const rect = card.getBoundingClientRect();
                    if (rect.top < 0) window.scrollTo({ top: window.scrollY + rect.top - 20, behavior: 'smooth' });
                }
            }

            function validateCurrent() {
                if (currentStep === 1) return nameInput.value.trim().length >= 3;
                if (currentStep === 2) return !!selectedDate;
                if (currentStep === 3) return !!selectedTime;
                if (currentStep === 4) {
                    const digits = phoneInput.value.replace(/\D/g, '');
                    return digits.length === 10 && digits[0] === '5';
                }
                return true;
            }

            function refreshGates() {
                document.querySelectorAll('[data-next]').forEach(btn => {
                    btn.disabled = !validateCurrent();
                });
                if (submitBtn) submitBtn.disabled = !validateCurrent();
            }

            wizard.querySelectorAll('[data-next]').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (!validateCurrent()) return;
                    setStep(currentStep + 1, false);
                    refreshGates();
                });
            });
            wizard.querySelectorAll('[data-prev]').forEach(btn => {
                btn.addEventListener('click', () => {
                    setStep(currentStep - 1, true);
                    refreshGates();
                });
            });

            if (nameInput) {
                nameInput.addEventListener('input', refreshGates);
                nameInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (validateCurrent()) { setStep(currentStep + 1); refreshGates(); }
                    }
                });
            }

            if (phoneInput) {
                phoneInput.addEventListener('input', () => {
                    let v = phoneInput.value.replace(/\D/g, '');
                    if (v.startsWith('90') && v.length > 10) v = v.slice(2);
                    if (v.startsWith('0')) v = v.slice(1);
                    v = v.slice(0, 10);
                    let out = v;
                    if (v.length > 3) out = v.slice(0, 3) + ' ' + v.slice(3);
                    if (v.length > 6) out = v.slice(0, 3) + ' ' + v.slice(3, 6) + ' ' + v.slice(6);
                    if (v.length > 8) out = v.slice(0, 3) + ' ' + v.slice(3, 6) + ' ' + v.slice(6, 8) + ' ' + v.slice(8);
                    phoneInput.value = out;
                    refreshGates();
                });
                phoneInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (validateCurrent()) { setStep(currentStep + 1); refreshGates(); }
                    }
                });
            }

            function renderSlots() {
                const day = DAYS.find(d => d.iso === selectedDate);
                if (!day || !timeGrid) return;
                timeGrid.innerHTML = '';
                day.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'time-chip';
                    btn.dataset.slot = slot.time;
                    btn.innerHTML = slot.time + '<span class="tc-rem">' +
                        (slot.past ? 'Geçmiş' : slot.full ? 'Dolu' : 'Müsait') + '</span>';
                    if (slot.past) btn.classList.add('is-past');
                    if (slot.full) btn.classList.add('is-full');
                    if (!slot.available) btn.disabled = true;
                    if (slot.time === selectedTime && slot.available) btn.classList.add('is-selected');
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        timeGrid.querySelectorAll('.time-chip').forEach(b => b.classList.remove('is-selected'));
                        btn.classList.add('is-selected');
                        selectedTime = slot.time;
                        timeInput.value = selectedTime;
                        refreshGates();
                    });
                    timeGrid.appendChild(btn);
                });
            }

            if (dayGrid) {
                dayGrid.querySelectorAll('.day-card').forEach(card => {
                    card.addEventListener('click', () => {
                        if (card.disabled) return;
                        dayGrid.querySelectorAll('.day-card').forEach(c => c.classList.remove('is-selected'));
                        card.classList.add('is-selected');
                        selectedDate = card.dataset.date;
                        dateInput.value = selectedDate;
                        selectedTime = '';
                        timeInput.value = '';
                        renderSlots();
                        refreshGates();
                    });
                });
            }

            refreshGates();
        })();

        /* ---------- OTP input ---------- */
        (() => {
            const otp = document.getElementById('otpInput');
            if (!otp) return;
            otp.addEventListener('input', () => {
                otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
            });
        })();

        /* ---------- Mobile menu ---------- */
        (() => {
            const overlay = document.getElementById('mobile-menu');
            const toggle = document.querySelector('.menu-toggle');
            const close = document.querySelector('.menu-close');
            if (!overlay || !toggle || !close) return;
            const focusables = () => [...overlay.querySelectorAll('a[href],button:not([disabled])')];
            const setMenu = (open) => {
                overlay.classList.toggle('is-open', open);
                overlay.setAttribute('aria-hidden', String(!open));
                overlay.inert = !open;
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
                document.body.classList.toggle('menu-open', open);
                if (open) close.focus(); else toggle.focus();
            };
            toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
            close.addEventListener('click', () => setMenu(false));
            overlay.querySelectorAll('.mobile-nav a').forEach(l => l.addEventListener('click', () => setMenu(false)));
            document.addEventListener('keydown', (e) => {
                if (toggle.getAttribute('aria-expanded') !== 'true') return;
                if (e.key === 'Escape') { setMenu(false); return; }
                if (e.key === 'Tab') {
                    const items = focusables(); if (!items.length) return;
                    if (e.shiftKey && document.activeElement === items[0]) { e.preventDefault(); items[items.length - 1].focus(); }
                    else if (!e.shiftKey && document.activeElement === items[items.length - 1]) { e.preventDefault(); items[0].focus(); }
                }
            });
        })();
    </script>
</body>
</html>
