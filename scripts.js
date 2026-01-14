/**
 * Music Festival Management System - JavaScript
 * Handles interactivity, form validation, and Bootstrap components
 */

// ============================================================
// SESSION & LOCAL STORAGE HELPERS
// ============================================================

/**
 * Store user session data in localStorage
 */
function storeSessionData(key, value) {
  try {
    localStorage.setItem('mfs_' + key, JSON.stringify(value));
  } catch (e) {
    console.warn('localStorage not available:', e);
  }
}

/**
 * Retrieve user session data from localStorage
 */
function getSessionData(key) {
  try {
    const data = localStorage.getItem('mfs_' + key);
    return data ? JSON.parse(data) : null;
  } catch (e) {
    console.warn('Error retrieving session data:', e);
    return null;
  }
}

/**
 * Remove user session data from localStorage
 */
function removeSessionData(key) {
  try {
    localStorage.removeItem('mfs_' + key);
  } catch (e) {
    console.warn('Error removing session data:', e);
  }
}

/**
 * Clear all session data
 */
function clearAllSessionData() {
  try {
    const keys = Object.keys(localStorage);
    keys.forEach(key => {
      if (key.startsWith('mfs_')) {
        localStorage.removeItem(key);
      }
    });
  } catch (e) {
    console.warn('Error clearing session data:', e);
  }
}

// ============================================================
// FORM VALIDATION & SUBMISSION
// ============================================================

/**
 * Validate email format
 */
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

/**
 * Validate password strength
 */
function validatePassword(password) {
  return password.length >= 6;
}

/**
 * Validate form fields
 */
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return false;

  let isValid = true;
  const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

  inputs.forEach(input => {
    if (!input.value.trim()) {
      input.classList.add('is-invalid');
      isValid = false;
    } else {
      input.classList.remove('is-invalid');
    }
  });

  return isValid;
}

/**
 * Show alert message with Bootstrap styling
 */
function showAlert(message, type = 'info') {
  const alertTypes = ['success', 'error', 'warning', 'info'];
  const alertType = alertTypes.includes(type) ? type : 'info';

  const alertDiv = document.createElement('div');
  alertDiv.className = `alert ${alertType}`;
  alertDiv.textContent = message;
  alertDiv.style.margin = '1rem 0';
  alertDiv.style.animation = 'slideIn 0.3s ease';

  const container = document.querySelector('.container') || document.body;
  container.insertBefore(alertDiv, container.firstChild);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}

/**
 * Confirm action with modal
 */
