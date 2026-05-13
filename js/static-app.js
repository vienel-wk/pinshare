const samplePins = [];

const notifications = [
  'Naya menyukai pin kamu.',
  'Rafi mulai mengikuti profil kamu.',
  'Pin "Ruang kerja minimalis" disimpan 5 orang.'
];

let activeCategory = 'semua';
let searchTerm = '';
let visibleCount = 6;
let activePin = null;
let comments = JSON.parse(localStorage.getItem('pinshare-comments') || '{}');
let likedPins = JSON.parse(localStorage.getItem('pinshare-liked') || '[]');
let savedPins = JSON.parse(localStorage.getItem('pinshare-saved') || '[]');

const pinGrid = document.getElementById('pinGrid');
const emptyState = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');
const searchInfo = document.getElementById('searchInfo');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const toast = document.getElementById('toast');
const commentForm = document.getElementById('commentForm');
const commentInput = document.getElementById('commentInput');
const commentList = document.getElementById('commentList');
const commentCount = document.getElementById('commentCount');

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value;
  return div.innerHTML;
}

function showToast(message, type = 'success') {
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  window.setTimeout(() => {
    toast.className = 'toast hidden';
  }, 2200);
}

function savePinState() {
  localStorage.setItem('pinshare-liked', JSON.stringify(likedPins));
  localStorage.setItem('pinshare-saved', JSON.stringify(savedPins));
}

function isLiked(pinId) {
  return likedPins.map(String).includes(String(pinId));
}

function isSaved(pinId) {
  return savedPins.map(String).includes(String(pinId));
}

function displayedLikes(pin) {
  return pin.likes + (isLiked(pin.id) ? 1 : 0);
}

function toggleLike(pinId) {
  const id = String(pinId);
  if (isLiked(id)) {
    likedPins = likedPins.filter((item) => String(item) !== id);
    showToast('Suka dibatalkan.');
  } else {
    likedPins.push(id);
    showToast('Pin disukai.');
  }
  savePinState();
  renderPins();
  if (activePin && activePin.id === id) syncModalActions();
}

function toggleSave(pinId) {
  const id = String(pinId);
  if (isSaved(id)) {
    savedPins = savedPins.filter((item) => String(item) !== id);
    showToast('Simpan dibatalkan.');
  } else {
    savedPins.push(id);
    showToast('Pin disimpan.');
  }
  savePinState();
  renderPins();
  if (activePin && activePin.id === id) syncModalActions();
}

function getUserPins() {
  return JSON.parse(localStorage.getItem('pinshare-user-pins') || '[]');
}

function initialsFromName(name) {
  return (name || 'PS').slice(0, 2).toUpperCase();
}

function normalizedUserPins() {
  return getUserPins().map((pin) => ({
    id: pin.id,
    title: pin.title,
    desc: pin.desc || '',
    category: (pin.category || 'desain').toLowerCase(),
    image: pin.image,
    user: pin.ownerName || 'Pengguna',
    initials: initialsFromName(pin.ownerName || 'PS'),
    likes: 0
  }));
}

function allPins() {
  return [...normalizedUserPins(), ...samplePins];
}

function pinVisual(pin) {
  if (pin.image) {
    return `<img src="${pin.image}" alt="${escapeHtml(pin.title)}" loading="lazy">`;
  }
  return `
    <div class="pin-placeholder" style="background:${pin.color};">
      <span>${escapeHtml(pin.category)}</span>
    </div>
  `;
}

function filteredPins() {
  return allPins().filter((pin) => {
    const inCategory = activeCategory === 'semua' || pin.category === activeCategory;
    const haystack = `${pin.title} ${pin.desc} ${pin.category} ${pin.user}`.toLowerCase();
    return inCategory && haystack.includes(searchTerm.toLowerCase());
  });
}

