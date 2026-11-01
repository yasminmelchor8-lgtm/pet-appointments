<?php
$pageTitle   = 'Appointments | Paws & Bubbles';
$activePage  = 'appointments';
$topBarTitle = 'Appointments Management';
$showAddAppointment = true;

require_once __DIR__ . '/includes/admin_header.php';   // admin check + layout
require_once __DIR__ . '/../../backend/appointments/appointment_functions.php';

const PER_PAGE = 6;

// What the admin typed 
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
if (!in_array($status, APPOINTMENT_STATUSES, true)) {
    $status = '';
}

$total      = countAppointments($pdo, $search, $status);
$totalPages = max(1, (int) ceil($total / PER_PAGE));
$page       = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$offset     = ($page - 1) * PER_PAGE;

$appointments = getAppointments($pdo, $search, $status, PER_PAGE, $offset);
$counts       = getAppointmentCounts($pdo);

$from = $total === 0 ? 0 : $offset + 1;
$to   = $offset + count($appointments);

// One-time message set by appointment_handler.php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function pageUrl(int $p, string $search, string $status): string
{
    $q = [];
    if ($search !== '') $q['search'] = $search;
    if ($status !== '') $q['status'] = $status;
    if ($p > 1)         $q['page']   = $p;
    return 'admin_appointments.php' . ($q ? '?' . http_build_query($q) : '');
}

