<?php

declare(strict_types=1);

$salonName = 'Recep Dündar Saç Tasarım Merkezi';
$phoneText = '0551 692 19 17';
$phone = '+905516921917';
$address = 'Kayalık, 02030 Adıyaman Merkez/Adıyaman';
$bookingUrl = 'randevu/index.php';
$mapUrl = 'https://maps.app.goo.gl/ncCjh3JMye7J3ZQD8';
$whatsAppUrl = 'https://wa.me/905516921917';
$instagramUrl = 'https://www.instagram.com/recepdundar.hairartist/';
$tiktokUrl = 'https://www.tiktok.com/@recepdundar.hairartist';

function icon(string $name): string
{
    $paths = [
        'arrow' => '<path d="M7 17 17 7M7 7h10v10"/>',
        'pin' => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone' => '<path d="M21 16.5v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 1.1 3.8 2 2 0 0 1 3.1 1.6h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L7 9.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h3"/>',
        'chat' => '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H5l-3 3V11.5A7.5 7.5 0 0 1 9.5 4h3a7.5 7.5 0 0 1 7.5 7.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/>',
        'check' => '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>',
        'spark' => '<path d="M12 3v3M5.6 5.6l2.1 2.1M3 12h3M5.6 18.4l2.1-2.1M12 18v3M18.4 18.4l-2.1-2.1M18 12h3M18.4 5.6l-2.1 2.1"/><circle cx="12" cy="12" r="4"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

$businessSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'HairSalon',
    'name' => $salonName,
    'image' => 'https://wsrv.nl/?url=https://i.ibb.co/BF9mSB3/image.png',
    'telephone' => $phone,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Kayalık', 'postalCode' => '02030', 'addressLocality' => 'Adıyaman Merkez', 'addressRegion' => 'Adıyaman', 'addressCountry' => 'TR'],
];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080808">
    <title>Recep Dündar Saç Tasarım Merkezi | Adıyaman</title>
    <meta name="description" content="Recep Dündar Saç Tasarım Merkezi, Adıyaman Merkez Kayalık'ta saç kesimi, sakal düzenleme ve saç şekillendirme hizmetleri sunuyor. Randevunuzu oluşturun.">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:title" content="Recep Dündar Saç Tasarım Merkezi | Adıyaman">
    <meta property="og:description" content="Adıyaman Merkez Kayalık'ta saç kesimi, sakal düzenleme ve saç şekillendirme. Randevunuzu oluşturun.">
    <meta property="og:image" content="https://wsrv.nl/?url=https://i.ibb.co/BF9mSB3/image.png">
    <link rel="icon" href="logo.png" type="image/png">
    <link rel="apple-touch-icon" href="logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script type="application/ld+json"><?= json_encode($businessSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <style>
        :root{--black:#080808;--black-2:#10100f;--panel:#171715;--yellow:#ffc21a;--white:#f7f7f4;--text:#d0d0ca;--muted:#aaa9a2;--line:rgba(255,255,255,.13);--sans:Arial,Helvetica,sans-serif;--serif:Georgia,'Times New Roman',serif}
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
        .brand-copy span{
            color:var(--yellow);
            font-family:Georgia,'Times New Roman',serif;
            font-style:italic;
            font-weight:400;
            font-size:12.5px;
            letter-spacing:.02em;
            line-height:1.15;
            text-transform:none;
        }

        .nav{display:flex;align-items:center;gap:28px;color:#d5d5d0;font-size:13px}
        .nav>a:not(.button){position:relative;padding:4px 0}
        .nav>a:not(.button):after{content:'';position:absolute;left:0;bottom:0;width:0;height:1px;background:var(--yellow);transition:width .28s ease}
        .nav>a:not(.button):hover{color:var(--yellow)}
        .nav>a:not(.button):hover:after{width:100%}
        .plain-link:hover{color:var(--yellow)}

        /* Sosyal medya ikonları (header) */
        .header-social{display:flex;align-items:center;gap:14px;margin-left:8px;padding-left:18px;border-left:1px solid var(--line)}
        .header-social a{color:#c4c4bd;font-size:16px;transition:color .25s,transform .25s;display:inline-flex}
        .header-social a:hover{color:var(--yellow);transform:translateY(-2px)}

        .button,.button-secondary{min-height:48px;display:inline-flex;justify-content:center;align-items:center;gap:10px;padding:0 20px;font-size:12px;font-weight:700;transition:background .18s,border-color .18s,color .18s,transform .2s;position:relative;overflow:hidden}
        .button{border:1px solid var(--yellow);background:var(--yellow);color:#111}
        .button:hover{background:#ffd34f;border-color:#ffd34f;transform:translateY(-1px)}
        .button-secondary{border:1px solid rgba(255,255,255,.48);color:var(--white)}
        .button-secondary:hover{border-color:var(--yellow);color:var(--yellow);transform:translateY(-1px)}
        .button .icon{width:16px;height:16px;flex-basis:16px;transition:transform .25s}
        .button:hover .icon{transform:translateX(3px)}

        .hero{padding:36px 0 0;position:relative;overflow:hidden}
        .hero-particles{position:absolute;inset:0;pointer-events:none;z-index:0}
        .hero-particles span{position:absolute;width:3px;height:3px;border-radius:50%;background:var(--yellow);box-shadow:0 0 8px rgba(255,194,26,.7);animation:float linear infinite}
        @keyframes float{
            0%,100%{transform:translateY(0) scale(.5);opacity:0}
            15%{opacity:.85}
            85%{opacity:.85}
            50%{transform:translateY(-70px) scale(1.15);opacity:.9}
        }
        .hero-layout{display:grid;grid-template-columns:.88fr 1.12fr;align-items:stretch;min-height:480px;position:relative;z-index:1}
        .hero-copy{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:50px 42px 50px 0}
        .overline,.section-kicker{margin:0 0 15px;color:var(--yellow);font-size:10px;font-weight:700;letter-spacing:.17em;text-transform:uppercase}
        .hero h1{margin:0;font:400 clamp(40px,4.7vw,59px)/1.08 var(--serif);letter-spacing:-.035em}
        .hero h1 small{display:block;margin-bottom:9px;color:var(--yellow);font:700 12px/1.4 var(--sans);letter-spacing:.16em;text-transform:uppercase}
        .tagline{margin:18px 0 10px;font-size:16px;font-weight:600;line-height:1.55}
        .hero-description{max-width:440px;margin:0;color:var(--text);font-size:14px;line-height:1.8}
        .appointment-note{display:flex;align-items:center;gap:8px;margin:16px 0 22px;color:var(--muted);font-size:12px}
        .appointment-note .icon{color:var(--yellow)}
        .hero-actions{display:flex;flex-wrap:wrap;gap:10px}
        .hero-image{position:relative;min-height:480px;overflow:hidden;background:#22211c}
        .hero-image img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;transition:transform 1.2s cubic-bezier(.2,.75,.25,1)}
        .hero-image:hover img{transform:scale(1.04)}
        .hero-image:after{content:'';position:absolute;inset:0;background:linear-gradient(0deg,rgba(0,0,0,.45),transparent 44%);pointer-events:none}
        .image-label{position:absolute;z-index:1;right:18px;bottom:17px;display:flex;align-items:center;gap:8px;padding:9px 12px;background:rgba(0,0,0,.72);font-size:10px;letter-spacing:.08em;text-transform:uppercase}
        .image-label .icon{color:var(--yellow);width:15px;height:15px;flex-basis:15px}
        .quick-info{display:grid;grid-template-columns:1fr 1fr;gap:20px;padding:18px 0;border-bottom:1px solid var(--line);color:var(--muted);font-size:12px}
        .quick-info p{display:flex;align-items:center;gap:9px;margin:0}
        .quick-info .icon{color:var(--yellow)}
        .quick-info strong{color:var(--white);font-weight:500}

        .section{padding:78px 0}
        .section-dark{background:var(--black-2)}
        .section-heading{max-width:720px;margin-bottom:32px}
        .section-kicker{margin-bottom:12px}
        .section-heading>p:last-child{max-width:660px;margin:15px 0 0;color:var(--text);font-size:14px;line-height:1.85}
        h2{margin:0;font:400 clamp(34px,4vw,48px)/1.14 var(--serif);letter-spacing:-.025em}

        .story{display:grid;grid-template-columns:.85fr 1.15fr;align-items:start;gap:72px}
        .story h2{max-width:430px}
        .story-copy,.about-content{color:var(--text);font-size:14px;line-height:1.9}
        .story-copy p,.about-content p{margin:0 0 17px}
        .story-copy p:last-of-type,.about-content p:last-of-type{margin-bottom:22px}
        .story-actions{display:flex;flex-wrap:wrap;gap:10px}

        .salon-band{padding:0 0 78px}
        .salon-card{display:grid;grid-template-columns:1fr 1fr;align-items:stretch;border:1px solid var(--line);background:var(--panel)}
        .salon-photo{position:relative;min-height:420px;overflow:hidden;background:#211f19}
        .salon-photo img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;transition:transform 1s cubic-bezier(.2,.75,.25,1)}
        .salon-card:hover .salon-photo img{transform:scale(1.05)}
        .salon-copy{display:flex;flex-direction:column;align-items:flex-start;justify-content:center;padding:34px 40px}
        .salon-copy h2{font-size:32px}
        .salon-location{margin:10px 0 14px;color:var(--yellow);font-size:12px;font-weight:600}
        .salon-copy>p:not(.section-kicker):not(.salon-location){max-width:490px;margin:0 0 18px;color:var(--text);font-size:13px;line-height:1.8}
        .address-line{margin-bottom:18px;font-size:13px;line-height:1.7}
        .address-line b{color:var(--yellow)}

        /* Hizmet kartları */
        .services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        .service-card{min-height:220px;padding:22px;border:1px solid var(--line);background:rgba(255,255,255,.018);transition:border-color .3s,background .3s,transform .35s cubic-bezier(.2,.75,.25,1);position:relative;overflow:hidden}
        .service-card:before{content:'';position:absolute;top:0;left:0;width:0;height:2px;background:var(--yellow);transition:width .4s ease}
        .service-card:hover{border-color:rgba(255,194,26,.42);background:rgba(255,194,26,.03);transform:translateY(-4px)}
        .service-card:hover:before{width:100%}
        .service-icon{width:44px;height:44px;display:grid;place-items:center;margin-bottom:20px;border:1px solid rgba(255,194,26,.45);color:var(--yellow);transition:background .3s,color .3s,border-color .3s}
        .service-icon i{font-size:19px;line-height:1}
        .service-card:hover .service-icon{background:var(--yellow);color:#111;border-color:var(--yellow)}
        .service-card h3{margin:0 0 10px;font:400 21px var(--serif)}
        .service-card p{margin:0;color:var(--text);font-size:13px;line-height:1.8}

        .why-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:0 38px;border-top:1px solid var(--line)}
        .why-item{display:grid;grid-template-columns:34px 1fr;gap:14px;padding:22px 0;border-bottom:1px solid var(--line);transition:padding-left .3s}
        .why-item:hover{padding-left:6px}
        .why-item>.icon{color:var(--yellow);transition:transform .3s}
        .why-item:hover>.icon{transform:rotate(-6deg) scale(1.1)}
        .why-item h3{margin:0 0 8px;font-size:15px}
        .why-item p{margin:0;color:var(--text);font-size:13px;line-height:1.75}

        /* Hakkımızda — arka plan görseli (dikey fotoğraf) — SOLDA GENİŞ ve üstten */
        .about-section{position:relative;overflow:hidden;min-height:520px}
        .about-deco{
            position:absolute;
            top:0;
            left:0;
            width:min(58%,820px);
            height:100%;
            pointer-events:none;
            user-select:none;
            z-index:0;
            opacity:.22;
            overflow:hidden;
            -webkit-mask-image:linear-gradient(90deg,#000 0%,#000 55%,transparent 100%), linear-gradient(180deg,#000 0%,#000 70%,transparent 100%);
            -webkit-mask-composite:source-in;
            mask-image:linear-gradient(90deg,#000 0%,#000 55%,transparent 100%), linear-gradient(180deg,#000 0%,#000 70%,transparent 100%);
            mask-composite:intersect;
        }
        .about-deco img{
            width:100%;
            height:100%;
            object-fit:cover;
            object-position:top center;
            display:block;
            animation:aboutFloat 14s ease-in-out infinite;
        }
        @keyframes aboutFloat{
            0%,100%{transform:translateY(0) scale(1)}
            50%{transform:translateY(-10px) scale(1.02)}
        }
        .about-section .container{position:relative;z-index:1}
        .about-content{max-width:760px;position:relative;margin-left:auto}

        .contact{background:var(--black-2)}
        .contact-intro{max-width:650px;margin:14px 0 30px;color:var(--text);font-size:14px;line-height:1.85}
        .contact-grid{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
        .contact-card{min-height:157px;padding:19px 16px 20px 0;border-right:1px solid var(--line);transition:background .3s}
        .contact-card+.contact-card{padding-left:16px}
        .contact-card:last-child{border-right:0}
        .contact-card:hover{background:rgba(255,194,26,.035)}
        .contact-card>.icon{margin-bottom:10px;color:var(--yellow);transition:transform .3s}
        .contact-card:hover>.icon{transform:translateY(-3px)}
        .contact-card h3{margin:0 0 7px;color:var(--muted);font-size:10px;letter-spacing:.14em;text-transform:uppercase}
        .contact-card strong,.contact-card .contact-value{display:block;font-size:13px;font-weight:600;line-height:1.7}
        .plain-link{display:inline-flex;align-items:center;gap:6px;margin-top:7px;color:#c4c4bd;font-size:11px}
        .plain-link .icon{width:13px;height:13px;flex-basis:13px}
        .booking-panel{display:flex;justify-content:space-between;align-items:center;gap:25px;margin-top:32px;padding:23px 26px;border:1px solid rgba(255,194,26,.38);background:rgba(255,194,26,.045);position:relative;overflow:hidden}
        .booking-panel:before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,194,26,.06),transparent);transition:left .8s}
        .booking-panel:hover:before{left:100%}
        .booking-panel h3{margin:0 0 6px;font:400 23px var(--serif)}
        .booking-panel p{margin:0;color:var(--text);font-size:13px;line-height:1.7}
        .booking-panel .button{flex:none}
        .footer{padding:20px 0;border-top:1px solid var(--line);color:#92928c;font-size:11px}
        .footer .container{display:flex;justify-content:space-between;gap:15px}
        .footer-social{display:flex;gap:14px}
        .footer-social a{color:#92928c;font-size:15px;transition:color .25s,transform .25s;display:inline-flex}
        .footer-social a:hover{color:var(--yellow);transform:translateY(-2px)}

        .mobile-actions,.menu-toggle,.mobile-menu{display:none}

        /* Reveal animation */
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
            .mobile-booking{min-height:42px;padding:0 15px;font-size:10px;letter-spacing:.06em}
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
            .menu-orbit:before,.menu-orbit:after{content:'';position:absolute;border:1px solid rgba(255,194,26,.08);border-radius:50%}
            .menu-orbit:before{inset:9%}
            .menu-orbit:after{inset:19%}
            body.menu-open{overflow:hidden}

            .hero{padding-top:20px}
            .hero-layout{grid-template-columns:1fr}
            .hero-copy{padding:32px 0 27px}
            .hero h1{font-size:clamp(43px,10vw,60px)}
            .hero-image{min-height:340px}
            .story{grid-template-columns:1fr;gap:22px}
            .salon-band{padding-bottom:60px}
            .salon-card{grid-template-columns:1fr}
            .salon-photo{min-height:280px;max-height:380px}
            .salon-copy{padding:26px}
            .services-grid{grid-template-columns:1fr;gap:10px}
            .service-card{min-height:0;padding:20px}
            .service-icon{margin-bottom:14px}
            .contact-grid{grid-template-columns:repeat(2,1fr)}
            .contact-card{min-height:140px;padding:16px 13px 16px 0;border-bottom:1px solid var(--line)}
            .contact-card+.contact-card{padding-left:13px}
            .contact-card:nth-child(2){border-right:0}
            .contact-card:nth-child(3),.contact-card:nth-child(4){border-bottom:0}
            .contact-card:nth-child(3){padding-left:0}
            .section{padding:61px 0}

            .about-section{min-height:0}
            .about-deco{width:75%;opacity:.16;left:-8%}
            .about-content{margin-left:0}
        }

        @media(max-width:520px){
            .container{width:calc(100% - 32px)}
            .header-inner{min-height:69px;gap:8px}
            .brand{gap:8px}
            .brand img{width:45px;height:45px}
            .brand-copy{gap:3px}
            .brand-copy strong{font-size:9px;letter-spacing:.02em}
            .brand-copy span{font-size:10px;letter-spacing:.01em}
            .mobile-actions{gap:7px}
            .mobile-booking{min-height:40px;padding:0 11px;font-size:9px}
            .menu-toggle{width:41px;height:41px}
            .mobile-menu{padding-left:21px;padding-right:21px}
            .menu-brand img{width:44px;height:44px}
            .menu-body{padding:12px 0}
            .mobile-nav a{padding:12px 0;font-size:clamp(31px,9vw,45px)}

            .hero{padding-top:7px}
            .hero-copy{padding:27px 0 22px}
            .overline{margin-bottom:12px;font-size:9px}
            .hero h1{font-size:clamp(39px,11vw,52px)}
            .hero h1 small{font-size:10px}
            .tagline{margin:12px 0 8px;font-size:15px}
            .hero-description{font-size:13px;line-height:1.75}
            .appointment-note{margin:11px 0 17px;font-size:11px}
            .hero-actions,.story-actions{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:8px}
            .button,.button-secondary{min-height:45px;padding:0 9px;font-size:10px}
            .hero-image{min-height:230px}
            .image-label{right:9px;bottom:9px;padding:7px 9px;font-size:9px}
            .quick-info{grid-template-columns:1fr;gap:10px;padding:15px 0;font-size:11px}
            .section{padding:52px 0}
            .section-heading{margin-bottom:24px}
            .section-kicker{margin-bottom:9px;font-size:9px}
            .section-heading>p:last-child,.story-copy,.about-content{font-size:13px}
            h2{font-size:clamp(30px,8.5vw,38px)}
            .story{gap:17px}
            .story-copy{line-height:1.82}
            .salon-band{padding-bottom:52px}
            .salon-photo{min-height:230px;max-height:300px}
            .salon-copy{padding:21px 18px 23px}
            .salon-copy h2{font-size:27px}
            .salon-copy>p:not(.section-kicker):not(.salon-location){font-size:12px}
            .service-card{padding:18px}
            .service-card h3{font-size:20px}
            .service-card p,.why-item p{font-size:12px;line-height:1.75}
            .why-grid{grid-template-columns:1fr}
            .why-item{grid-template-columns:28px 1fr;gap:11px;padding:18px 0}
            .why-item h3{font-size:14px}
            .about-content{line-height:1.82}
            .about-deco{width:95%;opacity:.11;left:-22%}
            .contact-intro{margin:12px 0 23px;font-size:13px}
            .contact-grid{grid-template-columns:1fr}
            .contact-card,.contact-card+.contact-card,.contact-card:nth-child(3){min-height:0;padding:15px 0;border-right:0;border-bottom:1px solid var(--line)}
            .contact-card:last-child{border-bottom:0}
            .contact-card>.icon{float:right}
            .contact-card h3{margin-bottom:5px}
            .booking-panel{align-items:stretch;flex-direction:column;gap:16px;margin-top:23px;padding:18px}
            .booking-panel h3{font-size:21px}
            .booking-panel p{font-size:12px}
            .booking-panel .button{width:100%}
            .footer .container{flex-direction:column;gap:7px}
        }

        @media(prefers-reduced-motion:reduce){
            html{scroll-behavior:auto}
            .mobile-menu,.mobile-nav a,.menu-toggle span,.reveal{transition:none!important;animation:none!important}
            .reveal{opacity:1;transform:none}
            .hero-particles{display:none}
            .about-deco img{animation:none}
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-inner">
            <a class="brand" href="/" aria-label="Recep Dündar Saç Tasarım Merkezi ana sayfa">
                <img src="logo.png" alt="Recep Dündar Saç Tasarım Merkezi logosu" width="62" height="62">
                <span class="brand-copy">
                    <strong>Recep Dündar</strong>
                    <span>Saç Tasarım Merkezi</span>
                </span>
            </a>
            <nav class="nav" aria-label="Ana menü">
                <a href="#hizmetler">Hizmetler</a>
                <a href="#salon">Salonumuz</a>
                <a href="#iletisim">İletişim</a>
                <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al<?= icon('arrow') ?></a>
                <span class="header-social">
                    <a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </span>
            </nav>
            <div class="mobile-actions">
                <a class="button mobile-booking" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al</a>
                <button class="menu-toggle" type="button" aria-label="Menüyü aç" aria-expanded="false" aria-controls="mobile-menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu" role="dialog" aria-modal="true" aria-label="Ana menü" aria-hidden="true" inert>
        <div class="menu-top">
            <a class="menu-brand" href="/" aria-label="Ana sayfa">
                <img src="logo.png" alt="" width="50" height="50">
                <span>Recep Dündar<small>Saç Tasarım Merkezi</small></span>
            </a>
            <button class="menu-close" type="button" aria-label="Menüyü kapat">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="menu-body">
            <nav class="mobile-nav" aria-label="Mobil menü">
                <a href="#hizmetler"><span>01</span>Hizmetler</a>
                <a href="#salon"><span>02</span>Salonumuz</a>
                <a href="#hakkimizda"><span>03</span>Hakkımızda</a>
                <a href="#iletisim"><span>04</span>İletişim</a>
            </nav>
            <div class="menu-social">
                <a href="<?= htmlspecialchars($instagramUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                <a href="<?= htmlspecialchars($tiktokUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
            </div>
        </div>
        <div class="menu-orbit" aria-hidden="true"></div>
    </div>

    <main>
        <section class="hero" aria-labelledby="main-title">
            <div class="hero-particles" aria-hidden="true"></div>
            <div class="container">
                <div class="hero-layout">
                    <div class="hero-copy">
                        <h1 id="main-title"><small>Recep Dündar</small>Saç Tasarım<br>Merkezi</h1>
                        <p class="hero-description">Saçınızın yapısını, yüz hatlarınızı ve günlük kullanımınızı dikkate alarak size yakışan modeli birlikte belirliyoruz.</p>
                        <p class="appointment-note"><?= icon('calendar') ?>Randevulu hizmet</p>
                        <div class="hero-actions">
                            <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al<?= icon('arrow') ?></a>
                            <a class="button-secondary" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"><?= icon('phone') ?>Telefon et</a>
                        </div>
                    </div>
                    <div class="hero-image">
                        <img src="https://wsrv.nl/?url=https://i.ibb.co/BF9mSB3/image.png" alt="Recep Dündar Saç Tasarım Merkezi'nin dış görünüşü" width="1000" height="760" fetchpriority="high">
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-dark" aria-labelledby="cut-title">
            <div class="container story">
                <div data-reveal><h2 id="cut-title">Saç kesimi,<br>size yakışan modelle başlar.</h2></div>
                <div class="story-copy" data-reveal>
                    <p>Herkesin saç yapısı ve yüz şekli farklıdır. Bu nedenle hazır bir modeli olduğu gibi uygulamak yerine, istediğiniz görünümü konuşarak saçınıza ve tarzınıza uygun hale getiriyoruz.</p>
                    <p>İster klasik ve düzenli, ister daha modern ve dikkat çekici bir model tercih edin; kesim öncesinde beklentinizi dinliyor ve birlikte karar veriyoruz.</p>
                    <p><strong>Randevunuzu oluşturun, size ayıracağımız zamanı planlayalım.</strong></p>
                    <div class="story-actions">
                        <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al<?= icon('arrow') ?></a>
                        <a class="button-secondary" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"><?= icon('phone') ?>Telefon et</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="salon-band" id="salon">
            <div class="container salon-card" data-reveal>
                <div class="salon-copy">
                    <p class="section-kicker">Salonumuz</p>
                    <h2>Recep Dündar<br>Saç Tasarım Merkezi</h2>
                    <p>Saç kesimi ve kişisel bakım konusunda özenli, rahat ve samimi bir salon deneyimi sunuyoruz. Salonumuza geldiğinizde önceliğimiz, istediğiniz modeli doğru anlamak ve saçınızın yapısına uygun şekilde uygulamak.</p>
                    <div class="address-line"><b>Adres:</b> <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></div>
                    <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al<?= icon('arrow') ?></a>
                </div>
                <div class="salon-photo">
                    <img src="https://i.ibb.co/HLvp1Xrh/image.png" alt="Berber saç kesimi yaparken" width="900" height="760" loading="lazy">
                </div>
            </div>
        </section>

        <section class="section section-dark" id="hizmetler">
            <div class="container">
                <div class="section-heading" data-reveal>
                    <p class="section-kicker">Hizmetler</p>
                    <h2>Saç ve sakal bakımı.</h2>
                </div>
                <div class="services-grid">
                    <article class="service-card" data-reveal>
                        <div class="service-icon"><i class="fa-solid fa-scissors"></i></div>
                        <h3>Saç kesimi</h3>
                        <p>İstediğiniz modeli birlikte değerlendirelim. Saçınızın yapısını ve yüz hatlarınızı göz önünde bulundurarak günlük kullanımda da rahat edeceğiniz bir kesim uygulayalım.</p>
                    </article>
                    <article class="service-card" data-reveal>
                        <div class="service-icon"><i class="fa-solid fa-user-tie"></i></div>
                        <h3>Sakal düzenleme</h3>
                        <p>Sakalınızı yüz hatlarınıza ve saç kesiminize uyumlu şekilde düzenleyerek daha temiz ve dengeli bir görünüm elde etmenize yardımcı oluyoruz.</p>
                    </article>
                    <article class="service-card" data-reveal>
                        <div class="service-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                        <h3>Saç şekillendirme</h3>
                        <p>Kesimin son dokunuşunu saçınıza uygun şekillendirmeyle tamamlayalım. Günlük kullanımınıza ve tercih ettiğiniz görünüme göre saçınızı şekillendirelim.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" aria-labelledby="why-title">
            <div class="container">
                <div class="section-heading" data-reveal>
                    <p class="section-kicker">Salonumuzda</p>
                    <h2 id="why-title">Neden bizi tercih ediyorlar?</h2>
                </div>
                <div class="why-grid">
                    <article class="why-item" data-reveal><?= icon('chat') ?><div><h3>Önce sizi dinliyoruz.</h3><p>Nasıl bir model istediğinizi anlamadan kesime başlamıyoruz. Beklentiniz bizim için önemli.</p></div></article>
                    <article class="why-item" data-reveal><?= icon('check') ?><div><h3>Saçınıza göre kesim.</h3><p>Her saç aynı şekilde kesilmez. Saçınızın doğal yapısını ve kullanım alışkanlıklarınızı dikkate alıyoruz.</p></div></article>
                    <article class="why-item" data-reveal><?= icon('spark') ?><div><h3>Özenli ve temiz görünüm.</h3><p>Kesimin yanı sıra saç ve sakalın birbiriyle uyumuna dikkat ederek daha bütünlüklü bir görünüm hedefliyoruz.</p></div></article>
                    <article class="why-item" data-reveal><?= icon('calendar') ?><div><h3>Randevulu hizmet.</h3><p>Size ayrılan zamanı önceden planlayarak daha rahat ve kişisel bir salon deneyimi sunuyoruz.</p></div></article>
                </div>
            </div>
        </section>

        <section class="section section-dark about-section" id="hakkimizda">
            <div class="about-deco" aria-hidden="true">
                <img src="https://i.ibb.co/7dpyW9rZ/image.png" alt="" loading="lazy">
            </div>
            <div class="container">
                <div class="section-heading" data-reveal>
                    <p class="section-kicker">Hakkımızda</p>
                    <h2>İyi bir kesim,<br>doğru iletişimle başlar.</h2>
                </div>
                <div class="about-content" data-reveal>
                    <p>Recep Dündar Saç Tasarım Merkezi olarak Adıyaman Merkez Kayalık bölgesinde saç kesimi, sakal düzenleme ve saç şekillendirme hizmetleri sunuyoruz.</p>
                    <p>Bizim için iyi bir saç kesimi yalnızca makas ve makine kullanmaktan ibaret değil. Öncelikle nasıl bir görünüm istediğinizi anlamaya, ardından saçınızın yapısını ve yüz hatlarınızı değerlendirerek size uygun bir model oluşturmaya önem veriyoruz.</p>
                    <p>Bu nedenle her müşterinin istediği modeli dinliyor, kesim öncesinde detayları konuşuyor ve mümkün olduğunca kişiye özel bir sonuç ortaya çıkarmaya çalışıyoruz.</p>
                    <p><strong>Randevunuzu oluşturmak veya hizmetler hakkında bilgi almak için bizimle iletişime geçebilirsiniz.</strong></p>
                    <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu al<?= icon('arrow') ?></a>
                </div>
            </div>
        </section>

        <section class="section contact" id="iletisim">
            <div class="container">
                <div class="section-heading" data-reveal>
                    <p class="section-kicker">İletişim ve konum</p>
                    <h2>Adıyaman'da<br>görüşmek üzere.</h2>
                    <p>Salonumuz <strong>Kayalık, Adıyaman Merkez'de</strong> hizmet vermektedir. Randevu oluşturmak, uygun saatleri öğrenmek veya hizmetler hakkında bilgi almak için bizi telefon ya da WhatsApp üzerinden arayabilirsiniz.</p>
                </div>
                <div class="contact-grid">
                    <div class="contact-card" data-reveal>
                        <?= icon('pin') ?>
                        <h3>Adres</h3>
                        <strong><?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?></strong>
                        <a class="plain-link" href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Yol tarifi al<?= icon('arrow') ?></a>
                    </div>
                    <div class="contact-card" data-reveal>
                        <?= icon('phone') ?>
                        <h3>Telefon</h3>
                        <a class="contact-value" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($phoneText, ENT_QUOTES, 'UTF-8') ?></a>
                        <a class="plain-link" href="tel:<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">Hemen ara<?= icon('arrow') ?></a>
                    </div>
                    <div class="contact-card" data-reveal>
                        <?= icon('chat') ?>
                        <h3>WhatsApp</h3>
                        <strong class="contact-value"><?= htmlspecialchars($phoneText, ENT_QUOTES, 'UTF-8') ?></strong>
                        <a class="plain-link" href="<?= htmlspecialchars($whatsAppUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">WhatsApp'tan yazın<?= icon('arrow') ?></a>
                    </div>
                    <div class="contact-card" data-reveal>
                        <?= icon('calendar') ?>
                        <h3>Randevu</h3>
                        <strong>Size uygun gün ve saati birlikte planlayalım.</strong>
                        <a class="plain-link" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu sayfasına git<?= icon('arrow') ?></a>
                    </div>
                </div>
                <div class="booking-panel" data-reveal>
                    <div>
                        <h3>Hazırsanız başlayalım.</h3>
                        <p>Saçınız için ayıracağımız zamanı planlayalım. İstediğiniz modeli konuşalım, saçınıza uygun kesimi birlikte belirleyelim.</p>
                    </div>
                    <a class="button" href="<?= htmlspecialchars($bookingUrl, ENT_QUOTES, 'UTF-8') ?>">Randevu oluştur<?= icon('arrow') ?></a>
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
        /* ---------- Hero partiküller ---------- */
        (() => {
            const wrap = document.querySelector('.hero-particles');
            if (!wrap) return;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduced) return;
            const count = window.innerWidth < 700 ? 12 : 22;
            for (let i = 0; i < count; i++) {
                const s = document.createElement('span');
                s.style.left = Math.random() * 100 + '%';
                s.style.top = (40 + Math.random() * 60) + '%';
                s.style.animationDuration = (7 + Math.random() * 7).toFixed(2) + 's';
                s.style.animationDelay = (-Math.random() * 10).toFixed(2) + 's';
                const size = (2 + Math.random() * 2.5).toFixed(2);
                s.style.width = size + 'px';
                s.style.height = size + 'px';
                wrap.appendChild(s);
            }
        })();

        /* ---------- Scroll reveal ---------- */
        (() => {
            const items = document.querySelectorAll('[data-reveal]');
            if (!items.length) return;
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduced) { items.forEach(el => el.classList.add('is-visible')); return; }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

            items.forEach((el) => {
                el.classList.add('reveal');
                observer.observe(el);
            });

            document.querySelectorAll('.services-grid, .why-grid, .contact-grid').forEach((grid) => {
                const kids = grid.querySelectorAll('[data-reveal]');
                kids.forEach((el, i) => {
                    el.style.transitionDelay = (i * 90) + 'ms';
                });
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

            overlay.querySelectorAll('.mobile-nav a').forEach((link) => {
                link.addEventListener('click', () => setMenu(false));
            });

            document.addEventListener('keydown', (event) => {
                if (toggle.getAttribute('aria-expanded') !== 'true') return;
                if (event.key === 'Escape') { setMenu(false); return; }
                if (event.key === 'Tab') {
                    const items = focusables();
                    if (!items.length) return;
                    if (event.shiftKey && document.activeElement === items[0]) {
                        event.preventDefault();
                        items[items.length - 1].focus();
                    } else if (!event.shiftKey && document.activeElement === items[items.length - 1]) {
                        event.preventDefault();
                        items[0].focus();
                    }
                }
            });
        })();
    </script>
</body>
</html>
