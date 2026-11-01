<?php
date_default_timezone_set('Asia/Manila');   // PH Time ulit

$pageTitle   = 'New Walk-in Booking | Paws & Bubbles';
$activePage  = 'appointments';
$topBarTitle = 'Appointments Management';

require_once __DIR__ . '/includes/admin_header.php';   
require_once __DIR__ . '/../../backend/appointments/appointment_functions.php';
require_once __DIR__ . '/../../backend/services/service_functions.php';

$services = getActiveServices($pdo);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $_SESSION['walkin_old'] ?? [];
unset($_SESSION['walkin_old']);

function old(array $old, string $key): string
{
    return (string) ($old[$key] ?? '');
}

$input  = 'w-full h-11 pl-10 pr-4 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-lg shadow-sm focus:outline-none focus:shadow-[0_0_0_3px_rgba(33,112,228,0.2)] transition-all';
$select = 'w-full h-11 pl-10 pr-10 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-lg shadow-sm focus:outline-none focus:shadow-[0_0_0_3px_rgba(33,112,228,0.2)] appearance-none cursor-pointer transition-all';
$label  = 'block font-title-md text-title-md text-on-surface mb-1.5';
$icon   = 'material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px] pointer-events-none';
$chev   = 'material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[20px]';
?>
<div class="w-full py-10 px-4 sm:px-6 lg:px-8">

  <div class="max-w-3xl mx-auto mb-8">
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-fixed text-primary font-label-md text-label-md mb-3">
      <span class="material-symbols-outlined text-[16px]">directions_walk</span>
      <span>Walk-in Booking</span>
    </div>
    <h1 class="font-headline-xl text-headline-xl text-on-surface tracking-tight">New Walk-in Appointment</h1>
    <p class="font-body-lg text-body-lg text-on-surface-variant mt-1">Book a grooming session for a customer who is at the salon.</p>
  </div>

  <div class="max-w-3xl mx-auto bg-surface-container-lowest rounded-xl p-6 sm:p-10 shadow-sm relative overflow-hidden">
    <div class="absolute -top-12 -right-12 w-48 h-48 bg-primary-fixed/30 rounded-full blur-2xl pointer-events-none"></div>

    <?php if ($flash): ?>
      <div class="relative mb-6 rounded-xl p-space-md flex items-start gap-space-sm bg-error-container text-on-error-container" role="alert">
        <span class="material-symbols-outlined text-[22px] flex-shrink-0 text-error">error</span>
        <span class="font-body-md text-body-md"><?= e($flash['message'] ?? '') ?></span>
      </div>
    <?php endif; ?>

    <div class="relative mb-8 p-4 rounded-xl bg-surface-container-low flex items-start gap-3">
      <span class="material-symbols-outlined text-primary text-[24px] mt-0.5">info</span>
      <p class="font-body-sm text-body-sm text-on-surface leading-relaxed">
        <span class="font-title-md text-title-md text-primary mr-1">Note:</span>
        Walk-in bookings are saved as
        <span class="px-2 py-0.5 rounded-full bg-surface-container font-label-sm text-label-sm text-primary">Confirmed</span>.
        If the email already belongs to a customer, the booking is added to that account. Otherwise a new customer record is created.
      </p>
    </div>

    <form action="../../backend/appointments/appointment_handler.php" method="POST" class="relative space-y-6" id="walkinForm">
      <input type="hidden" name="action" value="walkin">

      <!-- Customer -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="<?= $label ?>" for="customer_name">Customer Name <span class="text-error">*</span></label>
          <div class="relative">
            <span class="<?= $icon ?>">person</span>
            <input class="<?= $input ?>" id="customer_name" name="customer_name" type="text" maxlength="100" required
                   placeholder="e.g. Pedro Reyes" value="<?= e(old($old, 'customer_name')) ?>">
          </div>
        </div>
        <div>
          <label class="<?= $label ?>" for="phone">Phone Number <span class="text-error">*</span></label>
          <div class="relative">
            <span class="<?= $icon ?>">call</span>
            <input class="<?= $input ?>" id="phone" name="phone" type="tel" maxlength="20" required
                   placeholder="e.g. 0917 123 4567" value="<?= e(old($old, 'phone')) ?>">
          </div>
        </div>
      </div>

      <div>
        <label class="<?= $label ?>" for="email">Email <span class="font-body-sm text-body-sm text-outline font-normal">(Optional)</span></label>
        <div class="relative">
          <span class="<?= $icon ?>">mail</span>
          <input class="<?= $input ?>" id="email" name="email" type="email" maxlength="100"
                 placeholder="Only if the customer already has an account" value="<?= e(old($old, 'email')) ?>">
        </div>
      </div>

      <!-- Pet -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="<?= $label ?>" for="pet_name">Pet Name <span class="text-error">*</span></label>
          <div class="relative">
            <span class="<?= $icon ?>">pets</span>
            <input class="<?= $input ?>" id="pet_name" name="pet_name" type="text" maxlength="50" required
                   placeholder="e.g. Barnaby" value="<?= e(old($old, 'pet_name')) ?>">
          </div>
        </div>
        <div>
          <label class="<?= $label ?>" for="pet_type">Pet Type <span class="text-error">*</span></label>
          <div class="relative">
            <span class="<?= $icon ?>">category</span>
            <select class="<?= $select ?>" id="pet_type" name="pet_type" required>
              <option value="" disabled<?= old($old, 'pet_type') === '' ? ' selected' : '' ?>>Select Pet Type</option>
              <?php foreach (['Dog', 'Cat'] as $t): ?>
                <option value="<?= $t ?>"<?= old($old, 'pet_type') === $t ? ' selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
            <span class="<?= $chev ?>">expand_more</span>
          </div>
        </div>
      </div>

      <div>
        <label class="<?= $label ?>" for="breed">Breed <span class="font-body-sm text-body-sm text-outline font-normal">(Optional)</span></label>
        <div class="relative">
          <span class="<?= $icon ?>">badge</span>
          <input class="<?= $input ?>" id="breed" name="breed" type="text" maxlength="50"
                 placeholder="e.g. Golden Retriever, Persian Cat" value="<?= e(old($old, 'breed')) ?>">
        </div>
      </div>

      <!-- Service -->
      <div>
        <label class="<?= $label ?>" for="service_id">Service Package <span class="text-error">*</span></label>
        <div class="relative">
          <span class="<?= $icon ?>">spa</span>
          <select class="<?= $select ?>" id="service_id" name="service_id" required>
            <option value="" disabled<?= (int) old($old, 'service_id') === 0 ? ' selected' : '' ?>>Choose a grooming service...</option>
            <?php foreach ($services as $s): ?>
              <option value="<?= (int) $s['service_id'] ?>"<?= (int) old($old, 'service_id') === (int) $s['service_id'] ? ' selected' : '' ?>>
                <?= e($s['service_name']) ?> (₱<?= number_format((float) $s['price'], 2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <span class="<?= $chev ?>">expand_more</span>
        </div>
        <?php if (count($services) === 0): ?>
          <p class="mt-1 font-label-sm text-label-sm text-error">There are no active services. Add one in Manage Services first.</p>
        <?php endif; ?>
      </div>

      <!-- Date and time -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="<?= $label ?>" for="appointment_date">Appointment Date <span class="text-error">*</span></label>
          <input class="w-full h-11 px-4 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-lg shadow-sm focus:outline-none focus:shadow-[0_0_0_3px_rgba(33,112,228,0.2)] transition-all"
                 id="appointment_date" name="appointment_date" type="date" required
                 min="<?= e(date('Y-m-d')) ?>" value="<?= e(old($old, 'appointment_date')) ?>">
        </div>
        <div>
          <label class="<?= $label ?>" for="time_slot">Time Slot <span class="text-error">*</span></label>
          <div class="relative">
            <span class="<?= $icon ?>">schedule</span>
            <select class="<?= $select ?>" id="time_slot" name="time_slot" required>
              <option value="" disabled<?= old($old, 'time_slot') === '' ? ' selected' : '' ?>>Select Time</option>
              <?php foreach (TIME_SLOTS as $slot): ?>
                <option value="<?= e($slot) ?>"<?= old($old, 'time_slot') === $slot ? ' selected' : '' ?>><?= e($slot) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="<?= $chev ?>">expand_more</span>
          </div>
          <p class="mt-1 font-label-sm text-label-sm text-outline" id="slotHint">Pick a date to see which times are free.</p>
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="<?= $label ?>" for="notes">Additional Notes <span class="font-body-sm text-body-sm text-outline font-normal">(Optional)</span></label>
        <textarea class="w-full p-4 bg-surface-container-lowest text-on-surface font-body-md text-body-md rounded-lg shadow-sm focus:outline-none focus:shadow-[0_0_0_3px_rgba(33,112,228,0.2)] transition-all resize-y"
                  id="notes" name="notes" rows="3"
                  placeholder="Any allergies, behavioral notes, or special instructions for our groomers..."><?= e(old($old, 'notes')) ?></textarea>
      </div>

      <div class="pt-4 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 sm:gap-4">
        <a class="w-full sm:w-auto px-6 py-2.5 rounded-lg font-title-md text-title-md text-on-surface-variant hover:text-on-surface bg-surface-container-low hover:bg-surface-container text-center transition-all"
           href="admin_appointments.php">Cancel</a>
        <button class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-2.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-title-md text-title-md shadow-md hover:shadow-lg transition-all active:scale-[0.99]" type="submit">
          <span class="material-symbols-outlined text-[20px]">calendar_month</span>
          <span>Book Appointment</span>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const dateInput = document.getElementById('appointment_date');
  const slotSelect = document.getElementById('time_slot');
  const hint = document.getElementById('slotHint');

  // Ask the server which slots are free on the chosen date, and grey out the taken ones
  function loadSlots() {
    const date = dateInput.value;
    if (!date) return;

    fetch('../../backend/appointments/available_slots.php?date=' + encodeURIComponent(date))
      .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
      .then(function (data) {
        let freeCount = 0;
        Array.from(slotSelect.options).forEach(function (opt) {
          if (opt.value === '') return;
          const free = data.available.indexOf(opt.value) !== -1;
          opt.disabled = !free;
          opt.textContent = free ? opt.value : opt.value + ' (taken)';
          if (free) freeCount++;
        });
        // If the chosen time just became unavailable, make the admin pick again
        if (slotSelect.selectedOptions.length && slotSelect.selectedOptions[0].disabled) {
          slotSelect.value = '';
        }
        hint.textContent = freeCount === 0
          ? 'No free times on this date. Please pick another day.'
          : freeCount + ' of ' + (slotSelect.options.length - 1) + ' times are free.';
      })
      .catch(function () {
        hint.textContent = 'Could not check availability. The server will still block taken times.';
      });
  }

  dateInput.addEventListener('change', loadSlots);
  loadSlots();   // also runs when the form comes back with a date after an error
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>