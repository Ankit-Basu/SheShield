/* Sign in and sign up. Firebase is about 200 KB and only needed for Google sign-in,
   password reset, account creation and the login fallback, so it loads on demand. */
(function () {
  'use strict';

  const { post, busy } = window.SS;
  const DASHBOARD = '../views/pages/dashboard.php';
  const $ = (id) => document.getElementById(id);

  const errorBox = $('authError');
  const showError = (message) => {
    errorBox.querySelector('div').textContent = message;
    errorBox.hidden = false;
    errorBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  };
  const clearError = () => { errorBox.hidden = true; };

  let firebaseReady = null;
  const addScript = (src) => new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = src;
    s.onload = resolve;
    s.onerror = () => reject(new Error('Could not reach the sign-in service. Check your connection.'));
    document.head.appendChild(s);
  });
  /* global auth, googleProvider */
  const loadFirebase = () => {
    if (!firebaseReady) {
      firebaseReady = addScript('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js')
        .then(() => addScript('https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js'))
        .then(() => addScript('../js/firebase-config.js'));
      firebaseReady.catch(() => { firebaseReady = null; });
    }
    return firebaseReady;
  };

  // Exchanges a Firebase user for a PHP session.
  const openSession = async (user, nameFallback) => {
    const idToken = await user.getIdToken();
    const data = await post('../api/auth/firebase-login.php', {
      idToken, name: user.displayName || nameFallback, email: user.email, photo: user.photoURL,
    });
    window.location.href = data.redirect || DASHBOARD;
  };

  document.querySelectorAll('[data-eye]').forEach((b) => b.addEventListener('click', () => {
    const input = $(b.dataset.eye);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    b.querySelector('use').setAttribute('href', `../public/icons.svg#${show ? 'eye-off' : 'eye'}`);
  }));

  const google = $('googleBtn');
  google.addEventListener('click', async () => {
    clearError();
    busy(google, true, 'Connecting');
    try {
      await loadFirebase();
      const result = await auth.signInWithPopup(googleProvider);
      await openSession(result.user, result.user.email);
    } catch (err) {
      busy(google, false);
      if (err.code !== 'auth/popup-closed-by-user') showError(err.message || 'Google sign-in failed.');
    }
  });

  /* ─── Sign in ─────────────────────────────────────────────────── */
  const loginForm = $('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearError();
      const email = $('loginEmail').value.trim();
      const password = $('loginPass').value;
      if (!email || !password) { showError('Enter your email and password.'); return; }
      const btn = $('loginBtn');
      busy(btn, true, 'Signing in');
      try {
        await post('../auth/simple_login.php', { email, password });
        window.location.href = DASHBOARD;
      } catch (phpError) {
        // Accounts created through Firebase have no local password.
        try {
          await loadFirebase();
          const cred = await auth.signInWithEmailAndPassword(email, password);
          await openSession(cred.user, email);
        } catch (err) {
          busy(btn, false);
          showError('That email and password do not match. Please try again.');
        }
      }
    });

    $('forgotLink').addEventListener('click', async () => {
      clearError();
      const email = $('loginEmail').value.trim();
      if (!email) { showError('Enter your email above first, then choose "Forgot password".'); $('loginEmail').focus(); return; }
      try {
        await loadFirebase();
        await auth.sendPasswordResetEmail(email);
        window.showToast('If that email has an account, a reset link is on its way.');
      } catch (err) {
        if (err.code === 'auth/user-not-found') window.showToast('If that email has an account, a reset link is on its way.');
        else showError(err.message || 'Could not send a reset email.');
      }
    });
  }

  /* ─── Sign up ─────────────────────────────────────────────────── */
  const signupForm = $('signupForm');
  if (signupForm) {
    const pass = $('signupPass');
    const confirm = $('confirmPass');
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    pass.addEventListener('input', () => {
      const v = pass.value;
      const score = [v.length >= 8, /[A-Z]/.test(v), /[0-9]/.test(v), /[^A-Za-z0-9]/.test(v)].filter(Boolean).length;
      $('strength').dataset.score = v ? Math.max(score, 1) : 0;
      $('strengthLabel').textContent = v ? labels[Math.max(score, 1)] : '';
    });
    const checkMatch = () => {
      $('matchHint').textContent = confirm.value && confirm.value !== pass.value ? 'Passwords do not match yet.' : '';
    };
    confirm.addEventListener('input', checkMatch);
    pass.addEventListener('input', checkMatch);

    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      clearError();
      if (pass.value !== confirm.value) { showError('The two passwords do not match.'); return; }
      const data = Object.fromEntries(new FormData(signupForm));
      data.password = pass.value;
      const btn = $('signupBtn');
      busy(btn, true, 'Creating your account');
      try {
        await loadFirebase();
        const cred = await auth.createUserWithEmailAndPassword(data.email, data.password);
        await cred.user.updateProfile({ displayName: `${data.first_name} ${data.last_name}` });
        await post('../auth/signup.php', data);
        window.location.href = DASHBOARD;
      } catch (err) {
        busy(btn, false);
        showError(err.message || 'Could not create your account.');
      }
    });
  }
}());
