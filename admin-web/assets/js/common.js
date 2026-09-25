(function () {
  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);
  }

  function formatDate(value) {
    if (!value) return 'Not Available';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Not Available' : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
  }

  function formatMoney(value, currency = '') {
    if (value === null || value === undefined || value === '') return 'Not Available';
    const amount = Number(value);
    if (!Number.isFinite(amount)) return 'Not Available';
    return `${currency ? `${currency} ` : ''}${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }

  function statusPill(status) {
    const value = String(status || 'unknown').toLowerCase();
    const label = value.replaceAll('_', ' ');
    const tone = ['approved', 'paid', 'delivered', 'active'].includes(value) ? 'success'
      : ['pending', 'preparing', 'under_review'].includes(value) ? 'warning'
        : ['rejected', 'failed', 'cancelled', 'critical'].includes(value) ? 'danger' : 'neutral';
    return `<span class="status-pill status-${tone}"><i></i>${escapeHtml(label)}</span>`;
  }

  function setLoading(title = 'Loading data') {
    document.getElementById('content').innerHTML = `<section class="loading-state"><span class="spinner"></span><strong>${escapeHtml(title)}</strong><span>Please wait while the API responds.</span></section>`;
  }

  function showError(error, retry) {
    const message = error?.status === 403 ? 'You do not have permission to access this page.'
      : error?.status === 404 ? 'This API route is not available.'
        : error?.message || 'Unable to load data. Try again.';
    document.getElementById('content').innerHTML = `<section class="state-card error-state"><span class="state-icon">!</span><h2>Unable to load this view</h2><p>${escapeHtml(message)}</p><button class="button button-secondary" id="retry-button">Try again</button></section>`;
    if (retry) document.getElementById('retry-button').addEventListener('click', retry);
  }

  function showToast(message, kind = 'success') {
    const region = document.getElementById('toast-region');
    const toast = document.createElement('div');
    toast.className = `toast toast-${kind}`;
    toast.textContent = message;
    region.appendChild(toast);
    window.setTimeout(() => toast.remove(), 3600);
  }

  function pageHeading(kicker, title, description, action = '') {
    return `<div class="page-heading"><div><span class="eyebrow">${escapeHtml(kicker)}</span><h1>${escapeHtml(title)}</h1><p>${escapeHtml(description)}</p></div>${action}</div>`;
  }

  function unavailableCard(module, endpoint) {
    return `<section class="unavailable-card"><div class="unavailable-icon">⌁</div><div><span class="eyebrow">BACKEND DEPENDENCY</span><h2>${escapeHtml(module)} API not available</h2><p>This screen will show live data when Laravel exposes the required endpoint. No sample records are displayed.</p><code>${escapeHtml(endpoint)}</code></div><span class="status-pill status-neutral"><i></i>Not connected</span></section>`;
  }

  function pagination(pagination, onPage) {
    const current = Number(pagination?.current_page || 1);
    const last = Number(pagination?.last_page || 1);
    return `<div class="pagination"><span>${Number(pagination?.total || 0).toLocaleString()} records</span><div><button class="button button-small button-secondary" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}>Previous</button><span>Page ${current} of ${last}</span><button class="button button-small button-secondary" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}>Next</button></div></div>`;
  }

  window.ui = Object.freeze({ escapeHtml, formatDate, formatMoney, statusPill, setLoading, showError, showToast, pageHeading, unavailableCard, pagination });
})();