// Badge colours for each status (same palette as the design)
$badgeClass = [
    'pending'   => 'bg-secondary-fixed text-on-secondary-fixed-variant',
    'confirmed' => 'bg-primary-fixed text-primary',
    'completed' => 'bg-surface-container-high text-on-surface',
    'cancelled' => 'bg-error-container text-on-error-container',
];
?>
<div class="flex flex-col w-full">

  <!-- Heading -->
  <div class="px-margin pt-space-lg pb-space-sm">
    <span class="font-headline-lg text-headline-lg text-on-surface">Daily Schedule &amp; Bookings</span>
    <p class="font-body-md text-body-md text-on-surface-variant mt-1">View, edit, or cancel customer pet grooming appointments.</p>
  </div>

  <?php if ($flash): ?>
    <?php $isError = ($flash['type'] ?? '') === 'error'; ?>
    <div class="px-margin py-space-sm">
      <div class="rounded-xl p-space-md flex items-start gap-space-sm shadow-sm <?= $isError ? 'bg-error-container text-on-error-container' : 'bg-surface-container-low text-on-surface' ?>" role="status">
        <span class="material-symbols-outlined text-[22px] flex-shrink-0 <?= $isError ? 'text-error' : 'text-primary' ?>"><?= $isError ? 'error' : 'check_circle' ?></span>
        <span class="font-body-md text-body-md"><?= e($flash['message'] ?? '') ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- KPI cards -->
  <div class="px-margin py-space-sm">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
      <div class="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex items-start justify-between">
        <div class="flex flex-col gap-1">
          <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Total Appointments</span>
          <span class="font-headline-xl text-headline-xl text-on-surface font-bold mt-1"><?= $counts['total'] ?></span>
          <span class="font-body-sm text-body-sm text-on-surface-variant mt-1">All bookings</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center text-primary shrink-0">
          <span class="material-symbols-outlined text-headline-md">calendar_today</span>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex items-start justify-between">
        <div class="flex flex-col gap-1">
          <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Pending Approval</span>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="font-headline-xl text-headline-xl text-secondary font-bold"><?= $counts['pending'] ?></span>
            <?php if ($counts['pending'] > 0): ?>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary-fixed text-on-secondary-fixed-variant font-label-sm text-label-sm font-semibold">Needs Review</span>
            <?php endif; ?>
          </div>
          <span class="font-body-sm text-body-sm text-on-surface-variant mt-1">Waiting for confirmation</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center text-secondary shrink-0">
          <span class="material-symbols-outlined text-headline-md">pending_actions</span>
        </div>
      </div>

      <div class="bg-surface-container-lowest rounded-2xl p-space-lg shadow-sm flex items-start justify-between">
        <div class="flex flex-col gap-1">
          <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline font-semibold">Confirmed Bookings</span>
          <div class="flex items-baseline gap-2 mt-1">
            <span class="font-headline-xl text-headline-xl text-primary font-bold"><?= $counts['confirmed'] ?></span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-primary-fixed text-primary font-label-sm text-label-sm font-semibold">Scheduled</span>
          </div>
          <span class="font-body-sm text-body-sm text-on-surface-variant mt-1">Ready for the salon</span>
        </div>
        <div class="w-12 h-12 rounded-xl bg-surface-container-high flex items-center justify-center text-primary shrink-0">
          <span class="material-symbols-outlined text-headline-md">check_circle</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Table card -->
  <div class="px-margin py-space-md flex flex-col gap-space-md">
    <div class="bg-surface-container-lowest rounded-2xl shadow-sm overflow-hidden flex flex-col">

      <!-- Search + status filter -->
      <form method="GET" action="admin_appointments.php" class="p-space-lg flex flex-col sm:flex-row sm:items-center gap-space-sm" role="search">
        <div class="relative min-w-[240px] max-w-sm flex-1">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-title-md pointer-events-none">search</span>
          <input class="w-full h-10 pl-10 pr-space-md rounded-xl bg-surface-container-low text-on-surface placeholder:text-outline font-body-sm text-body-sm focus:outline-none focus:bg-surface-container transition-all"
                 type="search" name="search" value="<?= e($search) ?>" placeholder="Search customer or pet name..." aria-label="Search appointments">
        </div>
        <div class="relative">
          <select name="status" aria-label="Filter by status" onchange="this.form.submit()"
                  class="h-10 pl-3 pr-8 rounded-xl bg-surface-container-low text-on-surface font-body-sm text-body-sm appearance-none focus:outline-none focus:bg-surface-container cursor-pointer transition-all">
            <option value="">Filter: All Statuses</option>
            <?php foreach (APPOINTMENT_STATUSES as $s): ?>
              <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
          <span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-body-md">expand_more</span>
        </div>
        <?php if ($search !== '' || $status !== ''): ?>
          <a href="admin_appointments.php" class="px-space-md py-2.5 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Clear</a>
        <?php endif; ?>
      </form>

      <?php if (count($appointments) > 0): ?>
        <div class="overflow-x-auto w-full">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-surface-container-low text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">ID</th>
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">Customer Name</th>
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">Pet &amp; Breed</th>
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">Service &amp; Price (₱)</th>
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">Date &amp; Time</th>
                <th class="py-3.5 px-5 font-semibold text-left" scope="col">Status</th>
                <th class="py-3.5 px-5 font-semibold text-center pr-6" scope="col">Actions</th>
              </tr>
            </thead>
            <tbody class="text-on-surface font-body-md text-body-md">
              <?php foreach ($appointments as $a):
                  $id       = (int) $a['appointment_id'];
                  $idLabel  = formatAppointmentId($id);
                  $st       = $a['status'];
                  $isCancel = ($st === 'cancelled');
                  $petIcon  = $isCancel ? 'cancel' : 'pets';
                  $petColor = $isCancel ? 'text-error' : (strtolower($a['pet_type']) === 'cat' ? 'text-secondary' : 'text-primary');
                  $breed    = trim((string) $a['breed']);
                  $petLine  = ($breed !== '' ? $breed . ' ' : '') . '(' . $a['pet_type'] . ')';
              ?>
                <tr class="hover:bg-surface-container-low/60 transition-colors">
                  <td class="py-4 px-5 font-semibold text-outline text-body-sm"><?= e($idLabel) ?></td>
                  <td class="py-4 px-5">
                    <div class="flex flex-col">
                      <span class="font-title-md text-body-md text-on-surface font-semibold"><?= e($a['full_name']) ?></span>
                      <span class="font-label-sm text-label-sm text-outline"><?= e($a['contact_number'] ?? '') ?></span>
                    </div>
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-2">
                      <span class="material-symbols-outlined <?= $petColor ?> text-title-md"><?= $petIcon ?></span>
                      <div class="flex flex-col">
                        <span class="font-title-md text-body-md text-on-surface font-medium"><?= e($a['pet_name']) ?></span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant"><?= e($petLine) ?></span>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex flex-col">
                      <span class="font-title-md text-body-md text-on-surface font-medium"><?= e($a['service_name']) ?></span>
                      <span class="font-label-sm text-label-sm text-primary font-semibold">₱<?= number_format((float) $a['booked_price'], 2) ?></span>
                    </div>
                  </td>
                  <td class="py-4 px-5">
                    <div class="flex flex-col">
                      <span class="font-title-md text-body-md text-on-surface font-medium"><?= e(date('M j, Y', strtotime($a['appointment_date']))) ?></span>
                      <span class="font-label-sm text-label-sm text-outline"><?= e($a['time_slot']) ?></span>
                    </div>
                  </td>
                  <td class="py-4 px-5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label-md text-label-md font-semibold <?= $badgeClass[$st] ?? 'bg-surface-container text-on-surface' ?>"><?= e(ucfirst($st)) ?></span>
                  </td>
                  <td class="py-4 px-5 text-center pr-6">
                    <?php if ($isCancel): ?>
                      <span class="text-label-sm text-outline italic">Cancelled</span>
                    <?php else: ?>
                      <div class="flex items-center justify-center gap-2">
                        <button type="button" title="Edit appointment"
                                class="btn-edit inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label-md text-label-md transition-colors"
                                data-id="<?= $id ?>"
                                data-id-label="<?= e($idLabel) ?>"
                                data-customer="<?= e($a['full_name']) ?>"
                                data-pet="<?= e($a['pet_name']) ?>"
                                data-status="<?= e($st) ?>">
                          <span class="material-symbols-outlined text-body-sm">edit</span><span>Edit</span>
                        </button>
                        <?php if ($st !== 'completed'): ?>
                          <button type="button" title="Cancel appointment"
                                  class="btn-cancel inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-error-container hover:bg-error text-on-error-container hover:text-on-error font-label-md text-label-md transition-colors"
                                  data-id="<?= $id ?>"
                                  data-id-label="<?= e($idLabel) ?>"
                                  data-customer="<?= e($a['full_name']) ?>"
                                  data-pet="<?= e($a['pet_name']) ?>">
                            <span class="material-symbols-outlined text-body-sm">delete</span><span>Cancel</span>
                          </button>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
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
          <h3 class="font-title-lg text-title-lg text-on-surface">No appointments found</h3>
          <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mt-1">
            <?= ($search !== '' || $status !== '')
                ? 'Nothing matches your search or filter. Try something else or clear them.'
                : 'There are no bookings yet. They will show up here once customers book.' ?>
          </p>
        </div>
      <?php endif; ?>

      <!-- Footer: count + page links -->
      <div class="px-space-lg py-space-md bg-surface-container-lowest flex flex-col sm:flex-row sm:items-center justify-between gap-space-md">
        <span class="font-body-sm text-body-sm text-on-surface-variant">
          Showing <strong class="text-on-surface font-semibold"><?= $from ?> to <?= $to ?></strong>
          of <strong class="text-on-surface font-semibold"><?= $total ?></strong> appointments
        </span>
        <?php if ($totalPages > 1): ?>
          <nav class="flex items-center gap-1" aria-label="Pages">
            <?php if ($page > 1): ?>
              <a href="<?= e(pageUrl($page - 1, $search, $status)) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors" aria-label="Previous page">
                <span class="material-symbols-outlined text-title-md">chevron_left</span>
              </a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
              <a href="<?= e(pageUrl($p, $search, $status)) ?>"
                 class="w-8 h-8 rounded-lg flex items-center justify-center font-label-md text-label-md <?= $p === $page ? 'bg-primary text-on-primary font-semibold shadow-sm' : 'text-on-surface hover:bg-surface-container transition-colors' ?>"
                 <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="<?= e(pageUrl($page + 1, $search, $status)) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors" aria-label="Next page">
                <span class="material-symbols-outlined text-title-md">chevron_right</span>
              </a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- EDIT (CHANGE STATUS) MODAL -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md bg-inverse-surface/40 backdrop-blur-sm" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
  <form class="bg-surface-container-lowest rounded-xl shadow-xl max-w-md w-full overflow-hidden" method="POST" action="../../backend/appointments/appointment_handler.php">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="appointment_id" id="editId" value="">
    <input type="hidden" name="search" value="<?= e($search) ?>">
    <input type="hidden" name="filter_status" value="<?= e($status) ?>">
    <input type="hidden" name="page" value="<?= $page ?>">

    <div class="px-space-lg py-space-md bg-surface-container-low flex items-center justify-between">
      <div class="flex items-center gap-space-sm">
        <div class="w-8 h-8 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]">edit_note</span>
        </div>
        <div>
          <h2 class="font-title-md text-title-md text-on-surface" id="editTitle">Update Appointment</h2>
          <p class="font-label-sm text-label-sm text-on-surface-variant" id="editSubtitle"></p>
        </div>
      </div>
      <button type="button" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg" id="btnEditClose" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>

    <div class="p-space-lg space-y-space-md">
      <div class="space-y-space-xs text-left">
        <label class="block font-label-md text-label-md text-on-surface font-semibold" for="editStatus">Status</label>
        <select class="w-full h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest transition-colors"
                id="editStatus" name="status" required>
          <?php foreach (APPOINTMENT_STATUSES as $s): ?>
            <option value="<?= e($s) ?>"><?= e(ucfirst($s)) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="font-label-sm text-label-sm text-on-surface-variant block">Choosing Cancelled frees the time slot for other customers.</span>
      </div>
      <div class="pt-space-sm flex items-center justify-end gap-space-sm">
        <button type="button" id="btnEditCancel"
                class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Close</button>
        <button type="submit"
                class="px-space-lg py-2 rounded-lg bg-primary text-on-primary font-title-md text-body-md hover:bg-primary-container shadow-sm transition-colors">Save Status</button>
      </div>
    </div>
  </form>
