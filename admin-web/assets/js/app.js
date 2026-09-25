(function () {
  const pages = [
    ['dashboard', 'Dashboard', 'Overview', '▦'],
    ['farmers', 'Farmers', 'Marketplace', '♧'],
    ['verifications', 'Verification queue', 'Marketplace', '✓'],
    ['products', 'Products', 'Marketplace', '◈'],
    ['orders', 'Orders', 'Commerce', '▤'],
    ['payments', 'Payments', 'Commerce', '＄'],
    ['escrow', 'Escrow', 'Commerce', '◉'],
    ['shipments', 'Shipments', 'Logistics', '⇢'],
    ['fleet', 'Reefer fleet', 'Logistics', '▱'],
    ['telemetry', 'Cold chain', 'Logistics', '⌁'],
    ['hubs', 'Cold hubs', 'Logistics', '⌂'],
    ['grn', 'Goods received', 'Documents', '▧'],
    ['compliance', 'Compliance', 'Documents', '◎'],
    ['audit-logs', 'Audit logs', 'System', '≋'],
    ['users', 'Users', 'System', '♙'],
    ['settings', 'Settings', 'System', '⚙'],
  ];

  const missingApis = {
    orders: ['Orders', 'GET /api/admin/orders'],
    payments: ['Payments', 'GET /api/admin/payments'],
    escrow: ['Escrow', 'GET /api/admin/escrow'],
    shipments: ['Shipments', 'GET /api/admin/shipments'],
    fleet: ['Reefer fleet', 'GET /api/admin/reefer-vehicles'],
    telemetry: ['Cold-chain monitoring', 'GET /api/admin/telemetry/alerts'],
    hubs: ['Cold hubs', 'GET /api/admin/cold-hubs'],
    grn: ['Goods received notes', 'GET /api/admin/grn'],
    compliance: ['Compliance', 'GET /api/admin/compliance'],
    'audit-logs': ['Audit logs', 'GET /api/admin/audit-logs'],
    users: ['User management', 'GET /api/admin/users'],
    settings: ['Settings', 'GET /api/admin/settings'],
  };

  let currentUser;
  let farmerPage = 1;
  let verificationPage = 1;
  let productPage = 1;
  let farmerSearch = '';
  let verificationStatus = 'pending';
  let productStatus = 'pending';
  let searchTimer;

  const nav = document.getElementById('navigation');
  const content = document.getElementById('content');
  const pageTitle = document.getElementById('topbar-title');

  function renderNavigation(active) {
    let group = '';
    nav.innerHTML = pages.map(([id, title, section, icon]) => {
      const heading = group !== section ? `<div class="nav-section">${ui.escapeHtml(section)}</div>` : '';
      group = section;
      return `${heading}<a class="nav-link ${id === active ? 'active' : ''}" href="#${id}" ${id === active ? 'aria-current="page"' : ''}><span class="nav-icon">${icon}</span><span>${ui.escapeHtml(title)}</span>${id === 'verifications' ? '<span class="nav-live-dot"></span>' : ''}</a>`;
    }).join('');
  }

  function metricCard(label, value, hint, icon, unavailable = false) {
    return `<article class="metric-card ${unavailable ? 'metric-unavailable' : ''}"><div class="metric-top"><span>${ui.escapeHtml(label)}</span><span class="metric-icon">${icon}</span></div><strong>${unavailable ? '—' : ui.escapeHtml(value)}</strong><small>${ui.escapeHtml(hint)}</small></article>`;
  }

  async function loadDashboard() {
    ui.setLoading('Loading platform overview');
    try {
      const result = await api.get('/admin/dashboard');
      const metrics = result?.metrics || {};
      const recent = Array.isArray(result?.recent_orders) ? result.recent_orders : [];
      content.innerHTML = `${ui.pageHeading('OPERATIONS OVERVIEW', 'Good day, ' + (currentUser?.display_name || currentUser?.name || 'Administrator'), 'Here is the latest activity available from the Phum Kasikor API.', '<span class="live-label"><i></i>Live API data</span>')}
        <section class="metric-grid">
          ${metricCard('Total orders', metrics.orders, 'All recorded orders', '▤')}
          ${metricCard('Farmers', metrics.farmers, 'Registered farmer accounts', '♧')}
          ${metricCard('Pending verification', metrics.pending_verifications, 'Farmer applications to review', '✓')}
          ${metricCard('Product approvals', metrics.pending_products, 'Products awaiting review', '◈')}
          ${metricCard('Pending orders', metrics.pending_orders, 'Orders in pending state', '◷')}
          ${metricCard('Paid revenue', ui.formatMoney(metrics.paid_revenue), 'Currency not provided by API', '＄')}
          ${metricCard('GMV by currency', '', 'Requires currency-aware analytics API', '↗', true)}
          ${metricCard('Cold hubs & alerts', '', 'Logistics API not available', '⌁', true)}
          ${metricCard('Escrow & settlements', '', 'Settlement API not available', '◉', true)}
        </section>
        <section class="dashboard-lower">
          <div class="panel recent-panel"><div class="panel-heading"><div><span class="eyebrow">LATEST ACTIVITY</span><h2>Recent orders</h2></div><a class="text-link" href="#orders">View orders <span>→</span></a></div>
            ${recent.length ? `<div class="table-scroll"><table><thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th><th>Placed</th></tr></thead><tbody>${recent.map((order) => `<tr><td class="table-primary">#${ui.escapeHtml(order.id)}</td><td>${ui.escapeHtml(order.user?.name || 'Not Available')}</td><td>${ui.formatMoney(order.total_amount)}</td><td>${ui.statusPill(order.status)}</td><td>${ui.formatDate(order.created_at)}</td></tr>`).join('')}</tbody></table></div>` : '<div class="empty-inline"><span class="empty-symbol">▤</span><strong>No recent orders</strong><span>The API returned no order records.</span></div>'}
          </div>
          <aside class="panel insight-panel"><div class="insight-orbit">✳</div><span class="eyebrow">PLATFORM HEALTH</span><h2>Some operations are not connected yet</h2><p>Marketplace overview is live. Financial, compliance analytics, and cold-chain panels need additional Laravel endpoints.</p><a class="button button-dark" href="#compliance">Review API dependencies <span>→</span></a></aside>
        </section>`;
    } catch (error) { ui.showError(error, loadDashboard); }
  }

  async function loadFarmers(page = farmerPage, search = farmerSearch) {
    farmerPage = page;
    farmerSearch = search;
    ui.setLoading('Loading farmers');
    try {
      const query = new URLSearchParams({ page: String(page), per_page: '20' });
      if (search) query.set('search', search);
      const result = await api.get(`/admin/farmers?${query}`);
      const paginator = result?.farmers || {};
      const farmers = Array.isArray(paginator.data) ? paginator.data : [];
      content.innerHTML = `${ui.pageHeading('MARKETPLACE', 'Farmers', 'Review registered farmers and their current verification status.', `<span class="count-chip">${Number(paginator.total || 0).toLocaleString()} farmers</span>`)}
        <section class="panel table-panel"><div class="table-toolbar"><div class="search-box"><span>⌕</span><input id="farmer-search" type="search" placeholder="Search name, email or phone" value="${ui.escapeHtml(search)}" aria-label="Search farmers"></div><a class="button button-secondary" href="#verifications">Open verification queue <span>→</span></a></div>
          ${farmers.length ? `<div class="table-scroll"><table><thead><tr><th>Farmer</th><th>Contact</th><th>Farm</th><th>Verification</th><th>Joined</th><th></th></tr></thead><tbody>${farmers.map((farmer) => {
            const verification = farmer.farmer_verification || {};
            const name = farmer.display_name || farmer.name || 'Unnamed farmer';
            return `<tr><td><div class="person-cell"><span class="avatar avatar-soft">${ui.escapeHtml(name.slice(0, 1).toUpperCase())}</span><span><strong>${ui.escapeHtml(name)}</strong><small>ID ${ui.escapeHtml(farmer.id)}</small></span></div></td><td><span>${ui.escapeHtml(farmer.phone || 'Not Available')}</span><small class="cell-sub">${ui.escapeHtml(farmer.email || 'Not Available')}</small></td><td><strong>${Number(farmer.farms_count || 0)}</strong><small class="cell-sub">${farmer.farms?.length ? ui.escapeHtml(farmer.farms.map((farm) => farm.farm_name).join(', ')) : 'No farm details'}</small></td><td>${ui.statusPill(verification.status || 'not_submitted')}</td><td>${ui.formatDate(farmer.created_at)}</td><td><button class="text-button" data-view-farmer="${ui.escapeHtml(farmer.id)}">View</button></td></tr>`;
          }).join('')}</tbody></table></div>` : '<div class="empty-state"><span class="empty-symbol">♧</span><h2>No farmers found</h2><p>Try another search or check back when farmer accounts are available.</p></div>'}
          ${ui.pagination(paginator)}
        </section>
        <div id="farmer-modal" class="modal-backdrop" hidden><section class="document-modal farmer-modal" role="dialog" aria-modal="true" aria-labelledby="farmer-modal-title"><div class="modal-heading"><div><span class="eyebrow">FARMER PROFILE</span><h2 id="farmer-modal-title">Farmer details</h2></div><button class="icon-button" id="close-farmer" aria-label="Close farmer details">×</button></div><div id="farmer-modal-content" class="farmer-detail-content"></div></section></div>`;
      content.querySelectorAll('[data-view-farmer]').forEach((button) => button.addEventListener('click', () => showFarmerDetails(farmers.find((farmer) => String(farmer.id) === button.dataset.viewFarmer))));
      document.getElementById('close-farmer').addEventListener('click', () => { document.getElementById('farmer-modal').hidden = true; });
      document.getElementById('farmer-modal').addEventListener('click', (event) => { if (event.target.id === 'farmer-modal') event.currentTarget.hidden = true; });
      document.getElementById('farmer-search').addEventListener('input', (event) => {
        clearTimeout(searchTimer);
        const value = event.currentTarget.value.trim();
        searchTimer = setTimeout(() => loadFarmers(1, value), 350);
      });
      bindPagination(() => loadFarmers);
    } catch (error) { ui.showError(error, () => loadFarmers(page, search)); }
  }

  function showFarmerDetails(farmer) {
    if (!farmer) return;
    const verification = farmer.farmer_verification || {};
    const farms = Array.isArray(farmer.farms) ? farmer.farms : [];
    const row = (label, value) => `<div class="detail-row"><span>${ui.escapeHtml(label)}</span><strong>${ui.escapeHtml(value || 'Not Available')}</strong></div>`;
    document.getElementById('farmer-modal-title').textContent = farmer.display_name || farmer.name || 'Farmer details';
    document.getElementById('farmer-modal-content').innerHTML = `<section class="detail-section"><h3>Profile</h3>${row('Farmer ID', farmer.id)}${row('Name', farmer.name)}${row('Phone', farmer.phone)}${row('Email', farmer.email)}${row('Address', farmer.location)}${row('Created', ui.formatDate(farmer.created_at))}</section>
      <section class="detail-section"><h3>Farm details</h3>${farms.length ? farms.map((farm) => `${row('Farm', farm.farm_name)}${row('Location', farm.location)}`).join('') : row('Farm', 'Not Available')}${row('Coordinates', farmer.latitude != null && farmer.longitude != null ? `${farmer.latitude}, ${farmer.longitude}` : 'Not Available')}</section>
      <section class="detail-section"><h3>Verification & compliance</h3>${row('Verification status', verification.status)}${row('Reviewed at', ui.formatDate(verification.reviewed_at))}${row('Compliance risk score', 'Not Available')}${row('Land title / certificates', 'Not Available')}${row('Risk factors', 'Not Available')}</section>
      <a class="button button-secondary" href="#verifications">Open verification queue <span>→</span></a>`;
    document.getElementById('farmer-modal').hidden = false;
  }

  async function loadVerifications(page = verificationPage, status = verificationStatus) {
    verificationPage = page;
    verificationStatus = status;
    ui.setLoading('Loading verification queue');
    try {
      const query = new URLSearchParams({ page: String(page), per_page: '20' });
      if (status !== 'all') query.set('status', status);
      const result = await api.get(`/admin/verifications?${query}`);
      const paginator = result?.verifications || {};
      const records = Array.isArray(paginator.data) ? paginator.data : [];
      content.innerHTML = `${ui.pageHeading('FARMER COMPLIANCE', 'Verification queue', 'Review farmer identity documents stored on the private Laravel disk.', '<span class="private-note"><span>▣</span> Private documents</span>')}
        <section class="panel table-panel"><div class="table-toolbar"><div class="filter-tabs">${['pending', 'approved', 'rejected', 'all'].map((item) => `<button class="filter-tab ${status === item ? 'selected' : ''}" data-status="${item}">${item === 'all' ? 'All records' : item.replace('_', ' ')}</button>`).join('')}</div><span class="count-chip">${Number(paginator.total || 0).toLocaleString()} records</span></div>
          ${records.length ? `<div class="table-scroll"><table><thead><tr><th>Applicant</th><th>Identity name</th><th>Submitted</th><th>Status</th><th>Documents</th><th>Decision</th></tr></thead><tbody>${records.map((record) => `<tr><td><div class="person-cell"><span class="avatar avatar-soft">${ui.escapeHtml((record.user?.name || 'F').slice(0, 1).toUpperCase())}</span><span><strong>${ui.escapeHtml(record.user?.name || 'Not Available')}</strong><small>ID ${ui.escapeHtml(record.user_id)}</small></span></div></td><td><strong>${ui.escapeHtml(record.full_name)}</strong><small class="cell-sub">ID · ${ui.escapeHtml(record.id_number)}</small></td><td>${ui.formatDate(record.submitted_at)}</td><td>${ui.statusPill(record.status)}</td><td><div class="doc-actions"><button class="text-button" data-document="${record.id}" data-side="front">Front</button><button class="text-button" data-document="${record.id}" data-side="back">Back</button></div></td><td>${record.status === 'pending' ? `<div class="decision-actions"><button class="button button-small button-approve" data-review="${record.id}" data-decision="approved">Approve</button><button class="button button-small button-reject" data-review="${record.id}" data-decision="rejected">Reject</button></div>` : `<span class="cell-muted">${ui.escapeHtml(record.rejection_reason || 'Reviewed')}</span>`}</td></tr>`).join('')}</tbody></table></div>` : '<div class="empty-state"><span class="empty-symbol">✓</span><h2>Nothing in this queue</h2><p>No verification applications match the selected filter.</p></div>'}
          ${ui.pagination(paginator)}
        </section>
        <div id="document-modal" class="modal-backdrop" hidden><section class="document-modal" role="dialog" aria-modal="true" aria-labelledby="document-title"><div class="modal-heading"><div><span class="eyebrow">SECURE DOCUMENT VIEW</span><h2 id="document-title">Identity document</h2></div><button class="icon-button" id="close-document" aria-label="Close document">×</button></div><div id="document-content" class="document-content"><span class="spinner"></span></div></section></div>`;

      content.querySelectorAll('[data-status]').forEach((button) => button.addEventListener('click', () => loadVerifications(1, button.dataset.status)));
      content.querySelectorAll('[data-review]').forEach((button) => button.addEventListener('click', () => reviewVerification(button.dataset.review, button.dataset.decision)));
      content.querySelectorAll('[data-document]').forEach((button) => button.addEventListener('click', () => openDocument(button.dataset.document, button.dataset.side)));
      document.getElementById('close-document').addEventListener('click', closeDocument);
      document.getElementById('document-modal').addEventListener('click', (event) => { if (event.target.id === 'document-modal') closeDocument(); });
      bindPagination(() => loadVerifications);
    } catch (error) { ui.showError(error, () => loadVerifications(page, status)); }
  }

  let documentUrl;
  async function openDocument(id, side) {
    const modal = document.getElementById('document-modal');
    const target = document.getElementById('document-content');
    modal.hidden = false;
    target.innerHTML = '<span class="spinner"></span><span>Fetching private document…</span>';
    try {
      const blob = await api.get(`/admin/verifications/${encodeURIComponent(id)}/documents/${side}`, { responseType: 'blob' });
      documentUrl = URL.createObjectURL(blob);
      if (blob.type.startsWith('image/')) {
        target.innerHTML = `<img src="${documentUrl}" alt="Farmer identity document, ${side} side">`;
      } else {
        target.innerHTML = `<p>Preview is unavailable for this file type.</p><a class="button button-secondary" href="${documentUrl}" download="verification-${id}-${side}">Download securely</a>`;
      }
    } catch (error) {
      target.innerHTML = `<p class="inline-error">${ui.escapeHtml(error.message)}</p>`;
    }
  }

  function closeDocument() {
    if (documentUrl) URL.revokeObjectURL(documentUrl);
    documentUrl = null;
    document.getElementById('document-modal').hidden = true;
  }

  async function reviewVerification(id, decision) {
    let rejectionReason;
    if (decision === 'rejected') {
      rejectionReason = window.prompt('Enter a reason for rejecting this verification:');
      if (!rejectionReason?.trim()) {
        if (rejectionReason !== null) ui.showToast('A rejection reason is required.', 'error');
        return;
      }
    } else if (!window.confirm('Approve this farmer verification?')) return;

    try {
      await api.put(`/admin/verifications/${encodeURIComponent(id)}`, {
        decision,
        ...(rejectionReason ? { rejection_reason: rejectionReason.trim() } : {}),
      });
      ui.showToast(`Verification ${decision}.`);
      await loadVerifications(verificationPage, verificationStatus);
    } catch (error) { ui.showToast(error.message || 'Could not review verification.', 'error'); }
  }

  async function loadProducts(page = productPage, status = productStatus) {
    productPage = page;
    productStatus = status;
    ui.setLoading('Loading product catalog');
    try {
      const query = new URLSearchParams({ page: String(page), per_page: '20' });
      if (status !== 'all') query.set('status', status);
      const result = await api.get(`/admin/products?${query}`);
      const paginator = result?.products || {};
      const products = Array.isArray(paginator.data) ? paginator.data : [];
      content.innerHTML = `${ui.pageHeading('MARKETPLACE', 'Product catalog', 'Review products submitted by registered farmers.', '<span class="count-chip">Approval queue</span>')}
        <section class="panel table-panel"><div class="table-toolbar"><div class="filter-tabs">${[['pending','Pending'],['approved','Approved'],['rejected','Rejected'],['all','All products']].map(([key,label]) => `<button class="filter-tab ${status === key ? 'selected' : ''}" data-product-status="${key}">${label}</button>`).join('')}</div><span class="count-chip">${Number(paginator.total || 0).toLocaleString()} products</span></div>
          ${products.length ? `<div class="table-scroll"><table><thead><tr><th>Product</th><th>Farmer / farm</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Decision</th></tr></thead><tbody>${products.map((product) => `<tr><td><div class="product-cell"><span class="product-thumb">${product.images?.find((image) => image.is_primary)?.image ? `<img src="${ui.escapeHtml(product.images.find((image) => image.is_primary).image)}" alt="">` : '◈'}</span><span><strong>${ui.escapeHtml(product.name)}</strong><small class="cell-sub">${ui.escapeHtml(product.unit || '')} · #${ui.escapeHtml(product.id)}</small></span></div></td><td><strong>${ui.escapeHtml(product.farm?.user?.name || 'Not Available')}</strong><small class="cell-sub">${ui.escapeHtml(product.farm?.farm_name || 'Farm details unavailable')}</small></td><td>${ui.escapeHtml(product.category?.name || 'Not Available')}</td><td>${ui.formatMoney(product.price)}<small class="cell-sub">Currency not provided</small></td><td>${ui.escapeHtml(product.quantity_available)} ${ui.escapeHtml(product.unit || '')}</td><td>${ui.statusPill(product.approval_status)}</td><td>${product.approval_status === 'pending' ? `<div class="decision-actions"><button class="button button-small button-approve" data-product-review="${product.id}" data-decision="approved">Approve</button><button class="button button-small button-reject" data-product-review="${product.id}" data-decision="rejected">Reject</button></div>` : `<span class="cell-muted">${ui.escapeHtml(product.rejection_reason || '—')}</span>`}</td></tr>`).join('')}</tbody></table></div>` : '<div class="empty-state"><span class="empty-symbol">◈</span><h2>No products found</h2><p>Products matching this status will appear here.</p></div>'}
          ${ui.pagination(paginator)}
        </section><p class="page-footnote">Flagging and action requests are not supported by the current Laravel API.</p>`;
      content.querySelectorAll('.product-thumb img').forEach((image) => {
        if (!/^https?:\/\//i.test(image.getAttribute('src') || '')) {
          image.remove();
          image.parentElement.textContent = '◈';
        }
      });
      content.querySelectorAll('[data-product-status]').forEach((button) => button.addEventListener('click', () => loadProducts(1, button.dataset.productStatus)));
      content.querySelectorAll('[data-product-review]').forEach((button) => button.addEventListener('click', () => reviewProduct(button.dataset.productReview, button.dataset.decision)));
      bindPagination(() => loadProducts);
    } catch (error) { ui.showError(error, () => loadProducts(page, status)); }
  }

  async function reviewProduct(id, decision) {
    let rejectionReason;
    if (decision === 'rejected') {
      rejectionReason = window.prompt('Enter a reason for rejecting this product:');
      if (!rejectionReason?.trim()) {
        if (rejectionReason !== null) ui.showToast('A rejection reason is required.', 'error');
        return;
      }
    } else if (!window.confirm('Approve this product for the customer catalog?')) return;

    try {
      await api.put(`/admin/products/${encodeURIComponent(id)}/review`, {
        decision,
        ...(rejectionReason ? { rejection_reason: rejectionReason.trim() } : {}),
      });
      ui.showToast(`Product ${decision}.`);
      await loadProducts(productPage, productStatus);
    } catch (error) { ui.showToast(error.message || 'Could not review product.', 'error'); }
  }

  function bindPagination(loaderFactory) {
    content.querySelectorAll('[data-page]').forEach((button) => button.addEventListener('click', () => {
      const loader = loaderFactory();
      if (loader === loadFarmers) loadFarmers(Number(button.dataset.page), farmerSearch);
      else if (loader === loadVerifications) loadVerifications(Number(button.dataset.page), verificationStatus);
      else if (loader === loadProducts) loadProducts(Number(button.dataset.page), productStatus);
    }));
  }

  function renderUnavailable(page) {
    const [name, endpoint] = missingApis[page] || ['This module', 'Endpoint not defined'];
    content.innerHTML = `${ui.pageHeading('OPERATIONS', name, 'This module is reserved for live Laravel API data.')}${ui.unavailableCard(name, endpoint)}<section class="dependency-note"><strong>Integration status</strong><span>This frontend does not connect directly to PostgreSQL and does not display placeholder records.</span></section>`;
  }

  async function render() {
    const page = (location.hash.slice(1) || 'dashboard').split('?')[0];
    const pageInfo = pages.find(([id]) => id === page);
    if (!pageInfo) {
      location.hash = '#dashboard';
      return;
    }
    renderNavigation(page);
    pageTitle.textContent = pageInfo[1];
    document.title = `${pageInfo[1]} | Phum Kasikor Admin`;
    document.getElementById('sidebar').classList.remove('sidebar-open');
    document.getElementById('mobile-scrim').classList.remove('scrim-visible');
    content.focus({ preventScroll: true });

    if (page === 'dashboard') await loadDashboard();
    else if (page === 'farmers') await loadFarmers();
    else if (page === 'verifications') await loadVerifications();
    else if (page === 'products') await loadProducts();
    else renderUnavailable(page);
  }

  async function start() {
    document.getElementById('api-host').textContent = new URL(window.API_CONFIG.baseUrl).host;
    currentUser = await window.auth.requireAdmin();
    if (!currentUser) return;
    document.getElementById('user-name').textContent = currentUser.display_name || currentUser.name || 'Administrator';
    document.getElementById('user-avatar').textContent = (currentUser.display_name || currentUser.name || 'A').slice(0, 1).toUpperCase();
    window.addEventListener('hashchange', render);
    await render();
  }

  document.getElementById('logout-button').addEventListener('click', async () => {
    try { await window.auth.logout(); } catch { sessionStorage.removeItem('pk_admin_token'); }
    location.replace('login.html');
  });
  document.getElementById('menu-button').addEventListener('click', (event) => {
    const open = document.getElementById('sidebar').classList.toggle('sidebar-open');
    document.getElementById('mobile-scrim').classList.toggle('scrim-visible', open);
    event.currentTarget.setAttribute('aria-expanded', String(open));
  });
  document.getElementById('mobile-scrim').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('sidebar-open');
    document.getElementById('mobile-scrim').classList.remove('scrim-visible');
  });

  start();
})();
