(function () {
  const form = document.getElementById('login-form');
  const errorBox = document.getElementById('login-error');
  const submit = document.getElementById('login-submit');
  const password = document.getElementById('password');
  const params = new URLSearchParams(location.search);

  if (params.get('reason') === 'session') {
    errorBox.textContent = 'Your session expired. Sign in again to continue.';
    errorBox.hidden = false;
  } else if (params.get('reason') === 'api') {
    errorBox.textContent = 'Unable to verify your session with the API.';
    errorBox.hidden = false;
  }

  document.getElementById('toggle-password').addEventListener('click', (event) => {
    const isPassword = password.type === 'password';
    password.type = isPassword ? 'text' : 'password';
    event.currentTarget.textContent = isPassword ? 'Hide' : 'Show';
    event.currentTarget.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.hidden = true;
    submit.disabled = true;
    submit.innerHTML = '<span class="button-spinner"></span><span>Signing in…</span>';

    try {
      await window.auth.login(
        document.getElementById('identifier').value.trim(),
        password.value,
      );
      location.replace('index.html#dashboard');
    } catch (error) {
      errorBox.textContent = error.message || 'Sign in failed. Try again.';
      errorBox.hidden = false;
      submit.disabled = false;
      submit.innerHTML = '<span>Sign in</span><span aria-hidden="true">→</span>';
    }
  });
})();
