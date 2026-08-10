document.addEventListener('DOMContentLoaded', function () {
  // Mobile responsive sidebar toggle handler
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
    });
  }

  // Dark/Light theme switcher with localStorage persistence
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const icon = themeToggle.querySelector('i');

    function updateThemeIcon() {
      if (document.body.classList.contains('dark-mode')) {
        icon.classList.remove('fa-regular', 'fa-moon');
        icon.classList.add('fa-solid', 'fa-sun');
      } else {
        icon.classList.remove('fa-solid', 'fa-sun');
        icon.classList.add('fa-regular', 'fa-moon');
      }
    }

    updateThemeIcon();

    themeToggle.addEventListener('click', function () {
      const isDark = document.body.classList.toggle('dark-mode');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      updateThemeIcon();
    });
  }

  // Auto-dismiss alert messages after 4 seconds
  document.querySelectorAll('.alert-auto-dismiss').forEach(function (alertEl) {
    setTimeout(function () {
      const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
      alert.close();
    }, 4000);
  });

  // Confirm dialog prompt for destructive delete form actions
  document.querySelectorAll('.confirm-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const msg = form.dataset.confirmMessage || 'Are you sure you want to delete this record?';
      if (!confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  // Live search filter for client-side tables (TODO: Add input debouncing for large tables)
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

  // Reset modal form state upon hide
  document.querySelectorAll('.modal').forEach(function (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      const form = modalEl.querySelector('form');
      if (form && !form.dataset.keepValues) {
        // Reset form inputs for fresh modal reuse
      }
    });
  });
});

/** Quick action edit modal trigger helper: populates form fields from dataset */
function openEditModal(modalId, data) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  Object.keys(data).forEach(function (key) {
    const field = modal.querySelector('[name="' + key + '"]');
    if (field) {
      field.value = data[key];
      field.dispatchEvent(new Event('change'));
    }
  });
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}
