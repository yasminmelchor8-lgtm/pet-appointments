<?php
$pageTitle  = 'Manage Services | Paws & Bubbles';
$activePage = 'services';

require_once __DIR__ . '/includes/admin_header.php';   // pang check admin + layout
require_once __DIR__ . '/../../backend/services/service_functions.php';

$search   = trim($_GET['search'] ?? '');
$services = getActiveServices($pdo, $search);

// One-time message set by service_handler.php
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<div class="p-gutter lg:p-margin max-w-7xl mx-auto w-full space-y-space-lg">

  <?php if ($flash): ?>
    <?php $isError = ($flash['type'] ?? '') === 'error'; ?>
    <div class="rounded-xl p-space-md flex items-start gap-space-sm shadow-sm <?= $isError ? 'bg-error-container text-on-error-container' : 'bg-surface-container-low text-on-surface' ?>" role="status">
      <span class="material-symbols-outlined text-[22px] flex-shrink-0 <?= $isError ? 'text-error' : 'text-primary' ?>"><?= $isError ? 'error' : 'check_circle' ?></span>
      <span class="font-body-md text-body-md"><?= e($flash['message'] ?? '') ?></span>
    </div>
  <?php endif; ?>

  <!-- Page heading -->
  <div class="space-y-space-xs">
    <div class="flex items-center gap-space-xs text-primary font-label-md text-label-md uppercase tracking-wider">
      <span class="material-symbols-outlined text-[16px]">content_cut</span>
      <span>Catalog &amp; Offerings</span>
    </div>
    <h1 class="font-headline-xl text-headline-xl text-on-surface">Manage Services</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
      Add, view, edit, and update salon grooming packages, custom treatments, and standard rates.
    </p>
  </div>

  <!-- Notice -->
  <div class="bg-surface-container-low text-on-surface rounded-xl p-space-md flex items-start gap-space-sm shadow-sm">
    <span class="material-symbols-outlined text-primary text-[22px] flex-shrink-0 mt-0.5">info</span>
    <div class="flex-1 text-left">
      <span class="font-title-md text-title-md text-on-surface">Notice:</span>
      <span class="font-body-md text-body-md text-on-surface-variant ml-1">
        Service prices are shown in Philippine Peso (₱). Deleting a service will archive it and preserve past appointment records.
      </span>
    </div>
  </div>

  <!-- Toolbar: search + add -->
  <div class="bg-surface-container-lowest rounded-xl p-space-md shadow-sm flex flex-col sm:flex-row items-center justify-between gap-space-md">
    <form method="GET" action="admin_services.php" class="relative w-full sm:w-96" role="search">
      <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px] pointer-events-none">search</span>
      <input class="w-full h-10 pl-10 pr-4 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest transition-colors"
             type="search" name="search" value="<?= e($search) ?>"
             placeholder="Search service name, ID, or detail..." aria-label="Search services">
    </form>
    <div class="flex items-center gap-space-sm w-full sm:w-auto justify-end">
      <?php if ($search !== ''): ?>
        <a href="admin_services.php" class="px-space-md py-2.5 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Clear search</a>
      <?php endif; ?>
      <button type="button" id="btnOpenAddModal"
              class="inline-flex items-center justify-center gap-space-xs px-space-lg py-2.5 rounded-lg bg-primary text-on-primary font-title-md text-title-md hover:bg-primary-container shadow-sm transition-all active:scale-95">
        <span class="material-symbols-outlined text-[20px]">add_circle</span>
        <span>Add New Service</span>
      </button>
    </div>
  </div>

  <!-- Services table -->
  <div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
    <?php if (count($services) > 0): ?>
      <div class="overflow-x-auto w-full">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-surface-container-low text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
              <th class="py-space-md px-space-lg font-semibold">Service ID</th>
              <th class="py-space-md px-space-lg font-semibold">Service Package</th>
              <th class="py-space-md px-space-lg font-semibold">Description</th>
              <th class="py-space-md px-space-lg font-semibold text-right">Price (PHP ₱)</th>
              <th class="py-space-md px-space-lg font-semibold text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="font-body-md text-body-md text-on-surface">
            <?php foreach ($services as $s):
                $idLabel = formatServiceId((int) $s['service_id']);
            ?>
              <tr class="hover:bg-surface-container-low/60 transition-colors">
                <td class="py-space-md px-space-lg whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full font-label-sm text-label-sm bg-surface-container text-primary font-semibold"><?= e($idLabel) ?></span>
                </td>
                <td class="py-space-md px-space-lg whitespace-nowrap">
                  <div class="flex items-center gap-space-sm">
                    <div class="w-9 h-9 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
                      <span class="material-symbols-outlined text-[20px]">content_cut</span>
                    </div>
                    <span class="font-title-md text-title-md text-on-surface"><?= e($s['service_name']) ?></span>
                  </div>
                </td>
                <td class="py-space-md px-space-lg max-w-md">
                  <p class="text-on-surface-variant font-body-md text-body-md line-clamp-2"><?= e($s['description']) ?></p>
                </td>
                <td class="py-space-md px-space-lg whitespace-nowrap text-right">
                  <span class="font-title-lg text-title-lg text-on-surface font-semibold">₱<?= number_format((float) $s['price'], 2) ?></span>
                </td>
                <td class="py-space-md px-space-lg whitespace-nowrap text-right">
                  <div class="inline-flex items-center justify-end gap-space-xs">
                    <button type="button" title="Edit service"
                            class="btn-edit p-2 rounded-lg bg-surface-container-low text-primary hover:bg-surface-container transition-colors"
                            data-id="<?= (int) $s['service_id'] ?>"
                            data-id-label="<?= e($idLabel) ?>"
                            data-name="<?= e($s['service_name']) ?>"
                            data-description="<?= e($s['description']) ?>"
                            data-price="<?= e(number_format((float) $s['price'], 2, '.', '')) ?>">
                      <span class="material-symbols-outlined text-[18px]">edit</span>
                    </button>
                    <button type="button" title="Delete service"
                            class="btn-delete p-2 rounded-lg bg-error-container/40 text-error hover:bg-error-container hover:text-on-error-container transition-colors"
                            data-id="<?= (int) $s['service_id'] ?>"
                            data-id-label="<?= e($idLabel) ?>"
                            data-name="<?= e($s['service_name']) ?>">
                      <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="flex p-space-xl flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-full bg-surface-container-low flex items-center justify-center text-outline mb-space-sm">
          <span class="material-symbols-outlined text-[32px]">inventory_2</span>
        </div>
        <h3 class="font-title-lg text-title-lg text-on-surface">No grooming services found</h3>
        <p class="font-body-md text-body-md text-on-surface-variant max-w-sm mt-1">
          <?= $search !== ''
              ? 'Nothing matches your search. Try a different name or clear the search.'
              : 'There are no services yet. Add your first grooming package.' ?>
        </p>
      </div>
    <?php endif; ?>

    <div class="px-space-lg py-space-md bg-surface-container-low/50 text-on-surface-variant font-label-sm text-label-sm">
      Showing <?= count($services) ?> active salon <?= count($services) === 1 ? 'service' : 'services' ?>
    </div>
  </div>
