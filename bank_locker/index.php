<?php 
require_once __DIR__ . '/includes/config.php';
session_start(); 
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Secure your valuables with our state-of-the-art bank locker facility. Multiple locker sizes available with advanced security features.">
<title>Safe Deposit Lockers – Keep Your Valuables Safe | Bank Locker Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --primary:   #004c8f;
    --primary-d: #003566;
    --accent:    #e4232b;
    --accent-h:  #c81a21;
    --gold:      #c8a84b;
    --light-bg:  #f0f4f9;
    --text:      #1a1a2e;
    --text-m:    #4a5568;
    --border:    #dce3ee;
    --white:     #ffffff;
    --nav-h:     68px;
  }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Open Sans', sans-serif;
    color: var(--text);
    background: var(--white);
    overflow-x: hidden;
  }

  /* ─── TOP UTILITY BAR ─── */
  .top-bar {
    background: var(--primary-d);
    color: rgba(255,255,255,.75);
    font-size: 12px;
    padding: 6px 0;
  }
  .top-bar-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .top-bar a { color: rgba(255,255,255,.75); text-decoration: none; }
  .top-bar a:hover { color: #fff; }
  .top-bar-right { display: flex; gap: 20px; }
  .top-bar-right span { display: flex; align-items: center; gap: 5px; }

  /* ─── NAVBAR ─── */
  .navbar {
    background: var(--white);
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    position: sticky;
    top: 0;
    z-index: 1000;
    height: var(--nav-h);
  }
  .nav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 24px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }
  .nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    flex-shrink: 0;
  }
  .nav-logo-icon {
    width: 42px; height: 42px;
    background: var(--primary);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
  }
  .nav-logo-text { line-height: 1.1; }
  .nav-logo-text strong { display: block; font-size: 15px; font-weight: 800; color: var(--primary); }
  .nav-logo-text span  { font-size: 10px; color: var(--text-m); letter-spacing: .5px; text-transform: uppercase; }

  .nav-links {
    display: flex;
    list-style: none;
    gap: 4px;
    align-items: center;
  }
  .nav-links a {
    display: block;
    padding: 8px 14px;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text);
    text-decoration: none;
    border-radius: 6px;
    transition: all .2s;
    white-space: nowrap;
  }
  .nav-links a:hover,
  .nav-links a.active { color: var(--primary); background: rgba(0,76,143,.07); }

  .nav-cta {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
  }
  .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: 6px; font-size: 13.5px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: all .25s; line-height: 1; }
  .btn-primary   { background: var(--accent); color: #fff; }
  .btn-primary:hover { background: var(--accent-h); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(228,35,43,.35); }
  .btn-outline   { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
  .btn-outline:hover { background: var(--primary); color: #fff; transform: translateY(-1px); }

  /* hamburger */
  .hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 4px; }
  .hamburger span { width: 24px; height: 2px; background: var(--text); border-radius: 2px; transition: all .3s; }

  /* ─── HERO ─── */
  .hero {
    position: relative;
    min-height: calc(100vh - var(--nav-h) - 30px);
    background: url('<?= $base ?>/images/hero_locker.png') no-repeat center center / cover;
    display: flex;
    align-items: center;
  }
  .hero::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(105deg, rgba(0,30,70,.85) 0%, rgba(0,60,120,.65) 55%, rgba(0,30,70,.3) 100%);
  }
  .hero-content {
    position: relative;
    max-width: 1280px;
    margin: 0 auto;
    padding: 80px 24px;
    width: 100%;
  }
  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(200,168,75,.2);
    border: 1px solid rgba(200,168,75,.5);
    color: var(--gold);
    font-size: 12px;
    font-weight: 700;
    padding: 5px 14px;
    border-radius: 50px;
    margin-bottom: 20px;
    letter-spacing: .8px;
    text-transform: uppercase;
  }
  .hero h1 {
    font-size: clamp(28px, 4vw, 52px);
    font-weight: 800;
    color: #fff;
    line-height: 1.15;
    max-width: 620px;
    margin-bottom: 18px;
  }
  .hero h1 span { color: var(--gold); }
  .hero p {
    font-size: 16px;
    color: rgba(255,255,255,.82);
    max-width: 540px;
    line-height: 1.7;
    margin-bottom: 36px;
  }
  .hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
  .btn-hero-primary {
    background: var(--accent);
    color: #fff;
    padding: 14px 28px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all .25s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-hero-primary:hover { background: var(--accent-h); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(228,35,43,.4); }
  .btn-hero-secondary {
    background: rgba(255,255,255,.12);
    border: 2px solid rgba(255,255,255,.4);
    color: #fff;
    padding: 14px 28px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all .25s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .btn-hero-secondary:hover { background: rgba(255,255,255,.22); border-color: rgba(255,255,255,.7); transform: translateY(-2px); }

  .hero-stats {
    display: flex;
    gap: 0;
    margin-top: 52px;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    max-width: 580px;
    overflow: hidden;
  }
  .hero-stat {
    flex: 1;
    padding: 18px 20px;
    text-align: center;
    border-right: 1px solid rgba(255,255,255,.15);
  }
  .hero-stat:last-child { border-right: none; }
  .hero-stat strong { display: block; font-size: 24px; font-weight: 800; color: var(--gold); }
  .hero-stat span { font-size: 11px; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; display: block; }

  /* ─── BREADCRUMB ─── */
  .breadcrumb-bar {
    background: var(--light-bg);
    border-bottom: 1px solid var(--border);
    padding: 10px 0;
  }
  .breadcrumb-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 24px;
    display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-m);
  }
  .breadcrumb-inner a { color: var(--primary); text-decoration: none; font-weight: 600; }
  .breadcrumb-inner a:hover { text-decoration: underline; }
  .breadcrumb-inner .sep { color: #aab; }

  /* ─── SECTION COMMONS ─── */
  section { padding: 72px 0; }
  .section-inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
  .section-label {
    font-size: 12px; font-weight: 700; color: var(--accent);
    letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 8px;
  }
  .section-title {
    font-size: clamp(22px, 3vw, 34px);
    font-weight: 800; color: var(--primary);
    line-height: 1.25; margin-bottom: 14px;
  }
  .section-sub {
    font-size: 15px; color: var(--text-m);
    max-width: 560px; line-height: 1.7; margin-bottom: 48px;
  }

  /* ─── FEATURES SECTION ─── */
  .features-section { background: var(--white); }
  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 24px;
  }
  .feature-card {
    background: var(--light-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px 24px;
    transition: all .3s;
    position: relative;
    overflow: hidden;
  }
  .feature-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), #0077cc);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform .3s;
  }
  .feature-card:hover { transform: translateY(-5px); box-shadow: 0 12px 32px rgba(0,76,143,.12); border-color: var(--primary); background: #fff; }
  .feature-card:hover::before { transform: scaleX(1); }
  .feature-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, var(--primary), #0077cc);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    margin-bottom: 18px;
    box-shadow: 0 4px 12px rgba(0,76,143,.25);
  }
  .feature-card h3 { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
  .feature-card p { font-size: 13px; color: var(--text-m); line-height: 1.6; }

  /* ─── LOCKER SIZES (TABS) ─── */
  .sizes-section { background: var(--light-bg); }
  .tabs-wrapper { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 36px; overflow-x: auto; }
  .tab-btn {
    padding: 12px 24px;
    font-size: 14px; font-weight: 600;
    color: var(--text-m);
    background: none; border: none; cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    white-space: nowrap;
  }
  .tab-btn:hover { color: var(--primary); }
  .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; animation: fadeIn .3s; }
  @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

  .size-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
  .size-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px;
    transition: all .3s;
    text-align: center;
  }
  .size-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,76,143,.1); border-color: var(--primary); }
  .size-card .size-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--primary), #0077cc);
    color: #fff; font-size: 11px; font-weight: 700;
    padding: 4px 12px; border-radius: 50px;
    margin-bottom: 16px; letter-spacing: .5px;
  }
  .size-card .size-icon { font-size: 40px; margin-bottom: 12px; }
  .size-card h3 { font-size: 18px; font-weight: 800; color: var(--primary); margin-bottom: 6px; }
  .size-card .size-dim { font-size: 13px; color: var(--text-m); margin-bottom: 12px; }
  .size-card .size-price { font-size: 22px; font-weight: 800; color: var(--accent); }
  .size-card .size-price small { font-size: 12px; font-weight: 400; color: var(--text-m); }
  .size-card ul { list-style: none; text-align: left; margin: 16px 0; }
  .size-card ul li { font-size: 13px; color: var(--text-m); padding: 5px 0; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }
  .size-card ul li:last-child { border-bottom: none; }
  .size-card ul li::before { content: '✓'; color: #22c55e; font-weight: 700; flex-shrink: 0; }

  /* ─── PORTAL SECTION ─── */
  .portal-section { background: var(--white); }
  .portal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
  .portal-card {
    border-radius: 14px;
    padding: 36px 28px;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: all .3s;
    position: relative;
    overflow: hidden;
  }
  .portal-card::after {
    content: '';
    position: absolute; inset: 0;
    opacity: 0;
    transition: opacity .3s;
    background: rgba(255,255,255,.07);
  }
  .portal-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.18); }
  .portal-card:hover::after { opacity: 1; }
  .portal-admin   { background: linear-gradient(135deg, #004c8f, #003566); color: #fff; }
  .portal-banker  { background: linear-gradient(135deg, #0f766e, #065f46); color: #fff; }
  .portal-customer{ background: linear-gradient(135deg, #7c3aed, #5b21b6); color: #fff; }
  .portal-request { background: linear-gradient(135deg, #e4232b, #9f1239); color: #fff; }
  .portal-icon { font-size: 44px; margin-bottom: 16px; display: block; }
  .portal-card h3 { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
  .portal-card p { font-size: 13px; opacity: .82; line-height: 1.5; margin-bottom: 20px; }
  .portal-arrow {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 700;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    padding: 8px 18px; border-radius: 50px;
    transition: all .25s;
  }
  .portal-card:hover .portal-arrow { background: rgba(255,255,255,.28); }

  /* ─── HOW IT WORKS ─── */
  .how-section { background: var(--light-bg); }
  .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0; position: relative; }
  .steps::before {
    content: '';
    position: absolute;
    top: 32px; left: 10%; right: 10%;
    height: 2px;
    background: linear-gradient(90deg, var(--primary), #0077cc);
    z-index: 0;
  }
  .step { text-align: center; padding: 0 20px; position: relative; z-index: 1; }
  .step-num {
    width: 64px; height: 64px;
    background: linear-gradient(135deg, var(--primary), #0077cc);
    color: #fff;
    font-size: 22px; font-weight: 800;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 4px 14px rgba(0,76,143,.35);
    border: 3px solid #fff;
  }
  .step h4 { font-size: 15px; font-weight: 700; color: var(--primary); margin-bottom: 8px; }
  .step p { font-size: 13px; color: var(--text-m); line-height: 1.6; }

  /* ─── FAQ ─── */
  .faq-section { background: var(--white); }
  .faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .faq-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    transition: box-shadow .2s;
  }
  .faq-item:hover { box-shadow: 0 4px 16px rgba(0,76,143,.08); }
  .faq-q {
    width: 100%; text-align: left;
    background: none; border: none; cursor: pointer;
    padding: 18px 20px;
    font-size: 14px; font-weight: 600; color: var(--text);
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    transition: color .2s;
  }
  .faq-q:hover { color: var(--primary); }
  .faq-q .faq-icon { font-size: 18px; flex-shrink: 0; transition: transform .3s; color: var(--primary); }
  .faq-a {
    max-height: 0; overflow: hidden;
    transition: max-height .35s ease, padding .3s;
    font-size: 13.5px; color: var(--text-m); line-height: 1.7;
    padding: 0 20px;
  }
  .faq-item.open .faq-q { color: var(--primary); background: rgba(0,76,143,.04); }
  .faq-item.open .faq-icon { transform: rotate(45deg); }
  .faq-item.open .faq-a { max-height: 200px; padding: 0 20px 18px; }

  /* ─── CTA BANNER ─── */
  .cta-banner {
    background: linear-gradient(105deg, var(--primary) 0%, #0066cc 60%, #0077dd 100%);
    padding: 64px 0;
  }
  .cta-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 24px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 32px; flex-wrap: wrap;
  }
  .cta-text h2 { font-size: 28px; font-weight: 800; color: #fff; margin-bottom: 8px; }
  .cta-text p  { font-size: 15px; color: rgba(255,255,255,.8); max-width: 480px; line-height: 1.6; }
  .cta-actions { display: flex; gap: 12px; flex-wrap: wrap; }
  .btn-cta-white {
    background: #fff; color: var(--primary);
    padding: 14px 28px; border-radius: 6px;
    font-size: 14px; font-weight: 700;
    text-decoration: none;
    transition: all .25s;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-cta-white:hover { background: var(--light-bg); transform: translateY(-2px); }
  .btn-cta-outline {
    background: transparent;
    border: 2px solid rgba(255,255,255,.5);
    color: #fff;
    padding: 14px 28px; border-radius: 6px;
    font-size: 14px; font-weight: 600;
    text-decoration: none;
    transition: all .25s;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-cta-outline:hover { border-color: #fff; background: rgba(255,255,255,.1); transform: translateY(-2px); }

  /* ─── FOOTER ─── */
  footer {
    background: #0a1628;
    color: rgba(255,255,255,.7);
    padding: 56px 0 0;
  }
  .footer-grid {
    max-width: 1280px; margin: 0 auto; padding: 0 24px;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    padding-bottom: 48px;
  }
  .footer-brand .logo-text strong { font-size: 18px; font-weight: 800; color: #fff; }
  .footer-brand .logo-text span  { font-size: 11px; opacity: .6; text-transform: uppercase; }
  .footer-brand p { font-size: 13px; line-height: 1.7; margin-top: 14px; margin-bottom: 18px; max-width: 280px; }
  .footer-social { display: flex; gap: 10px; }
  .social-btn {
    width: 36px; height: 36px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    transition: all .2s;
    cursor: pointer;
  }
  .social-btn:hover { background: var(--primary); border-color: var(--primary); }
  .footer-col h4 { font-size: 13px; font-weight: 700; color: #fff; margin-bottom: 16px; text-transform: uppercase; letter-spacing: .8px; }
  .footer-col ul { list-style: none; }
  .footer-col ul li { margin-bottom: 10px; }
  .footer-col ul a { color: rgba(255,255,255,.65); text-decoration: none; font-size: 13px; transition: color .2s; }
  .footer-col ul a:hover { color: #fff; }
  .footer-bottom {
    border-top: 1px solid rgba(255,255,255,.08);
    max-width: 1280px; margin: 0 auto; padding: 18px 24px;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 8px;
    font-size: 12px; color: rgba(255,255,255,.4);
  }

  /* ─── FLOATING APPLY BTN ─── */
  .floating-apply {
    position: fixed;
    bottom: 28px; right: 28px;
    background: var(--accent);
    color: #fff;
    padding: 14px 22px;
    border-radius: 50px;
    font-size: 14px; font-weight: 700;
    text-decoration: none;
    box-shadow: 0 6px 24px rgba(228,35,43,.45);
    display: flex; align-items: center; gap: 8px;
    transition: all .3s;
    z-index: 999;
    animation: pulse 2.5s infinite;
  }
  .floating-apply:hover { background: var(--accent-h); transform: translateY(-3px) scale(1.03); }
  @keyframes pulse { 0%,100%{box-shadow:0 6px 24px rgba(228,35,43,.45);} 50%{box-shadow:0 6px 36px rgba(228,35,43,.7);} }

  /* ─── RESPONSIVE ─── */
  @media (max-width: 900px) {
    .nav-links { display: none; }
    .hamburger { display: flex; }
    .nav-links.open {
      display: flex; flex-direction: column;
      position: absolute; top: var(--nav-h); left: 0; right: 0;
      background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.1);
      padding: 16px;
    }
    .faq-grid { grid-template-columns: 1fr; }
    .footer-grid { grid-template-columns: 1fr 1fr; }
    .steps::before { display: none; }
    .cta-inner { flex-direction: column; text-align: center; }
  }
  @media (max-width: 600px) {
    .hero-stats { flex-direction: column; }
    .hero-stat { border-right: none; border-bottom: 1px solid rgba(255,255,255,.15); }
    .hero-stat:last-child { border-bottom: none; }
    .footer-grid { grid-template-columns: 1fr; gap: 24px; }
    .top-bar-left { display: none; }
    .nav-cta .btn-outline { display: none; }
  }
</style>
</head>
<body>

<!-- TOP UTILITY BAR -->
<div class="top-bar">
  <div class="top-bar-inner">
    <div class="top-bar-left">📍 Koshys Institute of Management Studies &nbsp;|&nbsp; ☎ 1800-XXX-XXXX (24×7 Toll Free)</div>
    <div class="top-bar-right">
      <span>🌐 EN</span>
      <a href="<?= $base ?>/customer/login.php">NetBanking</a>
      <a href="#">Branch Locator</a>
    </div>
  </div>
</div>

<!-- NAVBAR -->
<nav class="navbar" role="navigation" aria-label="Main Navigation">
  <div class="nav-inner">
    <a href="<?= $base ?>/" class="nav-logo" aria-label="Bank Locker Home">
      <div class="nav-logo-icon">🏦</div>
      <div class="nav-logo-text">
        <strong>SecureVault Bank</strong>
        <span>Locker Management System</span>
      </div>
    </a>

    <ul class="nav-links" id="nav-links">
      <li><a href="#features" class="active">Safe Lockers</a></li>
      <li><a href="#sizes">Locker Sizes</a></li>
      <li><a href="#portals">Portals</a></li>
      <li><a href="#how">How It Works</a></li>
      <li><a href="#faq">FAQs</a></li>
    </ul>

    <div class="nav-cta">
      <a href="<?= $base ?>/customer/login.php" class="btn btn-outline" id="login-btn">Login</a>
      <a href="<?= $base ?>/new_locker_request.php" class="btn btn-primary" id="apply-btn">Apply Now</a>
    </div>

    <button class="hamburger" id="hamburger" aria-label="Toggle Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- HERO -->
<section class="hero" aria-label="Hero Banner">
  <div class="hero-content">
    <div class="hero-badge">🔒 Trusted &amp; Certified Secure</div>
    <h1>Keep Your Valuables <span>Safe &amp; Secure</span> With Our Lockers</h1>
    <p>Rent a safe deposit locker and enjoy peace of mind knowing your jewellery, documents, and irreplaceable assets are protected by bank-grade security 24×7.</p>
    <div class="hero-actions">
      <a href="<?= $base ?>/new_locker_request.php" class="btn-hero-primary" id="hero-apply-btn">📩 Apply for a Locker</a>
      <a href="#features" class="btn-hero-secondary" id="hero-learn-btn">Learn More →</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><strong>3</strong><span>Locker Sizes</span></div>
      <div class="hero-stat"><strong>24×7</strong><span>Security</span></div>
      <div class="hero-stat"><strong>100%</strong><span>Digital Mgmt</span></div>
      <div class="hero-stat"><strong>Bank-Grade</strong><span>Protection</span></div>
    </div>
  </div>
</section>

<!-- BREADCRUMB -->
<div class="breadcrumb-bar">
  <div class="breadcrumb-inner">
    <a href="<?= $base ?>/">Home</a>
    <span class="sep">›</span>
    <span>Safe Deposit Lockers</span>
  </div>
</div>

<!-- FEATURES -->
<section class="features-section" id="features">
  <div class="section-inner">
    <p class="section-label">Why Choose Us</p>
    <h2 class="section-title">Benefits of Our Safe Deposit Lockers</h2>
    <p class="section-sub">Our locker facility offers unmatched security, convenience, and flexibility to protect everything you treasure most.</p>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Dual-Key Security</h3>
        <p>Two-key system — one with you, one with the bank — ensures absolute safety and zero unauthorised access.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📹</div>
        <h3>24×7 CCTV Surveillance</h3>
        <p>Round-the-clock monitoring with state-of-the-art cameras covering every inch of the vault area.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛡️</div>
        <h3>Fireproof &amp; Waterproof</h3>
        <p>Military-grade vaults built to withstand fire, flood, and extreme environmental conditions.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📱</div>
        <h3>Digital Management</h3>
        <p>Track locker status, access history, and rent payments conveniently through our online portal.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">👥</div>
        <h3>Joint Locker Facility</h3>
        <p>Share locker access with family members — ideal for joint account holders and nominees.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Instant Alerts</h3>
        <p>Get real-time SMS and email notifications whenever your locker is accessed or modified.</p>
      </div>
    </div>
  </div>
</section>

<!-- LOCKER SIZES (TABS) -->
<section class="sizes-section" id="sizes">
  <div class="section-inner">
    <p class="section-label">Locker Options</p>
    <h2 class="section-title">Choose the Right Size for You</h2>
    <p class="section-sub">We offer three locker categories designed to suit different storage needs, from personal documents to family heirlooms.</p>

    <div class="tabs-wrapper" role="tablist">
      <button class="tab-btn active" data-tab="small" role="tab" aria-selected="true" id="tab-small">Small Locker</button>
      <button class="tab-btn" data-tab="medium" role="tab" aria-selected="false" id="tab-medium">Medium Locker</button>
      <button class="tab-btn" data-tab="large" role="tab" aria-selected="false" id="tab-large">Large Locker</button>
    </div>

    <div class="tab-panel active" id="panel-small" role="tabpanel" aria-labelledby="tab-small">
      <div class="size-cards">
        <div class="size-card">
          <span class="size-badge">Most Popular</span>
          <div class="size-icon">🗃️</div>
          <h3>Small Locker</h3>
          <p class="size-dim">Approx. 5" × 5" × 18"</p>
          <p class="size-price">₹1,500 <small>/ year</small></p>
          <ul>
            <li>Ideal for documents &amp; jewellery</li>
            <li>Dual-key access system</li>
            <li>Fireproof &amp; waterproof</li>
            <li>24×7 surveillance</li>
            <li>Nominee facility available</li>
          </ul>
          <a href="<?= $base ?>/new_locker_request.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">Apply Now</a>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="panel-medium" role="tabpanel" aria-labelledby="tab-medium">
      <div class="size-cards">
        <div class="size-card">
          <span class="size-badge">Best Value</span>
          <div class="size-icon">🗄️</div>
          <h3>Medium Locker</h3>
          <p class="size-dim">Approx. 5" × 10" × 18"</p>
          <p class="size-price">₹2,500 <small>/ year</small></p>
          <ul>
            <li>Ideal for bulky documents &amp; valuables</li>
            <li>Dual-key access system</li>
            <li>Fireproof &amp; waterproof</li>
            <li>24×7 surveillance</li>
            <li>Joint locker option available</li>
          </ul>
          <a href="<?= $base ?>/new_locker_request.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">Apply Now</a>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="panel-large" role="tabpanel" aria-labelledby="tab-large">
      <div class="size-cards">
        <div class="size-card">
          <span class="size-badge">Premium</span>
          <div class="size-icon">🏦</div>
          <h3>Large Locker</h3>
          <p class="size-dim">Approx. 10" × 10" × 18"</p>
          <p class="size-price">₹4,000 <small>/ year</small></p>
          <ul>
            <li>Maximum capacity for families</li>
            <li>Dual-key access system</li>
            <li>Fireproof &amp; waterproof</li>
            <li>24×7 surveillance</li>
            <li>Up to 3 nominees supported</li>
          </ul>
          <a href="<?= $base ?>/new_locker_request.php" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">Apply Now</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PORTALS -->
<section class="portal-section" id="portals">
  <div class="section-inner">
    <p class="section-label">Access Portals</p>
    <h2 class="section-title">Login to Your Portal</h2>
    <p class="section-sub">Dedicated portals for every stakeholder — designed for seamless, secure management of locker operations.</p>
    <div class="portal-grid">
      <a href="<?= $base ?>/admin/login.php" class="portal-card portal-admin" id="portal-admin">
        <span class="portal-icon">🔐</span>
        <h3>Banker Login</h3>
        <p>Full administrative control over lockers, customers, reports and branch operations.</p>
        <span class="portal-arrow">Access Portal →</span>
      </a>
      <a href="<?= $base ?>/sub_banker/login.php" class="portal-card portal-banker" id="portal-subbanker">
        <span class="portal-icon">🏛️</span>
        <h3>Sub-Banker Login</h3>
        <p>Manage locker assignments, customer verifications, and daily branch operations.</p>
        <span class="portal-arrow">Access Portal →</span>
      </a>
      <a href="<?= $base ?>/customer/login.php" class="portal-card portal-customer" id="portal-customer">
        <span class="portal-icon">👤</span>
        <h3>Customer Login</h3>
        <p>View your locker details, access history, payment status, and update nominee info.</p>
        <span class="portal-arrow">Access Portal →</span>
      </a>
      <a href="<?= $base ?>/new_locker_request.php" class="portal-card portal-request" id="portal-newrequest">
        <span class="portal-icon">📩</span>
        <h3>New Locker Request</h3>
        <p>Apply for a new safe deposit locker online — quick, paperless and hassle-free.</p>
        <span class="portal-arrow">Apply Now →</span>
      </a>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section" id="how">
  <div class="section-inner">
    <p class="section-label">Simple Process</p>
    <h2 class="section-title">How to Get Your Locker</h2>
    <p class="section-sub">Getting a safe deposit locker is fast and fully digital — just four simple steps.</p>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <h4>Submit Request</h4>
        <p>Fill out the online locker request form with your personal and KYC details.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h4>Verification</h4>
        <p>Our team reviews your application and verifies your identity and documents.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h4>Locker Allotment</h4>
        <p>A locker of your preferred size is allocated to you at your nearest branch.</p>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <h4>Start Using</h4>
        <p>Collect your key, complete the agreement, and start using your locker immediately.</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="faq-section" id="faq">
  <div class="section-inner">
    <p class="section-label">Got Questions?</p>
    <h2 class="section-title">Frequently Asked Questions</h2>
    <p class="section-sub">Everything you need to know about our safe deposit locker service.</p>
    <div class="faq-grid" id="faq-grid">

      <div class="faq-item">
        <button class="faq-q">Who can avail a safe deposit locker? <span class="faq-icon">+</span></button>
        <div class="faq-a">Any individual, jointly with another person, or a company, can avail a safe deposit locker. The applicant must have a valid account with us.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">What can I store in the locker? <span class="faq-icon">+</span></button>
        <div class="faq-a">You can store jewellery, important documents, property papers, passports, and other valuables. Currency, illegal items, and perishables are not permitted.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">Is there a nomination facility? <span class="faq-icon">+</span></button>
        <div class="faq-a">Yes! You can nominate a family member who will be permitted access to the locker contents in the unfortunate event of your demise.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">What happens if I lose my locker key? <span class="faq-icon">+</span></button>
        <div class="faq-a">Report the loss immediately to the branch. The locker will be drilled open in your presence and a new lock and key will be provided at an applicable charge.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">Can I access the locker any time? <span class="faq-icon">+</span></button>
        <div class="faq-a">Lockers can be accessed during branch working hours. The digital portal is available 24×7 to track locker status and manage payments.</div>
      </div>
      <div class="faq-item">
        <button class="faq-q">Is the locker rental paid annually? <span class="faq-icon">+</span></button>
        <div class="faq-a">Yes, the locker rent is charged annually and is debited from your linked bank account on the due date each year.</div>
      </div>

    </div>
  </div>
</section>

<!-- CTA BANNER -->
<div class="cta-banner">
  <div class="cta-inner">
    <div class="cta-text">
      <h2>Ready to Secure What Matters Most?</h2>
      <p>Apply for a safe deposit locker today and get priority allotment at your nearest branch. It takes less than 5 minutes.</p>
    </div>
    <div class="cta-actions">
      <a href="<?= $base ?>/new_locker_request.php" class="btn-cta-white" id="cta-apply-btn">📩 Apply for a Locker</a>
      <a href="<?= $base ?>/customer/login.php" class="btn-cta-outline" id="cta-login-btn">Login to Portal →</a>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="logo-text">
        <strong>🏦 SecureVault Bank</strong><br>
        <span>Locker Management System</span>
      </div>
      <p>A comprehensive digital platform for managing bank locker services — secure, transparent, and fully paperless.</p>
      <div class="footer-social">
        <div class="social-btn">📘</div>
        <div class="social-btn">🐦</div>
        <div class="social-btn">📸</div>
        <div class="social-btn">▶️</div>
      </div>
    </div>
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="#features">Features</a></li>
        <li><a href="#sizes">Locker Sizes</a></li>
        <li><a href="#how">How It Works</a></li>
        <li><a href="#faq">FAQs</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Portals</h4>
      <ul>
        <li><a href="<?= $base ?>/admin/login.php">Banker Login</a></li>
        <li><a href="<?= $base ?>/sub_banker/login.php">Sub-Banker Login</a></li>
        <li><a href="<?= $base ?>/customer/login.php">Customer Login</a></li>
        <li><a href="<?= $base ?>/new_locker_request.php">New Request</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <ul>
        <li><a href="#">📍 KIMS, Bangalore</a></li>
        <li><a href="#">📞 1800-XXX-XXXX</a></li>
        <li><a href="#">✉️ support@securevault.in</a></li>
        <li><a href="#">⏰ Mon–Sat, 9AM–5PM</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 SecureVault Bank Locker System. BCA Project — Koshys Institute of Management Studies.</span>
    <span>Made by Rinto M Reji</span>
  </div>
</footer>

<!-- FLOATING APPLY BUTTON -->
<a href="<?= $base ?>/new_locker_request.php" class="floating-apply" id="floating-apply-btn">
  📩 Apply Now
</a>

<script>
  // Hamburger toggle
  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('nav-links');
  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    hamburger.setAttribute('aria-expanded', navLinks.classList.contains('open'));
  });

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href').slice(1);
      const el = document.getElementById(id);
      if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
      navLinks.classList.remove('open');
    });
  });

  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      btn.setAttribute('aria-selected','true');
      document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    });
  });

  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.faq-item');
      const wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!wasOpen) item.classList.add('open');
    });
  });

  // Hide floating button when in hero
  const floatingBtn = document.getElementById('floating-apply-btn');
  window.addEventListener('scroll', () => {
    floatingBtn.style.opacity = window.scrollY > 300 ? '1' : '0';
    floatingBtn.style.pointerEvents = window.scrollY > 300 ? 'auto' : 'none';
  }, { passive: true });
  floatingBtn.style.opacity = '0';
  floatingBtn.style.transition = 'opacity .3s';
  floatingBtn.style.pointerEvents = 'none';
</script>
</body>
</html>
