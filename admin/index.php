<?php

declare(strict_types=1);

/* ---------- Uzun süreli oturum ---------- */
ini_set('session.gc_maxlifetime', (string)(60 * 60 * 24 * 365));
ini_set('session.cookie_lifetime', (string)(60 * 60 * 24 * 365));
session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 365,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
date_default_timezone_set('Europe/Istanbul');

/* ---------- Veritabanı ---------- */
$DB_HOST = '';
$DB_NAME = '';
$DB_USER = '';
$DB_PASS = '';

$ADMIN_USER = 'admin';
$ADMIN_PASS = '123456';

$trDays      = [1=>'Pazartesi',2=>'Salı',3=>'Çarşamba',4=>'Perşembe',5=>'Cuma',6=>'Cumartesi',7=>'Pazar'];
$trDaysShort = [1=>'Pzt',2=>'Sal',3=>'Çar',4=>'Per',5=>'Cum',6=>'Cmt',7=>'Paz'];
$trMonths    = [1=>'Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

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

$pdo->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
    token VARCHAR(64) PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ([
    'days_ahead'      => '7',
    'slot_capacity'   => '1',
    'blocked_weekdays'=> '[]',
] as $k => $v) {
    $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)")->execute([$k, $v]);
}

if ((int)$pdo->query("SELECT COUNT(*) FROM time_slots")->fetchColumn() === 0) {
    $seed = ['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];
    $ins = $pdo->prepare("INSERT INTO time_slots (slot_time, is_active, sort_order) VALUES (?, 1, ?)");
    foreach ($seed as $i => $t) $ins->execute([$t . ':00', $i]);
}

/* ---------- Otomatik giriş (cookie) ---------- */
if (empty($_SESSION['admin_logged']) && !empty($_COOKIE['admin_token'])) {
    $st = $pdo->prepare("SELECT token FROM admin_sessions WHERE token=? AND expires_at > NOW()");
    $st->execute([$_COOKIE['admin_token']]);
    if ($st->fetchColumn()) $_SESSION['admin_logged'] = true;
}

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
$loginError = '';

/* ---------- LOGIN ---------- */
if ($action === 'login') {
    $u = (string)($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    if ($u === $ADMIN_USER && $p === $ADMIN_PASS) {
        $_SESSION['admin_logged'] = true;
        $token = bin2hex(random_bytes(32));
        $exp   = (new DateTime('+1 year'))->format('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO admin_sessions (token, expires_at) VALUES (?, ?)")->execute([$token, $exp]);
        setcookie('admin_token', $token, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $pdo->exec("DELETE FROM admin_sessions WHERE expires_at < NOW()");
        header('Location: index.php');
        exit;
    }
    $loginError = 'Kullanıcı adı veya şifre hatalı.';
}

/* ---------- LOGOUT ---------- */
if ($action === 'logout') {
    if (!empty($_COOKIE['admin_token'])) {
        $pdo->prepare("DELETE FROM admin_sessions WHERE token=?")->execute([$_COOKIE['admin_token']]);
        setcookie('admin_token', '', ['expires' => time() - 3600, 'path' => '/']);
    }
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$logged = !empty($_SESSION['admin_logged']);

/* ---------- POST aksiyonları (login gerektirir) ---------- */
$flash = '';

if ($logged && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_appointment') {
        $name  = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $date  = (string)($_POST['date'] ?? '');
        $time  = (string)($_POST['time'] ?? '');
        if (mb_strlen($name) >= 3 && $date && $time) {
            try {
                $pdo->prepare("INSERT INTO appointments (name, phone, appointment_date, appointment_time) VALUES (?,?,?,?)")
                    ->execute([$name, $phone ?: '-', $date, $time . ':00']);
                $flash = 'Randevu eklendi.';
            } catch (PDOException $e) {
                $flash = 'Bu saat dolu.';
            }
        } else {
            $flash = 'Eksik bilgi.';
        }
    }

    if ($action === 'update_appointment') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $date  = (string)($_POST['date'] ?? '');
        $time  = (string)($_POST['time'] ?? '');
        if ($id && mb_strlen($name) >= 3 && $date && $time) {
            try {
                $pdo->prepare("UPDATE appointments SET name=?, phone=?, appointment_date=?, appointment_time=? WHERE id=?")
                    ->execute([$name, $phone ?: '-', $date, $time . ':00', $id]);
                $flash = 'Randevu güncellendi.';
            } catch (PDOException $e) {
                $flash = 'Güncelleme başarısız (çakışma olabilir).';
            }
        }
    }

    if ($action === 'delete_appointment') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM appointments WHERE id=?")->execute([$id]);
            $flash = 'Randevu silindi.';
        }
    }

    if ($action === 'save_settings') {
        $daysAhead    = max(1, min(90, (int)($_POST['days_ahead'] ?? 7)));
        $slotCapacity = max(1, min(50, (int)($_POST['slot_capacity'] ?? 1)));
        $blocked      = array_map('intval', (array)($_POST['blocked'] ?? []));
        $blocked      = array_values(array_filter($blocked, fn($d) => $d >= 1 && $d <= 7));

        foreach ([
            'days_ahead'       => (string)$daysAhead,
            'slot_capacity'    => (string)$slotCapacity,
            'blocked_weekdays' => json_encode($blocked, JSON_UNESCAPED_UNICODE),
        ] as $k => $v) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
                ->execute([$k, $v]);
        }
        $flash = 'Ayarlar kaydedildi.';
    }

    if ($action === 'add_slot') {
        $t = (string)($_POST['slot_time'] ?? '');
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            $maxSort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM time_slots")->fetchColumn();
            try {
                $pdo->prepare("INSERT INTO time_slots (slot_time, is_active, sort_order) VALUES (?, 1, ?)")
                    ->execute([$t . ':00', $maxSort + 1]);
                $flash = 'Saat eklendi.';
            } catch (PDOException $e) {
                $flash = 'Bu saat zaten var.';
            }
        } else {
            $flash = 'Geçersiz saat.';
        }
    }

    if ($action === 'toggle_slot') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE time_slots SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
            $flash = 'Saat durumu güncellendi.';
        }
    }

    if ($action === 'delete_slot') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM time_slots WHERE id=?")->execute([$id]);
            $flash = 'Saat silindi.';
        }
    }
}