</div>

<!-- ADD / EDIT SERVICE MODAL -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md bg-inverse-surface/40 backdrop-blur-sm" id="serviceModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="bg-surface-container-lowest rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
    <div class="px-space-lg py-space-md bg-surface-container-low flex items-center justify-between">
      <div class="flex items-center gap-space-sm">
        <div class="w-8 h-8 rounded-lg bg-primary-fixed text-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-[20px]" id="modalIcon">add_circle</span>
        </div>
        <div>
          <h2 class="font-title-md text-title-md text-on-surface" id="modalTitle">Add New Service</h2>
          <p class="font-label-sm text-label-sm text-on-surface-variant" id="modalSubtitle">Set the package details and standard fee</p>
        </div>
      </div>
      <button type="button" class="text-on-surface-variant hover:text-on-surface p-1 rounded-lg" id="btnModalClose" aria-label="Close">
        <span class="material-symbols-outlined text-[22px]">close</span>
      </button>
    </div>

    <form class="p-space-lg space-y-space-md" id="serviceForm" method="POST" action="../../backend/services/service_handler.php">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="service_id" id="formServiceId" value="">

      <div class="space-y-space-xs text-left">
        <label class="block font-label-md text-label-md text-on-surface font-semibold" for="inputServiceName">Service Name</label>
        <input class="w-full h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest transition-colors"
               id="inputServiceName" name="service_name" type="text" maxlength="100" required
               placeholder="e.g. Deluxe Spa &amp; Pawdicure">
      </div>

      <div class="space-y-space-xs text-left">
        <label class="block font-label-md text-label-md text-on-surface font-semibold" for="inputServiceDesc">Description</label>
        <textarea class="w-full p-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest transition-colors resize-none"
                  id="inputServiceDesc" name="description" rows="3" required
                  placeholder="Inclusions, coat requirements, and duration..."></textarea>
      </div>

      <div class="space-y-space-xs text-left">
        <label class="block font-label-md text-label-md text-on-surface font-semibold" for="inputServicePrice">Price (PHP ₱)</label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 font-title-md text-title-md text-primary font-bold">₱</span>
          <input class="w-full h-10 pl-8 pr-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none focus:bg-surface-container-lowest transition-colors"
                 id="inputServicePrice" name="price" type="number" min="0" step="0.01" required placeholder="0.00">
        </div>
        <span class="font-label-sm text-label-sm text-on-surface-variant block">Prices are standard across breed sizes unless marked with a surcharge.</span>
      </div>

      <div class="pt-space-sm flex items-center justify-end gap-space-sm">
        <button type="button" id="btnModalCancel"
                class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Cancel</button>
        <button type="submit"
                class="px-space-lg py-2 rounded-lg bg-primary text-on-primary font-title-md text-body-md hover:bg-primary-container shadow-sm transition-colors">Save Service</button>
      </div>
    </form>
  </div>
