<?php
// Shared top part of every admin page: admin check, <head>, sidebar, top bar.
// Before including this file, a page can set:
//   $pageTitle  - text for the browser tab
//   $activePage - 'dashboard' | 'appointments' | 'services' | 'profile'
//   $topBarTitle - title shown in the top bar (default: Salon Management)
//   $showAddAppointment - true to show the "Add Appointment" button

require_once __DIR__ . '/../../../backend/auth/admin_guard.php';

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$pageTitle  = $pageTitle  ?? 'Admin | Paws & Bubbles';
$activePage = $activePage ?? '';
$topBarTitle        = $topBarTitle        ?? 'Salon Management';
$showAddAppointment = $showAddAppointment ?? false;

$logoutUrl = '../../backend/auth/logout.php';

$adminName  = $_SESSION['full_name'] ?? 'Admin';
$adminEmail = $_SESSION['email'] ?? '';   
$initials  = '';
foreach (array_slice(preg_split('/\s+/', trim($adminName)), 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}

$navLinks = [
    'dashboard'    => ['Dashboard',       'admin_dashboard.php',    'dashboard'],
    'appointments' => ['Appointments',    'admin_appointments.php', 'calendar_month'],
    'services'     => ['Manage Services', 'admin_services.php',     'spa'],
    'profile'      => ['Admin Profile',   'admin_profile.php',      'person'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<style>@layer base{html,body{margin:0;padding:0;}body{overscroll-behavior:none;}main>:first-child{margin-top:0!important;}main>:last-child{margin-bottom:0!important;}}::-webkit-scrollbar{display:none;}</style>
<script src="https://cdn.tailwindcss.com"></script>
<script src="../js/admin-tailwind-config.js"></script>
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased">

<aside class="fixed left-0 top-0 h-full w-64 bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex flex-col justify-between">
  <div class="flex flex-col">
    <div class="h-16 px-space-md flex items-center gap-space-sm">
      <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary-fixed text-primary shadow-sm">
        <span class="material-symbols-outlined text-[22px]">pets</span>
      </div>
      <div class="flex flex-col">
        <span class="font-title-md text-title-md text-on-surface leading-tight">Paws &amp; Bubbles</span>
        <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight">Grooming Salon</span>
      </div>
    </div>
    <div class="px-space-md pt-space-md">
      <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline px-space-xs mb-space-xs block">Main Menu</span>
    </div>
    <nav class="flex flex-col gap-space-xs px-space-md">
      <?php foreach ($navLinks as $key => [$label, $href, $icon]):
          $cls = ($activePage === $key)
              ? 'bg-surface-container text-primary font-title-md'
              : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface';
      ?>
        <a class="flex items-center gap-space-sm px-space-sm py-space-sm rounded-lg transition-colors <?= $cls ?>"
           href="<?= e($href) ?>"<?= $activePage === $key ? ' aria-current="page"' : '' ?>>
          <span class="material-symbols-outlined text-title-lg"><?= e($icon) ?></span>
          <span class="font-body-md text-body-md"><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
      <a class="flex items-center gap-space-sm px-space-sm py-space-sm rounded-lg text-error hover:bg-error-container transition-colors mt-2"
         href="<?= e($logoutUrl) ?>">
        <span class="material-symbols-outlined text-title-lg">logout</span>
        <span class="font-body-md text-body-md">Logout</span>
      </a>
    </nav>
  </div>
  <div class="p-space-md m-space-md rounded-xl bg-surface-container-low flex items-center gap-space-sm">
    <div class="w-8 h-8 rounded-full bg-primary-fixed flex items-center justify-center text-primary">
      <span class="material-symbols-outlined text-title-md">admin_panel_settings</span>
    </div>
    <div class="flex flex-col text-left">
      <span class="font-title-md text-body-md font-semibold text-on-surface">Admin Panel</span>
      <span class="font-label-sm text-label-sm text-on-surface-variant">PHP &amp; MySQL</span>
    </div>
  </div>
</aside>

<div class="pl-64">
  <header class="fixed top-0 left-64 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 px-gutter flex items-center justify-between gap-space-md">
    <span class="font-title-lg text-title-lg text-on-surface font-semibold"><?= e($topBarTitle) ?></span>
    <div class="flex items-center gap-space-md ml-auto">
      <?php if ($showAddAppointment): ?>
        <a href="admin_walkin.php"
           class="flex items-center gap-space-xs bg-primary hover:bg-primary-container text-on-primary px-space-md h-10 rounded-lg shadow-sm transition-all font-title-md text-title-md">
          <span class="material-symbols-outlined text-title-md">add</span>
          <span>Add Appointment</span>
        </a>
        <div class="h-8 w-px bg-surface-container"></div>
      <?php endif; ?>
      <div class="flex items-center gap-space-sm">
        <div class="w-9 h-9 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-title-md text-title-md font-semibold"><?= e($initials) ?></div>
        <div class="flex flex-col text-left">
          <span class="font-title-md text-body-md text-on-surface font-semibold leading-tight"><?= e($adminName) ?></span>
          <?php if ($adminEmail !== ''): ?>
            <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight"><?= e($adminEmail) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="w-full pt-16 bg-surface min-h-screen">