/* ---------- Sayfa verileri ---------- */
$page = (string)($_GET['page'] ?? 'dashboard');
$allowedPages = ['dashboard','appointments','slots','settings'];
if (!in_array($page, $allowedPages, true)) $page = 'dashboard';

$settings = [];
foreach ($pdo->query("SELECT setting_key, setting_value FROM settings") as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$daysAhead      = (int)($settings['days_ahead'] ?? 7);
$slotCapacity   = (int)($settings['slot_capacity'] ?? 1);
$blockedWeekdays= json_decode($settings['blocked_weekdays'] ?? '[]', true);
if (!is_array($blockedWeekdays)) $blockedWeekdays = [];

$slotsAll = [];
foreach ($pdo->query("SELECT id, TIME_FORMAT(slot_time,'%H:%i') AS t, is_active, sort_order FROM time_slots ORDER BY sort_order, slot_time") as $r) {
    $slotsAll[] = $r;
}
$slotTimes = array_column($slotsAll, 't');

/* Dashboard istatistikleri */
$stats = [
    'today'    => 0,
    'tomorrow' => 0,
    'week'     => 0,
    'month'    => 0,
    'total'    => 0,
    'upcoming' => 0,
];
$stats['today']    = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();
$stats['tomorrow'] = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
$stats['week']     = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$stats['month']    = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE YEAR(appointment_date)=YEAR(CURDATE()) AND MONTH(appointment_date)=MONTH(CURDATE())")->fetchColumn();
$stats['total']    = (int)$pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$stats['upcoming'] = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date >= CURDATE()")->fetchColumn();

/* Grafik: son 14 gün */
$chartDays = [];
$chartLabels = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDays[$d] = 0;
    $chartLabels[] = date('d.m', strtotime($d));
}
$stmt = $pdo->query("SELECT appointment_date AS d, COUNT(*) c FROM appointments WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY appointment_date");
foreach ($stmt as $r) if (isset($chartDays[$r['d']])) $chartDays[$r['d']] = (int)$r['c'];
$chartValues = array_values($chartDays);

/* Grafik: saatlik dağılım */
$hourly = [];
foreach ($slotTimes as $t) $hourly[$t] = 0;
$stmt = $pdo->query("SELECT TIME_FORMAT(appointment_time,'%H:%i') t, COUNT(*) c FROM appointments GROUP BY appointment_time ORDER BY appointment_time");
foreach ($stmt as $r) if (isset($hourly[$r['t']])) $hourly[$r['t']] = (int)$r['c'];

/* En çok randevu alan müşteriler */
$topCustomers = $pdo->query("SELECT name, phone, COUNT(*) c FROM appointments GROUP BY phone ORDER BY c DESC LIMIT 5")->fetchAll();