function renderPins() {
  const results = filteredPins();
  const visiblePins = results.slice(0, visibleCount);

  pinGrid.innerHTML = visiblePins.map((pin) => `
    <article class="pin-card" data-pin-id="${pin.id}">
      <div class="pin-image-wrap">
        ${pinVisual(pin)}
        <div class="pin-overlay">
          <button class="btn-save ${isSaved(pin.id) ? 'active' : ''}" type="button" data-action="save" data-pin-id="${pin.id}">
            ${isSaved(pin.id) ? 'Tersimpan' : 'Simpan'}
          </button>
          <div class="overlay-actions">
            <button class="icon-btn ${isLiked(pin.id) ? 'active' : ''}" type="button" data-action="like" data-pin-id="${pin.id}">
              ${isLiked(pin.id) ? 'Batal suka' : 'Suka'} ${displayedLikes(pin)}
            </button>
            <button class="icon-btn" type="button" data-action="share" data-pin-id="${pin.id}">Bagikan</button>
          </div>
        </div>
      </div>
      <div class="pin-info">
        <span class="pin-tag">${escapeHtml(pin.category)}</span>
        <h2 class="pin-title">${escapeHtml(pin.title)}</h2>
        <div class="pin-user">
          <span class="user-av" style="background:#e60023;">${pin.initials}</span>
          <span class="user-name">${escapeHtml(pin.user)}</span>
        </div>
      </div>
    </article>
  `).join('');

  emptyState.classList.toggle('hidden', results.length > 0);
  loadMoreBtn.style.display = visibleCount < results.length ? 'inline-flex' : 'none';

  if (searchTerm) {
    searchInfo.classList.remove('hidden');
    searchInfo.innerHTML = `Hasil pencarian untuk <strong>${escapeHtml(searchTerm)}</strong> <a href="#" id="clearSearch">hapus</a>`;
  } else {
    searchInfo.classList.add('hidden');
  }
}

function openPin(pinId) {
  activePin = allPins().find((pin) => String(pin.id) === String(pinId));
  if (!activePin) return;

  document.getElementById('modalTitle').textContent = activePin.title;
  document.getElementById('modalDesc').textContent = activePin.desc;
  const modalVisual = document.getElementById('modalVisual');
  modalVisual.style.background = activePin.image ? 'transparent' : activePin.color;
  modalVisual.innerHTML = activePin.image
    ? `<img src="${activePin.image}" alt="${escapeHtml(activePin.title)}">`
    : escapeHtml(activePin.category);
  document.getElementById('modalAvatar').textContent = activePin.initials;
  document.getElementById('modalUser').textContent = activePin.user;
  syncModalActions();
  renderComments();
  document.getElementById('pinModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function syncModalActions() {
  if (!activePin) return;
  const modalLikeBtn = document.getElementById('modalLikeBtn');
  const modalSaveBtn = document.getElementById('modalSaveBtn');
  document.getElementById('modalLikeCount').textContent = displayedLikes(activePin);
  modalLikeBtn.classList.toggle('liked', isLiked(activePin.id));
  modalLikeBtn.firstChild.textContent = isLiked(activePin.id) ? 'Batal suka ' : 'Suka ';
  modalSaveBtn.textContent = isSaved(activePin.id) ? 'Tersimpan' : 'Simpan';
  modalSaveBtn.classList.toggle('active', isSaved(activePin.id));
}

function saveComments() {
  localStorage.setItem('pinshare-comments', JSON.stringify(comments));
}

function renderComments() {
  if (!activePin) return;
  const pinComments = comments[activePin.id] || [];
  commentCount.textContent = pinComments.length;

  if (pinComments.length === 0) {
    commentList.innerHTML = '<p class="text-muted">Belum ada komentar.</p>';
    return;
  }

  commentList.innerHTML = pinComments.map((comment) => `
    <div class="comment-item">
      <div class="comment-av-default" style="background:#e60023;">AK</div>
      <div class="comment-bubble">
        <div class="comment-username">Kamu</div>
        <div class="comment-text">${escapeHtml(comment.text)}</div>
        <div class="comment-meta">${escapeHtml(comment.time)}</div>
      </div>
    </div>
  `).join('');
}

function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  document.body.style.overflow = '';
}

