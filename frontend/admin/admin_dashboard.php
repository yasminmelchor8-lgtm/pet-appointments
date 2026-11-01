<?php
date_default_timezone_set('Asia/Manila');   // gamit PH time

$pageTitle  = 'Dashboard | Paws & Bubbles';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/admin_header.php';   // admin check + layout ulit
require_once __DIR__ . '/../../backend/appointments/appointment_functions.php';

$counts     = getAppointmentCounts($pdo);
$newThisWeek = countBookedSince($pdo, date('Y-m-d H:i:s', strtotime('-7 days')));
$recent     = getRecentAppointments($pdo, 4);

$firstName = explode(' ', trim($adminName))[0] ?: 'Admin';

// Layout nung calendar"Today, Oct 24" / "Yesterday" / "Tomorrow" / "Oct 24, 2026"
function friendlyDate(string $date): string
{
    $day   = new DateTime($date);
    $today = new DateTime('today');
    $diff  = (int) $today->diff($day)->format('%r%a');

    if ($diff === 0)  return 'Today, ' . $day->format('M j');
    if ($diff === -1) return 'Yesterday';
    if ($diff === 1)  return 'Tomorrow';
    return $day->format('M j, Y');
}

// layout ulit "Emma Watson" -> "EW"
function initialsOf(string $name): string
{
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out !== '' ? $out : '?';
}