/* Randevular listesi (filtreli) */
$where = [];
$params = [];
if ($page === 'appointments') {
    $from   = (string)($_GET['from'] ?? '');
    $to     = (string)($_GET['to'] ?? '');
    $search = trim((string)($_GET['q'] ?? ''));
    $when   = (string)($_GET['when'] ?? 'all');

    if ($from) { $where[] = 'appointment_date >= ?'; $params[] = $from; }
    if ($to)   { $where[] = 'appointment_date <= ?'; $params[] = $to; }
    if ($search !== '') {
        $where[] = '(name LIKE ? OR phone LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    if ($when === 'upcoming') $where[] = 'appointment_date >= CURDATE()';
    if ($when === 'past')     $where[] = 'appointment_date < CURDATE()';
}
$sql = "SELECT * FROM appointments";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY appointment_date DESC, appointment_time DESC LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

/* Düzenlenecek randevu */
$editAppt = null;
if ($page === 'appointments' && !empty($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM appointments WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $editAppt = $st->fetch() ?: null;
}

/* Yaklaşan randevular (dashboard için) */
$upcomingList = $pdo->query("SELECT * FROM appointments WHERE appointment_date >= CURDATE() ORDER BY appointment_date ASC, appointment_time ASC LIMIT 6")->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080808">
    <meta name="robots" content="noindex, nofollow">
    <title>Yönetim · Recep Dündar Saç Tasarım Merkezi</title>
    <link rel="icon" href="../logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        :root{--black:#080808;--black-2:#10100f;--panel:#171715;--panel-2:#1c1c1a;--yellow:#ffc21a;--white:#f7f7f4;--text:#d0d0ca;--muted:#aaa9a2;--line:rgba(255,255,255,.13);--danger:#ff5d5d;--ok:#4ade80;--sans:Arial,Helvetica,sans-serif;--serif:Georgia,'Times New Roman',serif}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--black);color:var(--white);font-family:var(--sans);-webkit-font-smoothing:antialiased;overflow-x:hidden;min-height:100vh}
        a{color:inherit;text-decoration:none}
        img{display:block;max-width:100%}
        button{font-family:inherit}
        .container{width:min(1240px,calc(100% - 48px));margin:0 auto}

        /* ---------- LOGIN ---------- */
        .login-wrap{min-height:100vh;display:grid;place-items:center;padding:40px 20px;background:
            radial-gradient(circle at 30% 20%,rgba(255,194,26,.06),transparent 55%),
            radial-gradient(circle at 80% 90%,rgba(255,194,26,.04),transparent 55%),
            var(--black);}
        .login-card{width:min(420px,100%);border:1px solid var(--line);background:var(--panel);padding:42px 34px;position:relative;overflow:hidden}
        .login-card:before{content:'';position:absolute;top:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,var(--yellow),transparent)}
        .login-brand{display:flex;align-items:center;gap:12px;margin-bottom:32px}
        .login-brand img{width:52px;height:52px;border-radius:50%;object-fit:cover;background:#000;border:1px solid var(--line)}
        .login-brand .b-name{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .login-brand .b-sub{display:block;margin-top:3px;color:var(--yellow);font-family:Georgia,'Times New Roman',serif;font-style:italic;font-weight:400;font-size:11.5px;letter-spacing:.02em;text-transform:none}
        .login-title{margin:0 0 8px;font:400 26px/1.15 var(--serif);letter-spacing:-.02em}
        .login-sub{margin:0 0 26px;color:var(--muted);font-size:12.5px;line-height:1.7}
        .field{margin-bottom:18px}
        .field label{display:block;margin-bottom:9px;color:var(--muted);font-size:10px;letter-spacing:.16em;text-transform:uppercase;font-weight:700}
        .field input,.field select{width:100%;background:rgba(255,255,255,.02);border:1px solid var(--line);color:var(--white);font-family:inherit;font-size:14px;padding:14px 15px;transition:border-color .25s,background .25s;border-radius:0}
        .field input:focus,.field select:focus{outline:none;border-color:var(--yellow);background:rgba(255,194,26,.03)}
        .field input::placeholder{color:#5a5a5a}
        .login-error{display:flex;gap:10px;align-items:center;padding:12px 14px;border:1px solid rgba(255,93,93,.5);background:rgba(255,93,93,.06);color:#ffb2b2;font-size:12.5px;margin-bottom:20px}

        .btn{min-height:44px;display:inline-flex;justify-content:center;align-items:center;gap:9px;padding:0 18px;font-size:12px;font-weight:700;letter-spacing:.04em;cursor:pointer;border-radius:0;transition:background .2s,border-color .2s,color .2s,transform .2s;font-family:inherit;white-space:nowrap}
        .btn-primary{border:1px solid var(--yellow);background:var(--yellow);color:#111}
        .btn-primary:hover{background:#ffd34f;border-color:#ffd34f;transform:translateY(-1px)}
        .btn-ghost{background:transparent;border:1px solid var(--line);color:var(--text)}
        .btn-ghost:hover{color:var(--white);border-color:rgba(255,255,255,.35)}
        .btn-danger{background:transparent;border:1px solid rgba(255,93,93,.4);color:#ffb2b2}
        .btn-danger:hover{background:rgba(255,93,93,.08);border-color:var(--danger)}
        .btn-block{width:100%}
        .btn i{font-size:11px}

        /* ---------- ADMIN HEADER ---------- */
        .admin-header{position:sticky;top:0;z-index:40;background:var(--black);border-bottom:1px solid var(--line);backdrop-filter:blur(6px)}
        .admin-header-inner{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:16px}
        .admin-brand{display:flex;align-items:center;gap:12px;min-width:0}
        .admin-brand img{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#000;border:1px solid var(--line)}
        .admin-brand .b-name{font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
        .admin-brand .b-sub{display:block;margin-top:2px;color:var(--yellow);font-family:Georgia,'Times New Roman',serif;font-style:italic;font-weight:400;font-size:10.5px;letter-spacing:.02em;text-transform:none}
        .admin-header-right{display:flex;align-items:center;gap:10px}
        .admin-user{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border:1px solid var(--line);color:var(--muted);font-size:11px;letter-spacing:.06em;text-transform:uppercase}
        .admin-user i{color:var(--yellow);font-size:12px}
        .admin-logout{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border:1px solid rgba(255,93,93,.35);color:#ffb2b2;background:transparent;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;transition:all .2s}
        .admin-logout:hover{background:rgba(255,93,93,.08);border-color:var(--danger);color:#ff8a8a}

        /* ---------- ADMIN NAV ---------- */
        .admin-nav{background:var(--black-2);border-bottom:1px solid var(--line);position:sticky;top:72px;z-index:30}
        .admin-nav-inner{display:flex;gap:0;overflow-x:auto;scrollbar-width:none}
        .admin-nav-inner::-webkit-scrollbar{display:none}
        .admin-nav a{flex:none;display:inline-flex;align-items:center;gap:9px;padding:16px 20px;color:var(--muted);font-size:12px;letter-spacing:.06em;text-transform:uppercase;font-weight:700;border-bottom:2px solid transparent;transition:color .2s,border-color .2s;position:relative;white-space:nowrap}
        .admin-nav a i{font-size:13px}
        .admin-nav a:hover{color:var(--white)}
        .admin-nav a.active{color:var(--yellow);border-bottom-color:var(--yellow)}

        /* ---------- MAIN ---------- */
        .admin-main{padding:32px 0 60px}
        .page-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:28px}
        .page-title{margin:0 0 6px;font:400 clamp(26px,3.2vw,34px)/1.15 var(--serif);letter-spacing:-.02em}
        .page-title i{color:var(--yellow);font-size:.8em;margin-right:8px}
        .page-sub{margin:0;color:var(--muted);font-size:12.5px;line-height:1.7}
        .flash{margin-bottom:22px;padding:12px 16px;border:1px solid rgba(74,222,128,.4);background:rgba(74,222,128,.06);color:#b5f2cd;font-size:12.5px;display:flex;align-items:center;gap:10px}
        .flash i{color:var(--ok)}

        /* Stat cards */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:24px}
        .stat-card{padding:18px 18px 16px;border:1px solid var(--line);background:var(--panel);position:relative;overflow:hidden}
        .stat-card:before{content:'';position:absolute;top:0;left:0;width:3px;height:100%;background:var(--yellow);opacity:.6}
        .stat-card .sc-label{color:var(--muted);font-size:10px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;margin-bottom:8px}
        .stat-card .sc-value{font:400 30px/1 var(--serif);color:var(--white);letter-spacing:-.01em}
        .stat-card .sc-icon{position:absolute;top:14px;right:14px;color:var(--yellow);opacity:.35;font-size:18px}

        /* Chart / panels */
        .grid-2{display:grid;grid-template-columns:1.6fr 1fr;gap:16px;margin-bottom:24px}
        .panel{border:1px solid var(--line);background:var(--panel);padding:22px}
        .panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--line)}
        .panel-head h3{margin:0;font:400 17px/1.2 var(--serif);letter-spacing:-.01em}
        .panel-head h3 i{color:var(--yellow);font-size:.85em;margin-right:7px}
        .panel-head .ph-meta{color:var(--muted);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase}
        .chart-wrap{position:relative;height:240px;width:100%}

        /* List */
        .list{display:flex;flex-direction:column;gap:0;margin:0;padding:0;list-style:none}
        .list li{display:flex;align-items:center;gap:14px;padding:14px 0;border-bottom:1px solid var(--line);font-size:13px}
        .list li:last-child{border-bottom:0}
        .list .li-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,194,26,.1);color:var(--yellow);display:grid;place-items:center;font-size:11px;font-weight:700;flex:none;letter-spacing:.02em}
        .list .li-main{flex:1;min-width:0}
        .list .li-name{font-size:13px;font-weight:600;color:var(--white);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .list .li-meta{font-size:11px;color:var(--muted);margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .list .li-right{flex:none;text-align:right;font-size:11px;color:var(--muted);letter-spacing:.04em}
        .list .li-right b{display:block;color:var(--yellow);font-size:13px;font-weight:700;margin-bottom:2px}

        /* Filters */
        .filters{display:grid;grid-template-columns:1fr 1fr 1.5fr auto;gap:10px;margin-bottom:22px;align-items:end}
        .filters .field{margin:0}
        .filters .filter-actions{display:flex;gap:8px}

        /* Table */
        .table-wrap{border:1px solid var(--line);background:var(--panel);overflow:hidden}
        .table{width:100%;border-collapse:collapse}
        .table thead{background:rgba(255,255,255,.02)}
        .table th{text-align:left;padding:14px 16px;color:var(--muted);font-size:10px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;border-bottom:1px solid var(--line);white-space:nowrap}
        .table td{padding:14px 16px;font-size:13px;border-bottom:1px solid var(--line);color:var(--text);vertical-align:middle}
        .table tr:last-child td{border-bottom:0}
        .table tr:hover td{background:rgba(255,255,255,.015)}
        .table td strong{color:var(--white);font-weight:600}
        .table td .cell-phone{color:var(--muted);font-size:12px;display:block;margin-top:2px}
        .table td .cell-date{color:var(--white);font-weight:600}
        .table td .cell-time{display:inline-flex;align-items:center;gap:6px;color:var(--yellow);font-weight:700;font-size:13px}
        .table td .cell-time i{font-size:11px}
        .row-actions{display:flex;gap:6px;justify-content:flex-end}
        .icon-btn{width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--line);background:transparent;color:var(--muted);cursor:pointer;transition:all .2s}
        .icon-btn:hover{color:var(--white);border-color:rgba(255,255,255,.35)}
        .icon-btn.danger:hover{color:var(--danger);border-color:var(--danger);background:rgba(255,93,93,.06)}
        .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;font-size:10px;letter-spacing:.06em;font-weight:700;text-transform:uppercase;border:1px solid}
        .badge-warn{border-color:rgba(255,194,26,.45);color:var(--yellow);background:rgba(255,194,26,.05)}
        .badge-muted{border-color:var(--line);color:var(--muted)}
        .badge-ok{border-color:rgba(74,222,128,.4);color:var(--ok);background:rgba(74,222,128,.05)}

        .empty{padding:60px 20px;text-align:center;color:var(--muted);font-size:13px}
        .empty i{font-size:32px;color:var(--line);margin-bottom:14px;display:block}

        /* Slots */
        .slot-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-bottom:22px}
        .slot-card{position:relative;padding:16px 14px;border:1px solid var(--line);background:var(--panel);display:flex;flex-direction:column;gap:10px;transition:all .2s}
        .slot-card:hover{border-color:rgba(255,194,26,.4)}
        .slot-card.is-off{opacity:.45}
        .slot-time{font:400 22px/1 var(--serif);letter-spacing:-.01em}
        .slot-card.is-off .slot-time{text-decoration:line-through;color:var(--muted)}
        .slot-actions{display:flex;gap:6px}
        .slot-actions form{flex:1}
        .slot-btn{flex:1;padding:7px 8px;border:1px solid var(--line);background:transparent;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:all .2s}
        .slot-btn:hover{color:var(--white);border-color:rgba(255,255,255,.35)}
        .slot-btn.on{color:var(--ok);border-color:rgba(74,222,128,.4)}
        .slot-btn.danger:hover{color:var(--danger);border-color:var(--danger)}

        .add-slot{display:flex;gap:10px;align-items:end;max-width:360px}
        .add-slot .field{margin:0;flex:1}

        /* Settings */
        .settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:22px}
        .settings-block{border:1px solid var(--line);background:var(--panel);padding:24px}
        .settings-block h3{margin:0 0 6px;font:400 17px var(--serif)}
        .settings-block p.hint{margin:0 0 20px;color:var(--muted);font-size:12px;line-height:1.7}
        .weekday-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px}
        .weekday-chip{position:relative;display:flex;align-items:center;justify-content:center;padding:12px 8px;border:1px solid var(--line);background:rgba(255,255,255,.015);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;letter-spacing:.02em;user-select:none}
        .weekday-chip input{position:absolute;opacity:0;pointer-events:none}
        .weekday-chip:hover{border-color:rgba(255,194,26,.5)}
        .weekday-chip input:checked ~ span{color:var(--yellow)}
        .weekday-chip:has(input:checked){border-color:var(--yellow);background:rgba(255,194,26,.08)}
        .settings-actions{margin-top:22px;display:flex;justify-content:flex-end}

        /* Modal */
        .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:20px}
        .modal-backdrop.is-open{display:flex;animation:fadeIn .2s ease}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        .modal{width:min(520px,100%);max-height:90vh;overflow-y:auto;background:var(--panel);border:1px solid var(--line);position:relative;animation:slideUp .3s cubic-bezier(.2,.75,.25,1)}
        @keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:none;opacity:1}}
        .modal:before{content:'';position:absolute;top:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,var(--yellow),transparent)}
        .modal-head{display:flex;justify-content:space-between;align-items:center;padding:22px 24px 16px;border-bottom:1px solid var(--line)}
        .modal-head h3{margin:0;font:400 19px var(--serif)}
        .modal-head h3 i{color:var(--yellow);margin-right:8px;font-size:.9em}
        .modal-close{width:36px;height:36px;display:grid;place-items:center;border:1px solid var(--line);background:transparent;color:var(--muted);cursor:pointer;transition:all .2s}
        .modal-close:hover{color:var(--white);border-color:rgba(255,255,255,.35)}
        .modal-body{padding:22px 24px}
        .modal-foot{padding:16px 24px 22px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid var(--line)}
        .row-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}

        /* Footer */
        .admin-footer{padding:20px 0;border-top:1px solid var(--line);color:#8a8a83;font-size:11px;text-align:center}

        @media(max-width:900px){
            .grid-2{grid-template-columns:1fr}
            .settings-grid{grid-template-columns:1fr}
            .filters{grid-template-columns:1fr 1fr}
            .filters .filter-actions{grid-column:span 2}
            .table thead{display:none}
            .table, .table tbody, .table tr, .table td{display:block;width:100%}
            .table tr{border-bottom:1px solid var(--line);padding:12px 0}
            .table tr:last-child{border-bottom:0}
            .table td{padding:6px 16px;border:0}
            .table td:before{content:attr(data-label);display:block;color:var(--muted);font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;margin-bottom:4px}
            .row-actions{justify-content:flex-start;margin-top:8px}
        }

        @media(max-width:640px){
            .container{width:calc(100% - 32px)}
            .admin-header-inner{min-height:64px;gap:10px}
            .admin-brand img{width:38px;height:38px}
            .admin-brand .b-name{font-size:10px}
            .admin-brand .b-sub{font-size:9.5px}
            .admin-user{padding:6px 10px;font-size:9.5px}
            .admin-user span{display:none}
            .admin-logout{padding:7px 11px;font-size:9.5px}
            .admin-nav{top:64px}
            .admin-nav a{padding:14px 14px;font-size:11px}
            .admin-main{padding:22px 0 40px}
            .stats-grid{grid-template-columns:repeat(2,1fr)}
            .stat-card{padding:14px}
            .stat-card .sc-value{font-size:24px}
            .panel{padding:16px}
            .panel-head{flex-direction:column;align-items:flex-start;gap:6px}
            .chart-wrap{height:200px}
            .filters{grid-template-columns:1fr}
            .filters .filter-actions{grid-column:span 1}
            .row-2{grid-template-columns:1fr}
            .modal-body{padding:18px 18px}
            .modal-head{padding:18px 18px 14px}
            .modal-foot{padding:14px 18px 18px}
            .modal-foot .btn{flex:1}
            .weekday-grid{grid-template-columns:repeat(2,1fr)}
            .add-slot{max-width:none}
        }

        @media(prefers-reduced-motion:reduce){
            *{transition:none!important;animation:none!important}
        }
    </style>
</head>
<body>

<?php if (!$logged): ?>
    <!-- ==================== LOGIN ==================== -->
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <img src="../logo.png" alt="Logo" width="52" height="52">
                <span>
                    <span class="b-name">Recep Dündar</span>
                    <span class="b-sub">Saç Tasarım Merkezi</span>
                </span>
            </div>
            <h1 class="login-title">Yönetim Paneli</h1>
            <p class="login-sub">Devam etmek için hesabınıza giriş yapın.</p>

            <?php if ($loginError !== ''): ?>
                <div class="login-error"><i class="fa-solid fa-circle-exclamation"></i><?= h($loginError) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <label for="u">Kullanıcı adı</label>
                    <input type="text" id="u" name="username" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="p">Şifre</label>
                    <input type="password" id="p" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fa-solid fa-right-to-bracket"></i>Giriş yap
                </button>
            </form>
        </div>
    </div>
<?php else: ?>

    <!-- ==================== ADMIN HEADER ==================== -->
    <header class="admin-header">
        <div class="container admin-header-inner">
            <a class="admin-brand" href="index.php">
                <img src="../logo.png" alt="Logo" width="44" height="44">
                <span>
                    <span class="b-name">Recep Dündar</span>
                    <span class="b-sub">Yönetim Paneli</span>
                </span>
            </a>
            <div class="admin-header-right">
                <span class="admin-user"><i class="fa-solid fa-user-shield"></i><span>admin</span></span>
                <form method="post" style="display:inline">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="admin-logout"><i class="fa-solid fa-right-from-bracket"></i>Çıkış</button>
                </form>
            </div>
        </div>
    </header>

    <!-- ==================== NAV ==================== -->
    <nav class="admin-nav">
        <div class="container admin-nav-inner">
            <a href="index.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i>Panel</a>
            <a href="index.php?page=appointments" class="<?= $page === 'appointments' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-check"></i>Randevular</a>
            <a href="index.php?page=slots" class="<?= $page === 'slots' ? 'active' : '' ?>"><i class="fa-solid fa-clock"></i>Saatler</a>
            <a href="index.php?page=settings" class="<?= $page === 'settings' ? 'active' : '' ?>"><i class="fa-solid fa-sliders"></i>Ayarlar</a>
        </div>
    </nav>

    <!-- ==================== MAIN ==================== -->
    <main class="admin-main">
        <div class="container">

            <?php if ($flash !== ''): ?>
                <div class="flash"><i class="fa-solid fa-circle-check"></i><?= h($flash) ?></div>
            <?php endif; ?>

            <?php if ($page === 'dashboard'): ?>

                <div class="page-head">
                    <div>
                        <h1 class="page-title"><i class="fa-solid fa-chart-line"></i>Panel</h1>
                        <p class="page-sub">Randevu sisteminin genel durumu ve son hareketler.</p>
                    </div>
                    <button class="btn btn-primary" data-open-modal="addAppt">
                        <i class="fa-solid fa-plus"></i>Manuel randevu ekle
                    </button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card"><i class="fa-solid fa-sun sc-icon"></i><div class="sc-label">Bugün</div><div class="sc-value"><?= $stats['today'] ?></div></div>
                    <div class="stat-card"><i class="fa-solid fa-cloud-sun sc-icon"></i><div class="sc-label">Yarın</div><div class="sc-value"><?= $stats['tomorrow'] ?></div></div>
                    <div class="stat-card"><i class="fa-solid fa-calendar-week sc-icon"></i><div class="sc-label">7 gün</div><div class="sc-value"><?= $stats['week'] ?></div></div>
                    <div class="stat-card"><i class="fa-solid fa-calendar-days sc-icon"></i><div class="sc-label">Bu ay</div><div class="sc-value"><?= $stats['month'] ?></div></div>
                    <div class="stat-card"><i class="fa-solid fa-hourglass-half sc-icon"></i><div class="sc-label">Yaklaşan</div><div class="sc-value"><?= $stats['upcoming'] ?></div></div>
                    <div class="stat-card"><i class="fa-solid fa-database sc-icon"></i><div class="sc-label">Toplam</div><div class="sc-value"><?= $stats['total'] ?></div></div>
                </div>

                <div class="grid-2">
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fa-solid fa-chart-column"></i>Son 14 gün</h3>
                            <span class="ph-meta">Günlük randevu</span>
                        </div>
                        <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
                    </div>
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fa-solid fa-clock"></i>Saat dağılımı</h3>
                            <span class="ph-meta">Toplam</span>
                        </div>
                        <div class="chart-wrap"><canvas id="chartHourly"></canvas></div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fa-solid fa-calendar-day"></i>Yaklaşan randevular</h3>
                            <a href="index.php?page=appointments&when=upcoming" class="ph-meta" style="color:var(--yellow)">Tümünü gör →</a>
                        </div>
                        <?php if ($upcomingList): ?>
                            <ul class="list">
                                <?php foreach ($upcomingList as $a):
                                    $dt = new DateTime($a['appointment_date']);
                                    $wd = (int)$dt->format('N');
                                    $ini = mb_substr(trim($a['name']), 0, 1);
                                    ?>
                                    <li>
                                        <span class="li-avatar"><?= h(mb_strtoupper($ini)) ?></span>
                                        <div class="li-main">
                                            <div class="li-name"><?= h($a['name']) ?></div>
                                            <div class="li-meta"><?= h($a['phone']) ?> · <?= h($trDays[$wd] . ', ' . $dt->format('j') . ' ' . $trMonths[(int)$dt->format('n')]) ?></div>
                                        </div>
                                        <div class="li-right"><b><?= h(substr($a['appointment_time'], 0, 5)) ?></b></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty" style="padding:24px 0"><i class="fa-regular fa-calendar"></i>Yaklaşan randevu yok.</div>
                        <?php endif; ?>
                    </div>
                    <div class="panel">
                        <div class="panel-head">
                            <h3><i class="fa-solid fa-crown"></i>En çok gelenler</h3>
                            <span class="ph-meta">Top 5</span>
                        </div>
                        <?php if ($topCustomers): ?>
                            <ul class="list">
                                <?php foreach ($topCustomers as $c):
                                    $ini = mb_substr(trim($c['name']), 0, 1);
                                    ?>
                                    <li>
                                        <span class="li-avatar"><?= h(mb_strtoupper($ini)) ?></span>
                                        <div class="li-main">
                                            <div class="li-name"><?= h($c['name']) ?></div>
                                            <div class="li-meta"><?= h($c['phone']) ?></div>
                                        </div>
                                        <div class="li-right"><b><?= (int)$c['c'] ?>×</b></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty" style="padding:24px 0"><i class="fa-regular fa-user"></i>Kayıt yok.</div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($page === 'appointments'): ?>

                <div class="page-head">
                    <div>
                        <h1 class="page-title"><i class="fa-solid fa-calendar-check"></i>Randevular</h1>
                        <p class="page-sub"><?= count($appointments) ?> kayıt listeleniyor. Arama, filtreleme ve düzenleme yapabilirsiniz.</p>
                    </div>
                    <button class="btn btn-primary" data-open-modal="addAppt">
                        <i class="fa-solid fa-plus"></i>Randevu ekle
                    </button>
                </div>

                <form method="get" class="filters">
                    <input type="hidden" name="page" value="appointments">
                    <div class="field">
                        <label for="from">Başlangıç</label>
                        <input type="date" id="from" name="from" value="<?= h((string)($_GET['from'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="to">Bitiş</label>
                        <input type="date" id="to" name="to" value="<?= h((string)($_GET['to'] ?? '')) ?>">
                    </div>
                    <div class="field">
                        <label for="q">Ara</label>
                        <input type="text" id="q" name="q" placeholder="Ad veya telefon" value="<?= h((string)($_GET['q'] ?? '')) ?>">
                    </div>
                    <div class="filter-actions">
                        <input type="hidden" name="when" value="<?= h((string)($_GET['when'] ?? 'all')) ?>">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i>Filtrele</button>
                        <a href="index.php?page=appointments" class="btn btn-ghost"><i class="fa-solid fa-rotate"></i></a>
                    </div>
                </form>

                <?php if (!$appointments): ?>
                    <div class="table-wrap">
                        <div class="empty"><i class="fa-regular fa-calendar-xmark"></i>Kayıt bulunamadı.</div>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Müşteri</th>
                                    <th>Tarih</th>
                                    <th>Saat</th>
                                    <th>Kayıt</th>
                                    <th style="text-align:right">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($appointments as $a):
                                $dt = new DateTime($a['appointment_date']);
                                $wd = (int)$dt->format('N');
                                $past = $a['appointment_date'] < date('Y-m-d');
                                ?>
                                <tr>
                                    <td data-label="Müşteri">
                                        <strong><?= h($a['name']) ?></strong>
                                        <span class="cell-phone"><?= h($a['phone']) ?></span>
                                    </td>
                                    <td data-label="Tarih">
                                        <span class="cell-date"><?= h($dt->format('j') . ' ' . $trMonths[(int)$dt->format('n')] . ' ' . $dt->format('Y')) ?></span>
                                        <span class="cell-phone"><?= h($trDays[$wd]) ?></span>
                                    </td>
                                    <td data-label="Saat">
                                        <span class="cell-time"><i class="fa-regular fa-clock"></i><?= h(substr($a['appointment_time'], 0, 5)) ?></span>
                                        <?php if ($past): ?><span class="badge badge-muted" style="margin-left:6px">Geçmiş</span><?php endif; ?>
                                    </td>
                                    <td data-label="Kayıt">
                                        <span class="cell-phone" style="margin:0"><?= h(date('d.m.Y H:i', strtotime($a['created_at']))) ?></span>
                                    </td>
                                    <td data-label="İşlem">
                                        <div class="row-actions">
                                            <button type="button" class="icon-btn"
                                                data-edit-appt
                                                data-id="<?= (int)$a['id'] ?>"
                                                data-name="<?= h($a['name']) ?>"
                                                data-phone="<?= h($a['phone']) ?>"
                                                data-date="<?= h($a['appointment_date']) ?>"
                                                data-time="<?= h(substr($a['appointment_time'], 0, 5)) ?>"
                                                title="Düzenle"><i class="fa-solid fa-pen"></i></button>
                                            <form method="post" onsubmit="return confirm('Bu randevuyu silmek istediğinize emin misiniz?');" style="display:inline">
                                                <input type="hidden" name="action" value="delete_appointment">
                                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                                <button type="submit" class="icon-btn danger" title="Sil"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php elseif ($page === 'slots'): ?>

                <div class="page-head">
                    <div>
                        <h1 class="page-title"><i class="fa-solid fa-clock"></i>Saatler</h1>
                        <p class="page-sub">Aktif saatler randevu sayfasında gösterilir. Kapalı olan saatler seçilemez.</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom:22px">
                    <div class="panel-head">
                        <h3><i class="fa-solid fa-plus"></i>Yeni saat ekle</h3>
                    </div>
                    <form method="post" class="add-slot">
                        <input type="hidden" name="action" value="add_slot">
                        <div class="field">
                            <label for="slot_time">Saat (HH:MM)</label>
                            <input type="time" id="slot_time" name="slot_time" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i>Ekle</button>
                    </form>
                </div>

                <div class="panel">
                    <div class="panel-head">
                        <h3><i class="fa-solid fa-list"></i>Aktif saatler</h3>
                        <span class="ph-meta"><?= count($slotsAll) ?> kayıt</span>
                    </div>
                    <?php if ($slotsAll): ?>
                        <div class="slot-grid">
                            <?php foreach ($slotsAll as $s): ?>
                                <div class="slot-card <?= $s['is_active'] ? '' : 'is-off' ?>">
                                    <div class="slot-time"><?= h($s['t']) ?></div>
                                    <div class="slot-actions">
                                        <form method="post">
                                            <input type="hidden" name="action" value="toggle_slot">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="slot-btn <?= $s['is_active'] ? 'on' : '' ?>">
                                                <?= $s['is_active'] ? 'Açık' : 'Kapalı' ?>
                                            </button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Bu saati silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete_slot">
                                            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="slot-btn danger"><i class="fa-solid fa-xmark"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty"><i class="fa-regular fa-clock"></i>Henüz saat eklenmedi.</div>
                    <?php endif; ?>
                </div>

            <?php elseif ($page === 'settings'): ?>

                <div class="page-head">
                    <div>
                        <h1 class="page-title"><i class="fa-solid fa-sliders"></i>Ayarlar</h1>
                        <p class="page-sub">Randevu sisteminin genel davranışını buradan yönetebilirsiniz.</p>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="settings-grid">
                        <div class="settings-block">
                            <h3><i class="fa-solid fa-calendar-days" style="color:var(--yellow);margin-right:6px"></i>Randevu aralığı</h3>
                            <p class="hint">Bugünden itibaren kaç gün sonrasına kadar randevu alınabileceğini belirler.</p>
                            <div class="field">
                                <label for="days_ahead">Kaç gün sonrası?</label>
                                <input type="number" id="days_ahead" name="days_ahead" min="1" max="90" value="<?= (int)$daysAhead ?>" required>
                            </div>
                        </div>
                        <div class="settings-block">
                            <h3><i class="fa-solid fa-users" style="color:var(--yellow);margin-right:6px"></i>Saat başı kapasite</h3>
                            <p class="hint">Bir saatte kaç kişiye randevu verilebileceğini belirler.</p>
                            <div class="field">
                                <label for="slot_capacity">Kişi sayısı</label>
                                <input type="number" id="slot_capacity" name="slot_capacity" min="1" max="50" value="<?= (int)$slotCapacity ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="settings-block" style="margin-top:22px">
                        <h3><i class="fa-solid fa-ban" style="color:var(--yellow);margin-right:6px"></i>Kapalı günler</h3>
                        <p class="hint">Seçili hafta günleri randevu sayfasında gösterilmez. Örn. salon Pazar günleri kapalıysa Pazar'ı işaretleyin.</p>
                        <div class="weekday-grid">
                            <?php for ($d = 1; $d <= 7; $d++): ?>
                                <label class="weekday-chip">
                                    <input type="checkbox" name="blocked[]" value="<?= $d ?>" <?= in_array($d, $blockedWeekdays, true) ? 'checked' : '' ?>>
                                    <span><?= h($trDays[$d]) ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i>Ayarları kaydet</button>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </main>

    <!-- ==================== MODAL: Randevu ekle / düzenle ==================== -->
    <div class="modal-backdrop" id="modalAddAppt">
        <div class="modal">
            <div class="modal-head">
                <h3 id="modalTitle"><i class="fa-solid fa-calendar-plus"></i>Randevu ekle</h3>
                <button type="button" class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form method="post" id="apptForm">
                <input type="hidden" name="action" id="apptAction" value="add_appointment">
                <input type="hidden" name="id" id="apptId" value="">
                <div class="modal-body">
                    <div class="field">
                        <label for="a_name">Ad Soyad</label>
                        <input type="text" id="a_name" name="name" required maxlength="120" placeholder="Örn. Ahmet Yılmaz">
                    </div>
                    <div class="field">
                        <label for="a_phone">Telefon</label>
                        <input type="tel" id="a_phone" name="phone" maxlength="20" placeholder="05XX XXX XX XX">
                    </div>
                    <div class="row-2">
                        <div class="field">
                            <label for="a_date">Tarih</label>
                            <input type="date" id="a_date" name="date" required>
                        </div>
                        <div class="field">
                            <label for="a_time">Saat</label>
                            <select id="a_time" name="time" required>
                                <?php foreach ($slotTimes as $t): ?>
                                    <option value="<?= h($t) ?>"><?= h($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn btn-ghost" data-close-modal>İptal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i>Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="admin-footer">
        <div class="container">
            © <?= date('Y') ?> Recep Dündar Saç Tasarım Merkezi · Yönetim
        </div>
    </footer>

    <script>
        /* ---------- Grafikler ---------- */
        <?php if ($page === 'dashboard'): ?>
        (() => {
            const font = "Arial, Helvetica, sans-serif";
            const yellow = '#ffc21a';
            const grid = 'rgba(255,255,255,.08)';
            const tick = '#aaa9a2';

            const dailyCtx = document.getElementById('chartDaily');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                        datasets: [{
                            data: <?= json_encode($chartValues) ?>,
                            backgroundColor: 'rgba(255,194,26,.55)',
                            hoverBackgroundColor: yellow,
                            borderWidth: 0,
                            borderRadius: 2,
                            barPercentage: .7,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#111',
                                borderColor: 'rgba(255,194,26,.4)',
                                borderWidth: 1,
                                titleColor: '#fff',
                                bodyColor: '#ddd',
                                displayColors: false,
                                callbacks: { label: (c) => c.parsed.y + ' randevu' }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: tick, font: { size: 10, family: font } },
                                border: { color: grid }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: grid },
                                ticks: { color: tick, font: { size: 10, family: font }, precision: 0 },
                                border: { display: false }
                            }
                        }
                    }
                });
            }

            const hourlyCtx = document.getElementById('chartHourly');
            if (hourlyCtx) {
                new Chart(hourlyCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode(array_keys($hourly), JSON_UNESCAPED_UNICODE) ?>,
                        datasets: [{
                            data: <?= json_encode(array_values($hourly)) ?>,
                            backgroundColor: [
                                '#ffc21a','#ffd34f','#e6a90c','#c4900b','#a3760a',
                                '#826008','#614704','#ffce4d','#f0b41c','#d99f0e',
                                '#bf8b0d','#a67708','#8c6306'
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: tick,
                                    font: { size: 10, family: font },
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    padding: 8,
                                }
                            },
                            tooltip: {
                                backgroundColor: '#111',
                                borderColor: 'rgba(255,194,26,.4)',
                                borderWidth: 1,
                                callbacks: { label: (c) => c.label + ' — ' + c.parsed + ' randevu' }
                            }
                        }
                    }
                });
            }
        })();
        <?php endif; ?>

        /* ---------- Modal ---------- */
        (() => {
            const modal = document.getElementById('modalAddAppt');
            if (!modal) return;
            const form  = document.getElementById('apptForm');
            const action= document.getElementById('apptAction');
            const idF   = document.getElementById('apptId');
            const nameF = document.getElementById('a_name');
            const phoneF= document.getElementById('a_phone');
            const dateF = document.getElementById('a_date');
            const timeF = document.getElementById('a_time');
            const title = document.getElementById('modalTitle');

            function openModal() { modal.classList.add('is-open'); setTimeout(() => nameF.focus(), 80); }
            function closeModal() { modal.classList.remove('is-open'); }

            document.querySelectorAll('[data-open-modal="addAppt"]').forEach(b => {
                b.addEventListener('click', () => {
                    form.reset();
                    action.value = 'add_appointment';
                    idF.value = '';
                    title.innerHTML = '<i class="fa-solid fa-calendar-plus"></i>Randevu ekle';
                    dateF.value = new Date().toISOString().slice(0, 10);
                    openModal();
                });
            });

            document.querySelectorAll('[data-edit-appt]').forEach(b => {
                b.addEventListener('click', () => {
                    action.value = 'update_appointment';
                    idF.value   = b.dataset.id;
                    nameF.value = b.dataset.name;
                    phoneF.value= b.dataset.phone;
                    dateF.value = b.dataset.date;
                    timeF.value = b.dataset.time;
                    title.innerHTML = '<i class="fa-solid fa-pen"></i>Randevuyu düzenle';
                    openModal();
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
        })();

        /* ---------- URL'den düzenleme tetikleme (appointments?edit=ID) ---------- */
        <?php if ($editAppt): ?>
        (() => {
            const modal = document.getElementById('modalAddAppt');
            if (!modal) return;
            document.getElementById('apptAction').value = 'update_appointment';
            document.getElementById('apptId').value = <?= (int)$editAppt['id'] ?>;
            document.getElementById('a_name').value = <?= json_encode($editAppt['name'], JSON_UNESCAPED_UNICODE) ?>;
            document.getElementById('a_phone').value = <?= json_encode($editAppt['phone'], JSON_UNESCAPED_UNICODE) ?>;
            document.getElementById('a_date').value = <?= json_encode($editAppt['appointment_date']) ?>;
            document.getElementById('a_time').value = <?= json_encode(substr($editAppt['appointment_time'], 0, 5)) ?>;
            document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen"></i>Randevuyu düzenle';
            modal.classList.add('is-open');
        })();
        <?php endif; ?>
    </script>

<?php endif; ?>

</body>
</html>