function confirmAction(title = 'Confirm', message = 'Are you sure?', onConfirm = null, onCancel = null) {
  const backdrop = document.createElement('div');
  backdrop.className = 'modal-backdrop';
  backdrop.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
  `;

  const modal = document.createElement('div');
  modal.className = 'modal-content';
  modal.style.cssText = `
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    text-align: center;
  `;

  modal.innerHTML = `
    <h3 style="color: var(--primary-color); margin-bottom: 1rem;">${title}</h3>
    <p style="margin-bottom: 1.5rem; opacity: 0.8;">${message}</p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
      <button class="btn btn-secondary" onclick="this.closest('.modal-backdrop').remove();">Cancel</button>
      <button class="btn btn-danger" onclick="this.closest('.modal-backdrop').remove(); if(window.confirmCallback) window.confirmCallback();">Confirm</button>
    </div>
  `;

  backdrop.appendChild(modal);
  document.body.appendChild(backdrop);

  window.confirmCallback = onConfirm;
}

// ============================================================
// NAVIGATION & ROUTING
// ============================================================

/**
 * Navigate to a page with loading indicator
 */
function navigateTo(url) {
  const loader = document.createElement('div');
  loader.className = 'loader';
  loader.style.cssText = `
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2000;
    display: none;
  `;
  document.body.appendChild(loader);

  loader.style.display = 'block';
  setTimeout(() => {
    window.location.href = url;
  }, 300);
}

/**
 * Redirect user based on role
 */
function redirectByRole(role) {
  const dashboards = {
    'admin': 'admin_dashboard.php',
    'judge': 'judge_portal.php',
    'participant': 'user_dashboard.php'
  };

  const dashboard = dashboards[role] || 'index.php';
  navigateTo(dashboard);
}

// ============================================================
// TABLE OPERATIONS
// ============================================================

/**
 * Sort table by column
 */
function sortTableByColumn(tableId, columnIndex, ascending = true) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('tbody tr'));
  rows.sort((a, b) => {
    const aVal = a.children[columnIndex].textContent.trim();
    const bVal = b.children[columnIndex].textContent.trim();

    if (!isNaN(aVal) && !isNaN(bVal)) {
      return ascending ? aVal - bVal : bVal - aVal;
    }
    return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
  });

  const tbody = table.querySelector('tbody');
  rows.forEach(row => tbody.appendChild(row));
}

/**
 * Filter table by search term
 */
function filterTable(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);

  if (!input || !table) return;

  const filter = input.value.toUpperCase();
  const rows = table.querySelectorAll('tbody tr');

  rows.forEach(row => {
    const text = row.textContent.toUpperCase();
    row.style.display = text.includes(filter) ? '' : 'none';
  });
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
  const table = document.getElementById(tableId);
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll('tr');

  rows.forEach(row => {
    const cols = row.querySelectorAll('td, th');
    const csvRow = Array.from(cols).map(col => {
      let text = col.textContent.trim();
      if (text.includes(',') || text.includes('"') || text.includes('\n')) {
        text = '"' + text.replace(/"/g, '""') + '"';
      }
      return text;
    });
    csv.push(csvRow.join(','));
  });

  downloadFile(csv.join('\n'), filename, 'text/csv');
}

/**
 * Download file utility
 */
function downloadFile(content, filename, type) {
  const element = document.createElement('a');
  element.setAttribute('href', 'data:' + type + ';charset=utf-8,' + encodeURIComponent(content));
  element.setAttribute('download', filename);
  element.style.display = 'none';
  document.body.appendChild(element);
  element.click();
  document.body.removeChild(element);
}

// ============================================================
// FORM HELPERS
// ============================================================

/**
 * Reset form fields
 */
function resetForm(formId) {
  const form = document.getElementById(formId);
  if (form) {
    form.reset();
    form.querySelectorAll('.is-invalid').forEach(el => {
      el.classList.remove('is-invalid');
    });
  }
}

/**
 * Disable form submission
 */
function disableFormSubmit(formId) {
  const form = document.getElementById(formId);
  if (!form) return;

  const button = form.querySelector('button[type="submit"]');
  if (button) {
    button.disabled = true;
    button.textContent = 'Processing...';
  }
}

/**
 * Enable form submission
 */
function enableFormSubmit(formId, buttonText = 'Submit') {
  const form = document.getElementById(formId);
  if (!form) return;

  const button = form.querySelector('button[type="submit"]');
  if (button) {
    button.disabled = false;
    button.textContent = buttonText;
  }
}

// ============================================================
// DATETIME HELPERS
// ============================================================

/**
 * Format date to readable string
 */
function formatDate(dateString) {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('en-US', options);
}

/**
 * Format time to readable string
 */
function formatTime(dateString) {
  const options = { hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleTimeString('en-US', options);
}

/**
 * Get time elapsed since a date
 */
function timeAgo(dateString) {
  const date = new Date(dateString);
  const seconds = Math.floor((new Date() - date) / 1000);

  let interval = seconds / 31536000;
  if (interval > 1) return Math.floor(interval) + ' years ago';

  interval = seconds / 2592000;
  if (interval > 1) return Math.floor(interval) + ' months ago';

  interval = seconds / 86400;
  if (interval > 1) return Math.floor(interval) + ' days ago';

  interval = seconds / 3600;
  if (interval > 1) return Math.floor(interval) + ' hours ago';

  interval = seconds / 60;
  if (interval > 1) return Math.floor(interval) + ' minutes ago';

  return Math.floor(seconds) + ' seconds ago';
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Debounce function for search
 */
function debounce(func, delay) {
  let timeoutId;
  return function (...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(this, args), delay);
  };
}

/**
 * Check if user is logged in (from session)
 */
function isUserLoggedIn() {
  return !!getSessionData('user_id');
}

/**
 * Get current user info from session
 */
function getCurrentUserInfo() {
  return {
    userId: getSessionData('user_id'),
    fullName: getSessionData('full_name'),
    role: getSessionData('role'),
    email: getSessionData('email')
  };
}

/**
 * Format currency
 */
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount);
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    showAlert('Copied to clipboard!', 'success');
  }).catch(() => {
    showAlert('Failed to copy', 'error');
  });
}

// ============================================================
// DOM READY INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
  // Initialize tooltips and popovers
  const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
  tooltips.forEach(el => {
    el.title = el.getAttribute('data-title') || '';
  });

  // Add smooth scroll behavior for internal links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Remove alerts after timeout
  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.remove();
    }, 5000);
  });
});