</div>

<!-- DELETE CONFIRMATION MODAL -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md bg-inverse-surface/40 backdrop-blur-sm" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
  <form class="bg-surface-container-lowest rounded-xl shadow-xl max-w-md w-full overflow-hidden p-space-lg space-y-space-md"
        method="POST" action="../../backend/services/service_handler.php">
    <input type="hidden" name="action" value="hide">
    <input type="hidden" name="service_id" id="deleteServiceId" value="">

    <div class="flex items-center gap-space-sm">
      <div class="w-10 h-10 rounded-full bg-error-container text-error flex items-center justify-center flex-shrink-0">
        <span class="material-symbols-outlined text-[24px]">warning</span>
      </div>
      <h2 class="font-title-lg text-title-lg text-on-surface" id="deleteTitle">Delete this service?</h2>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant">
      Are you sure you want to remove <span class="font-semibold text-on-surface" id="deleteTargetName"></span>
      (<span class="text-primary" id="deleteTargetId"></span>)? It will no longer be bookable for upcoming appointments.
    </p>
    <div class="flex items-center justify-end gap-space-sm pt-space-xs">
      <button type="button" id="btnDeleteCancel"
              class="px-space-md py-2 rounded-lg bg-surface-container text-on-surface font-title-md text-body-md hover:bg-surface-container-high transition-colors">Cancel</button>
      <button type="submit"
              class="px-space-md py-2 rounded-lg bg-error text-on-error font-title-md text-body-md hover:opacity-90 shadow-sm transition-opacity">Confirm Delete</button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const serviceModal = document.getElementById('serviceModal');
  const deleteModal  = document.getElementById('deleteModal');
  const form         = document.getElementById('serviceForm');

  const formAction    = document.getElementById('formAction');
  const formServiceId = document.getElementById('formServiceId');
  const inputName     = document.getElementById('inputServiceName');
  const inputDesc     = document.getElementById('inputServiceDesc');
  const inputPrice    = document.getElementById('inputServicePrice');
  const modalTitle    = document.getElementById('modalTitle');
  const modalSubtitle = document.getElementById('modalSubtitle');
  const modalIcon     = document.getElementById('modalIcon');

  function openModal(modal)  { modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function closeModal(modal) { modal.classList.add('hidden');    modal.classList.remove('flex'); }

  // Add New Service
  document.getElementById('btnOpenAddModal').addEventListener('click', function () {
    form.reset();
    formAction.value    = 'add';
    formServiceId.value = '';
    modalTitle.textContent    = 'Add New Service';
    modalSubtitle.textContent = 'Set the package details and standard fee';
    modalIcon.textContent     = 'add_circle';
    openModal(serviceModal);
    inputName.focus();
  });

  // Edit Data
  document.querySelectorAll('.btn-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
      formAction.value    = 'edit';
      formServiceId.value = btn.dataset.id;
      inputName.value     = btn.dataset.name;
      inputDesc.value     = btn.dataset.description;
      inputPrice.value    = btn.dataset.price;
      modalTitle.textContent    = 'Edit Service Package';
      modalSubtitle.textContent = 'Updating ' + btn.dataset.idLabel;
      modalIcon.textContent     = 'edit_note';
      openModal(serviceModal);
      inputName.focus();
    });
  });

  // Delete Data 
  document.querySelectorAll('.btn-delete').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.getElementById('deleteServiceId').value = btn.dataset.id;
      document.getElementById('deleteTargetName').textContent = '"' + btn.dataset.name + '"';
      document.getElementById('deleteTargetId').textContent   = btn.dataset.idLabel;
      openModal(deleteModal);
    });
  });

  document.getElementById('btnModalClose').addEventListener('click', function () { closeModal(serviceModal); });
  document.getElementById('btnModalCancel').addEventListener('click', function () { closeModal(serviceModal); });
  document.getElementById('btnDeleteCancel').addEventListener('click', function () { closeModal(deleteModal); });
  [serviceModal, deleteModal].forEach(function (modal) {
    modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(modal); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeModal(serviceModal); closeModal(deleteModal); }
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>