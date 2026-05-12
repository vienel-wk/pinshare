const AUTH_USERS_KEY = 'pinshare-users';
const AUTH_SESSION_KEY = 'pinshare-session';

function getUsers() {
  return JSON.parse(localStorage.getItem(AUTH_USERS_KEY) || '[]');
}

function setUsers(users) {
  localStorage.setItem(AUTH_USERS_KEY, JSON.stringify(users));
}

function getSession() {
  return JSON.parse(localStorage.getItem(AUTH_SESSION_KEY) || 'null');
}

function setSession(user) {
  localStorage.setItem(AUTH_SESSION_KEY, JSON.stringify({
    name: user.name,
    email: user.email
  }));
}

function clearSession() {
  localStorage.removeItem(AUTH_SESSION_KEY);
}

function authToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  window.setTimeout(() => {
    toast.className = 'toast hidden';
  }, 2200);
}

function updateAuthUi() {
  const session = getSession();
  document.querySelectorAll('[data-auth-user]').forEach((item) => {
    item.classList.toggle('hidden', !session);
  });
  document.querySelectorAll('[data-auth-guest]').forEach((item) => {
    item.classList.toggle('hidden', Boolean(session));
  });

  const avatarBtn = document.getElementById('avatarBtn');
  if (avatarBtn && session) {
    avatarBtn.textContent = session.name.slice(0, 2).toUpperCase();
  }
}

function setupLogout() {
  document.querySelectorAll('[data-logout]').forEach((button) => {
    button.addEventListener('click', () => {
      clearSession();
      authToast('Berhasil logout.');
      window.setTimeout(() => {
        window.location.href = 'login.html';
      }, 700);
    });
  });
}

function setupLoginPage() {
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  if (!loginForm || !registerForm) return;

  const loginTab = document.getElementById('loginTab');
  const registerTab = document.getElementById('registerTab');
  const subtitle = document.getElementById('authSubtitle');

  function showMode(mode) {
    const isLogin = mode === 'login';
    loginForm.classList.toggle('hidden', !isLogin);
    registerForm.classList.toggle('hidden', isLogin);
    loginTab.classList.toggle('active', isLogin);
    registerTab.classList.toggle('active', !isLogin);
    subtitle.textContent = isLogin
      ? 'Masuk untuk menyimpan inspirasi favoritmu.'
      : 'Daftar akun demo untuk memakai fitur PinShare.';
  }

  loginTab.addEventListener('click', () => showMode('login'));
  registerTab.addEventListener('click', () => showMode('register'));

  registerForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const name = document.getElementById('registerName').value.trim();
    const email = document.getElementById('registerEmail').value.trim().toLowerCase();
    const password = document.getElementById('registerPassword').value;
    const users = getUsers();

    if (users.some((user) => user.email === email)) {
      authToast('Email sudah terdaftar.', 'error');
      return;
    }

    const user = { name, email, password };
    users.push(user);
    setUsers(users);
    setSession(user);
    authToast('Pendaftaran berhasil.');
    window.setTimeout(() => {
      window.location.href = 'index.html';
    }, 800);
  });

  loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const email = document.getElementById('loginEmail').value.trim().toLowerCase();
    const password = document.getElementById('loginPassword').value;
    const user = getUsers().find((item) => item.email === email && item.password === password);

    if (!user) {
      authToast('Email atau kata sandi salah.', 'error');
      return;
    }

    setSession(user);
    authToast('Berhasil masuk.');
    window.setTimeout(() => {
      window.location.href = 'index.html';
    }, 800);
  });
}

setupLoginPage();
setupLogout();
updateAuthUi();