</div>

<!-- CANCEL CONFIRMATION MODAL -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md bg-inverse-surface/40 backdrop-blur-sm" id="cancelModal" role="dialog" aria-modal="true" aria-labelledby="cancelTitle">
  <form class="bg-surface-container-lowest rounded-xl shadow-xl max-w-md w-full overflow-hidden p-space-lg space-y-space-md" method="POST" action="../../backend/appointments/appointment_handler.php">
    <input type="hidden" name="action" value="cancel">
    <input type="hidden" name="appointment_id" id="cancelId" value="">
    <input type="hidden" name="search" value="<?= e($search) ?>">
    <input type="hidden" name="filter_status" value="<?= e($status) ?>">
    <input type="hidden" name="page" value="<?= $page ?>">

    <div class="flex items-center gap-space-sm">
      <div class="w-10 h-10 rounded-full bg-error-container text-error flex items-center justify-center flex-shrink-0">
        <span class="material-symbols-outlined text-[24px]">warning</span>
      </div>
      <h2 class="font-title-lg text-title-lg text-on-surface" id="cancelTitle">Cancel this appointment?</h2>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant">
      Are you sure you want to cancel <span class="font-semibold text-on-surface" id="cancelTarget"></span>
      (<span class="text-primary" id="cancelTargetId"></span>)? The time slot will be free for other customers.
    </p>
    <div class="flex items-center justify-end gap-space-sm pt-space-xs">
      <button type="button" id="btnCancelClose"
              class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Keep it</button>
      <button type="submit"
              class="px-space-md py-2 rounded-lg bg-error text-on-error font-title-md text-body-md hover:opacity-90 shadow-sm transition-opacity">Confirm Cancel</button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal   = document.getElementById('editModal');
  const cancelModal = document.getElementById('cancelModal');

  function openModal(modal)  { modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function closeModal(modal) { modal.classList.add('hidden');    modal.classList.remove('flex'); }

  // Edit: fill the modal from the data-* attributes on the button
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('editId').value = btn.dataset.id;
      document.getElementById('editStatus').value = btn.dataset.status;
      document.getElementById('editSubtitle').textContent =
        btn.dataset.idLabel + ' · ' + btn.dataset.customer + ' · ' + btn.dataset.pet;
      openModal(editModal);
    });
  });

  // Cancel: ask first
  document.querySelectorAll('.btn-cancel').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('cancelId').value = btn.dataset.id;
      document.getElementById('cancelTarget').textContent = btn.dataset.customer + '\u2019s appointment for ' + btn.dataset.pet;
      document.getElementById('cancelTargetId').textContent = btn.dataset.idLabel;
      openModal(cancelModal);
    });
  });

  // Close buttons, backdrop click, Escape key
  document.getElementById('btnEditClose').addEventListener('click',   function () { closeModal(editModal); });
  document.getElementById('btnEditCancel').addEventListener('click',  function () { closeModal(editModal); });
  document.getElementById('btnCancelClose').addEventListener('click', function () { closeModal(cancelModal); });
  [editModal, cancelModal].forEach(function (modal) {
    modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(modal); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeModal(editModal); closeModal(cancelModal); }
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>