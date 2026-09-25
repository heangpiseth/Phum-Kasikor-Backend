(function () {
  async function login(identifier, password) {
    const result = await window.api.post('/auth/login', { identifier, password }, {
      skipAuth: true,
      allowUnauthorized: true,
    });

    if (!result?.token) throw new Error('The API did not return an access token.');
    sessionStorage.setItem('pk_admin_token', result.token);

    try {
      const session = await window.api.get('/me');
      const user = session?.user || session;
      if (user?.role !== 'admin') {
        await logout();
        throw new Error('This account does not have administrator access.');
      }
      return user;
    } catch (error) {
      if (error.status !== 401) sessionStorage.removeItem('pk_admin_token');
      throw error;
    }
  }

  async function currentUser() {
    if (!sessionStorage.getItem('pk_admin_token')) return null;
    const result = await window.api.get('/me');
    return result?.user || result;
  }

  async function logout() {
    try {
      if (sessionStorage.getItem('pk_admin_token')) {
        await window.api.post('/logout', {});
      }
    } finally {
      sessionStorage.removeItem('pk_admin_token');
    }
  }

  async function requireAdmin() {
    try {
      const user = await currentUser();
      if (!user) {
        location.replace('login.html');
        return null;
      }
      if (user.role !== 'admin') {
        document.body.innerHTML = '<main class="access-denied"><div class="access-icon">!</div><h1>Access denied</h1><p>You do not have permission to access this page.</p><a class="button button-primary" href="login.html">Return to sign in</a></main>';
        return null;
      }
      return user;
    } catch (error) {
      if (error.status === 403) {
        document.body.innerHTML = '<main class="access-denied"><div class="access-icon">!</div><h1>Access denied</h1><p>You do not have permission to access this page.</p><a class="button button-primary" href="login.html">Return to sign in</a></main>';
      } else if (error.status !== 401) {
        location.replace('login.html?reason=api');
      }
      return null;
    }
  }

  window.auth = Object.freeze({ login, logout, currentUser, requireAdmin });
})();
