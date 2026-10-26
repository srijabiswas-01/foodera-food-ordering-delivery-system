<?php
require_once __DIR__ . '/../../app-functions.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function riderToken(){ if(empty($_SESSION['rider_csrf'])) $_SESSION['rider_csrf']=bin2hex(random_bytes(32)); return $_SESSION['rider_csrf']; }
function riderRequireLogin(){ if(empty($_SESSION['foodera_rider']) || empty($_SESSION['rider_id'])){ header('Location: ../login.php'); exit; } }
function riderEscape($value){ return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function riderRedirect($path,$message=''){ if($message!=='') $_SESSION['rider_notice']=$message; header('Location: '.$path); exit; }

/* Keep the staff navigation consistent across the small rider portal pages. */
if (PHP_SAPI !== 'cli' && !defined('FOODERA_RIDER_NAV_BUFFER')) {
    define('FOODERA_RIDER_NAV_BUFFER', true);
    ob_start(function ($html) {
        if (strpos($html, 'class="rider-top-actions"') !== false && strpos($html, 'href="profile.php"') === false) $html = str_replace('class="rider-top-actions">', 'class="rider-top-actions"><a class="rider-history-link" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>', $html);
        if (strpos($html, 'class="rider-profile-form"') !== false) $html = str_replace('</head>', '<style>.rider-profile-card{max-width:760px}.rider-profile-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:0}.rider-profile-form label{display:grid;gap:6px;font-weight:700;font-size:.92rem}.rider-profile-form small{color:var(--muted);font-size:.8rem;font-weight:400}.rider-profile-form input,.rider-profile-form textarea{width:100%;padding:11px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font:inherit}.rider-profile-form textarea{resize:vertical}.rider-profile-form input[readonly],.rider-profile-form textarea[readonly]{background:#f8fafc;color:var(--muted)}.rider-profile-form button{grid-column:1/-1;justify-self:start}@media(max-width:600px){.rider-profile-form{grid-template-columns:1fr}.rider-profile-form button{width:100%}}</style></head>', $html);
        if (strpos($html, 'class="rider-profile-grid"') !== false) $html = str_replace('</head>', '<style>.rider-profile-form{display:grid;gap:16px;margin:0}.rider-profile-form>.rider-card{margin:0}.rider-profile-form h2{margin:0 0 14px}.rider-profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.rider-profile-grid label{display:grid;gap:6px;font-weight:700;font-size:.9rem}.rider-profile-grid input,.rider-profile-grid textarea,.rider-profile-grid select{width:100%;padding:11px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--text);font:inherit}.rider-profile-grid input[readonly]{background:#f8fafc;color:var(--muted)}.rider-profile-wide{grid-column:1/-1}.rider-profile-save{justify-self:start}@media(max-width:600px){.rider-profile-grid{grid-template-columns:1fr}.rider-profile-wide{grid-column:auto}.rider-profile-save{width:100%}}</style></head>', $html);
        return $html;
    });
}
