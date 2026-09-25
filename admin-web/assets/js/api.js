(function () {
  class ApiError extends Error {
    constructor(message, status, payload) {
      super(message);
      this.name = 'ApiError';
      this.status = status;
      this.payload = payload;
    }
  }

  async function request(path, options = {}) {
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');

    const token = sessionStorage.getItem('pk_admin_token');
    if (token && !options.skipAuth) headers.set('Authorization', `Bearer ${token}`);

    let body = options.body;
    if (body !== undefined && !(body instanceof FormData) && typeof body !== 'string') {
      headers.set('Content-Type', 'application/json');
      body = JSON.stringify(body);
    }

    let response;
    try {
      response = await fetch(`${window.API_CONFIG.baseUrl}${path}`, {
        method: options.method || 'GET',
        headers,
        body,
      });
    } catch (error) {
      throw new ApiError('Could not reach the Laravel API. Check the server and CORS settings.', 0, error);
    }

    if (response.status === 401) {
      sessionStorage.removeItem('pk_admin_token');
      if (!options.allowUnauthorized && !location.pathname.endsWith('/login.html')) {
        location.replace('login.html?reason=session');
      }
    }

    if (options.responseType === 'blob') {
      if (!response.ok) throw new ApiError(`Request failed (${response.status}).`, response.status);
      return response.blob();
    }

    const raw = await response.text();
    let payload = null;
    if (raw) {
      try { payload = JSON.parse(raw); } catch { payload = { message: raw }; }
    }

    if (!response.ok) {
      const validation = payload?.errors ? Object.values(payload.errors).flat().join(' ') : '';
      const message = validation || payload?.message || payload?.error?.message || `Request failed (${response.status}).`;
      throw new ApiError(message, response.status, payload);
    }

    return payload;
  }

  window.api = Object.freeze({
    get: (path, options) => request(path, options),
    post: (path, body, options = {}) => request(path, { ...options, method: 'POST', body }),
    put: (path, body, options = {}) => request(path, { ...options, method: 'PUT', body }),
    delete: (path, options = {}) => request(path, { ...options, method: 'DELETE' }),
    ApiError,
  });
})();
