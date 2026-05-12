// =============================================
// main.js — Fungsi JavaScript Utama PinShare
// =============================================

// -----------------------------------------------
// TOAST NOTIFICATION
// -----------------------------------------------
/**
 * Tampilkan notifikasi toast
 * @param {string} msg   - Pesan yang ditampilkan
 * @param {string} type  - 'success' | 'error' | 'info'
 * @param {number} duration - Durasi dalam ms (default 3000)
 */
function showToast(msg, type = 'info', duration = 3000) {
  const toast = document.getElementById('toast');
  if (!toast) return;

  toast.textContent = msg;
  toast.className = `toast show ${type}`;

  setTimeout(() => {
    toast.className = 'toast hidden';
  }, duration);
}

// -----------------------------------------------
// MODAL
// -----------------------------------------------
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
  }
}

// Tutup modal saat klik di luar
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal')) {
    e.target.classList.add('hidden');
    document.body.style.overflow = '';
  }
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal:not(.hidden)').forEach(m => {
      m.classList.add('hidden');
      document.body.style.overflow = '';
    });
  }
});

// -----------------------------------------------
// DROPDOWN USER MENU
// -----------------------------------------------
const avatarBtn    = document.getElementById('avatarBtn');
const userDropdown = document.getElementById('userDropdown');

if (avatarBtn && userDropdown) {
  avatarBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    userDropdown.classList.toggle('open');
  });

  document.addEventListener('click', function(e) {
    if (!userDropdown.contains(e.target)) {
      userDropdown.classList.remove('open');
    }
  });
}

// -----------------------------------------------
// LIKE PIN (via AJAX)
// -----------------------------------------------
let likeInProgress = false;

function likePin(pinId, btn) {
  if (likeInProgress) return;

  // Cek login
  const isLoggedIn = document.body.dataset.loggedIn === '1';
  if (!isLoggedIn) {
    showToast('Login dulu untuk menyukai pin 💡', 'error');
    setTimeout(() => { window.location.href = 'login.php'; }, 1200);
    return;
  }

  likeInProgress = true;

  fetch('php/like.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `pin_id=${pinId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const countEl = btn.querySelector('.like-count');
      if (countEl) countEl.textContent = data.count;

      if (data.action === 'liked') {
        btn.classList.add('active', 'liked');
        showToast('Disukai! ❤️', 'success', 1500);
      } else {
        btn.classList.remove('active', 'liked');
      }
    } else {
      showToast(data.message || 'Gagal', 'error');
    }
  })
  .catch(() => showToast('Koneksi error', 'error'))
  .finally(() => { likeInProgress = false; });
}

// -----------------------------------------------
// SAVE PIN KE BOARD
// -----------------------------------------------
let currentSavePinId = null;

function savePin(pinId) {
  const isLoggedIn = document.body.dataset.loggedIn === '1';
  if (!isLoggedIn) {
    showToast('Login dulu untuk menyimpan pin 💡', 'error');
    setTimeout(() => { window.location.href = 'login.php'; }, 1200);
    return;
  }

  currentSavePinId = pinId;

  // Ambil daftar board user via AJAX
  fetch('php/get_boards.php')
    .then(res => res.json())
    .then(data => {
      if (data.status === 'ok') {
        renderBoardList(data.boards);
        openModal('modalSave');
      }
    })
    .catch(() => showToast('Gagal memuat board', 'error'));
}

function renderBoardList(boards) {
  const container = document.getElementById('boardList');
  if (!container) return;

  if (boards.length === 0) {
    container.innerHTML = `
      <div style="text-align:center; padding:24px; color:var(--text-muted);">
        Belum ada board. Buat board baru dulu!
      </div>`;
    return;
  }

  container.innerHTML = boards.map(b => `
    <button class="board-item" onclick="savePinToBoard(${b.id})">
      <div class="board-thumb">${b.cover ? `<img src="uploads/${b.cover}">` : '📌'}</div>
      <div>
        <div class="board-name">${escapeHtml(b.name)}</div>
        <div style="font-size:12px;color:var(--text-muted)">${b.pin_count} pin</div>
      </div>
    </button>
  `).join('');
}

function savePinToBoard(boardId) {
  fetch('php/save_pin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `pin_id=${currentSavePinId}&board_id=${boardId}`
  })
  .then(res => res.json())
  .then(data => {
    closeModal('modalSave');
    showToast(data.status === 'ok' ? '✅ Disimpan ke board!' : data.message, 
              data.status === 'ok' ? 'success' : 'error');
  })
  .catch(() => showToast('Koneksi error', 'error'));
}

function createBoard() {
  const name = prompt('Nama board baru:');
  if (!name || name.trim() === '') return;

  fetch('php/create_board.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `name=${encodeURIComponent(name)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      showToast('Board dibuat! 🎉', 'success');
      // Reload daftar board
      savePin(currentSavePinId);
    } else {
      showToast(data.message, 'error');
    }
  });
}

// -----------------------------------------------
// SHARE PIN
// -----------------------------------------------
function sharePin(pinId) {
  const url = `${window.location.origin}${window.location.pathname.replace(/\/[^\/]*$/, '')}/pin.php?id=${pinId}`;
  const input = document.getElementById('shareUrl');
  if (input) input.value = url;
  openModal('modalShare');
}

function copyUrl() {
  const input = document.getElementById('shareUrl');
  if (!input) return;

  if (navigator.clipboard) {
    navigator.clipboard.writeText(input.value).then(() => {
      showToast('Link disalin! 🔗', 'success', 2000);
      closeModal('modalShare');
    });
  } else {
    input.select();
    document.execCommand('copy');
    showToast('Link disalin! 🔗', 'success', 2000);
    closeModal('modalShare');
  }
}

// -----------------------------------------------
// FOLLOW USER
// -----------------------------------------------
let followInProgress = false;

function toggleFollow(targetUserId, btn) {
  if (followInProgress) return;
  followInProgress = true;

  fetch('php/follow.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `target_id=${targetUserId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      if (data.action === 'followed') {
        btn.textContent = 'Mengikuti';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline');
        showToast('Berhasil mengikuti! 🎉', 'success', 1500);
      } else {
        btn.textContent = 'Ikuti';
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary');
      }

      // Update jumlah follower di halaman profil
      const followerCountEl = document.getElementById('followerCount');
      if (followerCountEl && data.follower_count !== undefined) {
        followerCountEl.textContent = data.follower_count;
      }
    }
  })
  .catch(() => showToast('Koneksi error', 'error'))
  .finally(() => { followInProgress = false; });
}

// -----------------------------------------------
// SEARCH BAR
// -----------------------------------------------
const searchInput = document.getElementById('searchInput');
const searchForm  = document.getElementById('searchForm');

if (searchInput) {
  // Submit dengan Enter
  searchInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const q = this.value.trim();
      if (q) {
        window.location.href = `index.php?q=${encodeURIComponent(q)}`;
      }
    }
  });
}

if (searchForm) {
  searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const q = searchInput ? searchInput.value.trim() : '';
    if (q) window.location.href = `index.php?q=${encodeURIComponent(q)}`;
  });
}

// -----------------------------------------------
// UTILITY
// -----------------------------------------------
function escapeHtml(text) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(text));
  return div.innerHTML;
}

// Konfirmasi hapus pin
function confirmDelete(pinId) {
  if (confirm('Yakin ingin menghapus pin ini? Tindakan ini tidak bisa dibatalkan.')) {
    window.location.href = `php/delete_pin.php?id=${pinId}`;
  }
}