document.getElementById('categoryBar').addEventListener('click', (event) => {
  const pill = event.target.closest('.pill');
  if (!pill) return;
  activeCategory = pill.dataset.cat;
  visibleCount = 6;
  document.querySelectorAll('.pill').forEach((item) => item.classList.remove('active'));
  pill.classList.add('active');
  renderPins();
});

pinGrid.addEventListener('click', (event) => {
  const actionButton = event.target.closest('[data-action]');
  if (actionButton) {
    event.stopPropagation();
    const pin = allPins().find((item) => String(item.id) === String(actionButton.dataset.pinId));
    const label = actionButton.dataset.action;
    if (label === 'like') {
      toggleLike(pin.id);
    }
    if (label === 'save') toggleSave(pin.id);
    if (label === 'share') shareCurrentPin(pin);
    return;
  }

  const card = event.target.closest('.pin-card');
  if (card) openPin(card.dataset.pinId);
});

searchForm.addEventListener('submit', (event) => {
  event.preventDefault();
  searchTerm = searchInput.value.trim();
  visibleCount = 6;
  renderPins();
});

document.addEventListener('click', (event) => {
  if (event.target.id === 'clearSearch') {
    event.preventDefault();
    searchTerm = '';
    searchInput.value = '';
    renderPins();
  }
});

loadMoreBtn.addEventListener('click', () => {
  visibleCount += 4;
  renderPins();
});

document.getElementById('closeModal').addEventListener('click', () => closeModal('pinModal'));
document.getElementById('pinModal').addEventListener('click', (event) => {
  if (event.target.id === 'pinModal') closeModal('pinModal');
});

document.getElementById('modalLikeBtn').addEventListener('click', () => {
  if (!activePin) return;
  toggleLike(activePin.id);
});

document.getElementById('modalSaveBtn').addEventListener('click', () => {
  if (!activePin) return;
  toggleSave(activePin.id);
});
document.getElementById('followBtn').addEventListener('click', (event) => {
  event.target.textContent = event.target.textContent === 'Ikuti' ? 'Mengikuti' : 'Ikuti';
});

commentForm.addEventListener('submit', (event) => {
  event.preventDefault();
  if (!activePin) return;

  const text = commentInput.value.trim();
  if (!text) return;

  const pinComments = comments[activePin.id] || [];
  pinComments.unshift({
    text,
    time: new Date().toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    })
  });

  comments[activePin.id] = pinComments;
  commentInput.value = '';
  saveComments();
  renderComments();
  showToast('Komentar ditambahkan.');
});

function shareCurrentPin(pin = activePin) {
  const url = `${window.location.origin}${window.location.pathname}#pin-${pin.id}`;
  if (navigator.share) {
    navigator.share({ title: pin.title, text: pin.desc, url }).catch(() => {});
  } else if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => showToast('Link pin disalin.'));
  } else {
    showToast('Bagikan link halaman ini.');
  }
}

document.getElementById('modalShareBtn').addEventListener('click', () => shareCurrentPin());

document.getElementById('avatarBtn').addEventListener('click', (event) => {
  event.stopPropagation();
  document.getElementById('userDropdown').classList.toggle('open');
});

document.addEventListener('click', () => {
  document.getElementById('userDropdown').classList.remove('open');
});

document.getElementById('notifBtn').addEventListener('click', () => {
  document.getElementById('notifList').innerHTML = notifications.map((item) => `
    <div class="notif-item unread">
      <div class="notif-av-default" style="background:#e60023;">PS</div>
      <div class="notif-text">${escapeHtml(item)}</div>
      <div class="notif-time">baru</div>
    </div>
  `).join('');
  document.getElementById('notifModal').classList.remove('hidden');
  document.getElementById('notifBadge').textContent = '0';
});

document.getElementById('closeNotif').addEventListener('click', () => closeModal('notifModal'));

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    document.querySelectorAll('.modal:not(.hidden)').forEach((modal) => modal.classList.add('hidden'));
    document.body.style.overflow = '';
  }
});

renderPins();
