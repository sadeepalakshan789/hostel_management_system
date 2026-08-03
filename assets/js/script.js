document.addEventListener('DOMContentLoaded', function () {
  // Mobile sidebar toggle
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
    });
  }

  // Auto-dismiss alerts after 4 seconds
  document.querySelectorAll('.alert-auto-dismiss').forEach(function (alertEl) {
    setTimeout(function () {
      const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
      alert.close();
    }, 4000);
  });

  // Client-side confirmation for delete forms
  document.querySelectorAll('.confirm-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const msg = form.dataset.confirmMessage || 'Are you sure you want to delete this record?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Live client-side search filter for tables with data-search-target
  document.querySelectorAll('[data-live-search]').forEach(function (input) {
    input.addEventListener('keyup', function () {
      const targetSelector = input.getAttribute('data-live-search');
      const table = document.querySelector(targetSelector);
      if (!table) return;
      const filter = input.value.toLowerCase();
      table.querySelectorAll('tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
      });
    });
  });

  // Reset a modal's form when it is hidden (clears validation state for reuse)
  document.querySelectorAll('.modal').forEach(function (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      const form = modalEl.querySelector('form');
      if (form && !form.dataset.keepValues) {
        // Only reset forms explicitly marked as resettable (Create forms)
      }
    });
  });
});

/** Populate and open an edit modal from a table row's data-* attributes */
function openEditModal(modalId, data) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  Object.keys(data).forEach(function (key) {
    const field = modal.querySelector('[name="' + key + '"]');
    if (field) field.value = data[key];
  });
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}