$styles = [
    'pending'   => ['badge' => 'bg-secondary-fixed text-on-secondary-container', 'dot' => 'bg-secondary',
                    'avatar' => 'bg-secondary-fixed text-on-secondary-fixed-variant',
                    'pet' => 'bg-secondary-fixed/50 text-secondary', 'time' => 'text-secondary'],
    'confirmed' => ['badge' => 'bg-surface-container text-primary', 'dot' => 'bg-primary',
                    'avatar' => 'bg-primary-fixed text-primary',
                    'pet' => 'bg-surface-container text-primary', 'time' => 'text-primary'],
    'completed' => ['badge' => 'bg-surface-container-low text-on-surface-variant', 'dot' => 'bg-outline',
                    'avatar' => 'bg-surface-container-high text-on-surface-variant',
                    'pet' => 'bg-surface-container-low text-outline', 'time' => 'text-outline'],
    'cancelled' => ['badge' => 'bg-error-container text-on-error-container', 'dot' => 'bg-error',
                    'avatar' => 'bg-error-container text-on-error-container',
                    'pet' => 'bg-error-container/50 text-error', 'time' => 'text-outline'],
];
?>
<div class="flex flex-col w-full">
  <div class="p-gutter flex flex-col gap-space-lg max-w-[1440px] mx-auto w-full">

    <!-- Heading, search, add button -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md">
      <div class="flex flex-col">
        <div class="flex items-center gap-space-xs text-primary mb-1">
          <span class="material-symbols-outlined text-[18px]">space_dashboard</span>
          <span class="font-label-sm text-label-sm uppercase tracking-wider text-primary">Administration Module</span>
        </div>
        <h1 class="font-headline-xl text-headline-xl text-on-surface tracking-tight">Dashboard Overview</h1>
        <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">Welcome back, <?= e($firstName) ?>! Here is today's salon activity summary.</p>
      </div>
      <div class="flex items-center gap-space-sm self-start md:self-auto">
        <form method="GET" action="admin_appointments.php" class="relative flex items-center" role="search">
          <span class="material-symbols-outlined absolute left-3 text-outline text-[20px] pointer-events-none">search</span>
          <input class="pl-10 pr-4 py-2 w-64 bg-surface-container-lowest rounded-lg font-body-sm text-body-sm text-on-surface placeholder:text-outline shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                 type="search" name="search" placeholder="Search booking or pet..." aria-label="Search appointments">
        </form>
        <a class="flex items-center gap-space-xs px-space-md py-2 bg-primary text-on-primary font-title-md text-body-md rounded-lg shadow-sm hover:bg-primary-container transition-all active:scale-[0.99]"
           href="admin_walkin.php">
          <span class="material-symbols-outlined text-[20px]">add</span>
          <span>Add New Booking</span>
        </a>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-surface-container-low rounded-full pointer-events-none transition-transform group-hover:scale-110"></div>
        <div class="flex items-center justify-between relative z-10">
          <span class="font-title-md text-body-md text-on-surface-variant font-semibold">Total Appointments</span>
          <div class="w-11 h-11 rounded-lg bg-surface-container text-primary flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">calendar_month</span>
          </div>
        </div>
        <div class="mt-space-lg relative z-10 flex items-baseline justify-between">
          <span class="font-headline-xl text-headline-xl text-on-surface font-bold tracking-tight"><?= $counts['total'] ?></span>
          <?php if ($newThisWeek > 0): ?>
            <div class="flex items-center gap-1 text-primary bg-surface-container-low px-2 py-0.5 rounded-full">
              <span class="material-symbols-outlined text-[14px]">trending_up</span>
              <span class="font-label-sm text-label-sm font-semibold">+<?= $newThisWeek ?> this week</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="mt-space-sm pt-space-xs relative z-10">
          <span class="font-body-sm text-body-sm text-outline">All bookings so far</span>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-secondary-fixed/30 rounded-full pointer-events-none transition-transform group-hover:scale-110"></div>
        <div class="flex items-center justify-between relative z-10">
          <span class="font-title-md text-body-md text-on-surface-variant font-semibold">Pending Appointments</span>
          <div class="w-11 h-11 rounded-lg bg-secondary-fixed text-on-secondary-container flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">schedule</span>
          </div>
        </div>
        <div class="mt-space-lg relative z-10 flex items-baseline justify-between">
          <span class="font-headline-xl text-headline-xl text-on-surface font-bold tracking-tight"><?= $counts['pending'] ?></span>
          <?php if ($counts['pending'] > 0): ?>
            <span class="font-label-sm text-label-sm bg-secondary-fixed text-on-secondary-container px-2.5 py-0.5 rounded-full font-semibold">Needs Review</span>
          <?php endif; ?>
        </div>
        <div class="mt-space-sm pt-space-xs relative z-10">
          <span class="font-body-sm text-body-sm text-outline">Awaiting confirmation</span>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition-shadow">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-surface-container-high/40 rounded-full pointer-events-none transition-transform group-hover:scale-110"></div>
        <div class="flex items-center justify-between relative z-10">
          <span class="font-title-md text-body-md text-on-surface-variant font-semibold">Confirmed Appointments</span>
          <div class="w-11 h-11 rounded-lg bg-surface-container-high text-primary flex items-center justify-center">
            <span class="material-symbols-outlined text-[24px]">check_circle</span>
          </div>
        </div>
        <div class="mt-space-lg relative z-10 flex items-baseline justify-between">
          <span class="font-headline-xl text-headline-xl text-on-surface font-bold tracking-tight"><?= $counts['confirmed'] ?></span>
          <span class="font-label-sm text-label-sm bg-surface-container text-primary px-2.5 py-0.5 rounded-full font-semibold">Scheduled</span>
        </div>
        <div class="mt-space-sm pt-space-xs relative z-10">
          <span class="font-body-sm text-body-sm text-outline">Ready for the salon</span>
        </div>
      </div>
    </div>

    <!-- Recent appointments -->
    <div class="bg-surface-container-lowest rounded-xl shadow-sm flex flex-col overflow-hidden">
      <div class="px-space-lg py-space-md flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm bg-surface-container-low/50">
        <div class="flex items-center gap-space-sm">
          <div class="w-2.5 h-2.5 rounded-full bg-primary"></div>
          <h2 class="font-title-lg text-title-lg text-on-surface font-semibold">Recent Appointments</h2>
          <span class="px-2 py-0.5 bg-surface-container text-primary font-label-sm text-label-sm rounded-full font-semibold">Latest <?= count($recent) ?></span>
        </div>
        <div class="flex items-center gap-space-sm text-outline font-label-sm text-label-sm">
          <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-primary"></span> Confirmed</span>
          <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-secondary-container"></span> Pending</span>
          <span class="flex items-center gap-1"><span class="inline-block w-2 h-2 rounded-full bg-outline-variant"></span> Completed</span>
        </div>
      </div>

      <?php if (count($recent) > 0): ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-lowest text-outline font-label-sm text-label-sm uppercase tracking-wider">
                <th class="px-space-lg py-3">Customer</th>
                <th class="px-space-md py-3">Pet &amp; Breed</th>
                <th class="px-space-md py-3">Service Tier</th>
                <th class="px-space-md py-3">Date &amp; Time</th>
                <th class="px-space-md py-3">Status</th>
                <th class="px-space-lg py-3 text-right">Quick View</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-surface-container-low font-body-md text-body-md">
              <?php foreach ($recent as $a):
                  $st    = $a['status'];
                  $sty   = $styles[$st] ?? $styles['completed'];
                  $breed = trim((string) $a['breed']);
                  $petIcon = ($st === 'cancelled') ? 'cancel' : 'pets';
                  $dateLabel = friendlyDate($a['appointment_date']);
                  $idLabel   = formatAppointmentId((int) $a['appointment_id']);
              ?>
                <tr class="hover:bg-surface-container-low/40 transition-colors">
                  <td class="px-space-lg py-3.5">
                    <div class="flex items-center gap-space-sm">
                      <div class="w-8 h-8 rounded-full <?= $sty['avatar'] ?> font-semibold text-body-sm flex items-center justify-center"><?= e(initialsOf($a['full_name'])) ?></div>
                      <div class="flex flex-col">
                        <span class="font-title-md text-body-md text-on-surface font-semibold leading-tight"><?= e($a['full_name']) ?></span>
                        <span class="font-body-sm text-body-sm text-on-surface-variant"><?= e($a['email'] ?? '') ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="px-space-md py-3.5">
                    <div class="flex items-center gap-2">
                      <div class="w-7 h-7 rounded-lg <?= $sty['pet'] ?> flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px]"><?= $petIcon ?></span>
                      </div>
                      <div class="flex flex-col">
                        <span class="font-title-md text-body-md text-on-surface font-semibold leading-tight"><?= e($a['pet_name']) ?></span>
                        <span class="font-body-sm text-body-sm text-outline"><?= e($breed !== '' ? $breed : $a['pet_type']) ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="px-space-md py-3.5">
                    <div class="flex flex-col">
                      <span class="text-on-surface font-medium leading-tight"><?= e($a['service_name']) ?></span>
                      <span class="font-body-sm text-body-sm text-outline">₱<?= number_format((float) $a['booked_price'], 2) ?></span>
                    </div>
                  </td>
                  <td class="px-space-md py-3.5">
                    <div class="flex flex-col">
                      <span class="text-on-surface font-medium leading-tight"><?= e($dateLabel) ?></span>
                      <span class="font-body-sm text-body-sm <?= $sty['time'] ?> font-semibold"><?= e($a['time_slot']) ?></span>
                    </div>
                  </td>
                  <td class="px-space-md py-3.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-label-sm text-label-sm font-semibold <?= $sty['badge'] ?>">
                      <span class="w-1.5 h-1.5 rounded-full <?= $sty['dot'] ?>"></span>
                      <?= e(ucfirst($st)) ?>
                    </span>
                  </td>
                  <td class="px-space-lg py-3.5 text-right">
                    <button type="button" title="View details"
                            class="btn-view inline-flex items-center justify-center w-8 h-8 rounded-lg bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-primary transition-colors"
                            data-id-label="<?= e($idLabel) ?>"
                            data-customer="<?= e($a['full_name']) ?>"
                            data-email="<?= e($a['email'] ?? '') ?>"
                            data-phone="<?= e($a['contact_number'] ?? '') ?>"
                            data-pet="<?= e($a['pet_name']) ?>"
                            data-breed="<?= e(($breed !== '' ? $breed . ' ' : '') . '(' . $a['pet_type'] . ')') ?>"
                            data-service="<?= e($a['service_name']) ?>"
                            data-price="<?= e('₱' . number_format((float) $a['booked_price'], 2)) ?>"
                            data-date="<?= e(date('l, M j, Y', strtotime($a['appointment_date']))) ?>"
                            data-time="<?= e($a['time_slot']) ?>"
                            data-status="<?= e(ucfirst($st)) ?>"
                            data-notes="<?= e(trim((string) $a['notes'])) ?>">
                      <span class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="flex p-space-xl flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center text-outline mb-space-sm">
            <span class="material-symbols-outlined text-[32px]">event_busy</span>
          </div>
          <h3 class="font-title-lg text-title-lg text-on-surface">No appointments yet</h3>
          <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mt-1">New bookings will show up here as soon as customers book.</p>
        </div>
      <?php endif; ?>

      <div class="p-space-md bg-surface-container-low/30 flex flex-col sm:flex-row items-center justify-between gap-space-md">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-primary"></span>
          <span class="font-label-sm text-label-sm text-on-surface-variant">Showing the <?= count($recent) ?> most recent of <?= $counts['total'] ?> bookings</span>
        </div>
        <div class="flex items-center gap-space-sm w-full sm:w-auto justify-end">
          <a class="flex items-center gap-1.5 px-space-md py-2 rounded-lg bg-surface-container text-primary font-title-md text-body-md hover:bg-surface-container-high transition-colors font-semibold" href="admin_appointments.php">
            <span>View All Appointments</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </a>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- QUICK VIEW MODAL -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md bg-inverse-surface/40 backdrop-blur-sm" id="viewModal" role="dialog" aria-modal="true" aria-labelledby="viewTitle">
  <div class="bg-surface-container-lowest rounded-xl shadow-xl max-w-md w-full overflow-hidden">
    <div class="px-space-lg py-space-md bg-surface-container-low flex items-center justify-between">
      <div class="flex items-center gap-space-sm">
        <div class="w-8 h-8 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">visibility</span>
        </div>
        <div>
          <h2 class="font-title-md text-title-md text-on-surface" id="viewTitle">Appointment Details</h2>
          <p class="font-label-sm text-label-sm text-on-surface-variant" id="viewId"></p>
        </div>
      </div>
      <button type="button" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg" id="btnViewClose" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>
    <dl class="p-space-lg grid grid-cols-3 gap-y-space-sm gap-x-space-md font-body-md text-body-md">
      <dt class="text-outline font-label-md text-label-md uppercase">Customer</dt><dd class="col-span-2 text-on-surface font-semibold" id="vCustomer"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Contact</dt><dd class="col-span-2 text-on-surface-variant" id="vContact"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Pet</dt><dd class="col-span-2 text-on-surface" id="vPet"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Service</dt><dd class="col-span-2 text-on-surface" id="vService"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Date</dt><dd class="col-span-2 text-on-surface" id="vDate"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Status</dt><dd class="col-span-2 text-on-surface font-semibold" id="vStatus"></dd>
      <dt class="text-outline font-label-md text-label-md uppercase">Notes</dt><dd class="col-span-2 text-on-surface-variant" id="vNotes"></dd>
    </dl>
    <div class="px-space-lg pb-space-lg flex items-center justify-end gap-space-sm">
      <a href="admin_appointments.php" class="px-space-md py-2 rounded-lg bg-surface-container text-primary font-title-md text-body-md hover:bg-surface-container-high transition-colors">Manage appointments</a>
      <button type="button" id="btnViewDone"
              class="px-space-lg py-2 rounded-lg bg-primary text-on-primary font-title-md text-body-md hover:bg-primary-container shadow-sm transition-colors">Close</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('viewModal');
  function open()  { modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function close() { modal.classList.add('hidden');    modal.classList.remove('flex'); }
  function put(id, text) { document.getElementById(id).textContent = text; }

  document.querySelectorAll('.btn-view').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const d = btn.dataset;
      put('viewId',    d.idLabel);
      put('vCustomer', d.customer);
      put('vContact',  [d.phone, d.email].filter(Boolean).join(' · ') || '—');
      put('vPet',      d.pet + ' · ' + d.breed);
      put('vService',  d.service + ' · ' + d.price);
      put('vDate',     d.date + ' at ' + d.time);
      put('vStatus',   d.status);
      put('vNotes',    d.notes || 'No notes');
      open();
    });
  });

  document.getElementById('btnViewClose').addEventListener('click', close);
  document.getElementById('btnViewDone').addEventListener('click', close);
  modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>