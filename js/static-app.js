const pins = [
  {
    id: 1,
    title: 'Inspirasi ruang kerja minimalis',
    desc: 'Moodboard ruang kerja kecil dengan warna netral, rak sederhana, dan pencahayaan hangat.',
    category: 'desain',
    image: 'uploads/img_6a01dce9b973c1.86645048.jpg',
    user: 'Ahmad Kurniawan',
    initials: 'AK',
    likes: 128
  },
  {
    id: 2,
    title: 'Referensi foto produk kreatif',
    desc: 'Contoh komposisi foto produk untuk katalog, media sosial, dan toko online.',
    category: 'fotografi',
    image: 'uploads/img_6a0222586afd32.67139827.jpg',
    user: 'Naya Studio',
    initials: 'NS',
    likes: 86
  },
  {
    id: 3,
    title: 'Layout aplikasi sederhana',
    desc: 'Ide tampilan dashboard yang rapi untuk tugas sekolah atau proyek portfolio.',
    category: 'teknologi',
    image: 'uploads/img_6a01dce9b973c1.86645048.jpg',
    user: 'Rafi Dev',
    initials: 'RD',
    likes: 74
  },
  {
    id: 4,
    title: 'Dekorasi kamar mungil',
    desc: 'Kombinasi storage, tanaman kecil, dan poster untuk kamar yang terasa lega.',
    category: 'arsitektur',
    image: 'uploads/img_6a0222586afd32.67139827.jpg',
    user: 'Dina Home',
    initials: 'DH',
    likes: 211
  },
  {
    id: 5,
    title: 'Resep sarapan cepat',
    desc: 'Inspirasi menu praktis yang tetap terlihat cantik untuk difoto.',
    category: 'makanan',
    image: 'uploads/img_6a01dce9b973c1.86645048.jpg',
    user: 'Kitchen Lab',
    initials: 'KL',
    likes: 95
  },
  {
    id: 6,
    title: 'Warna outfit harian',
    desc: 'Referensi warna pakaian yang mudah dipadukan untuk aktivitas sehari-hari.',
    category: 'fashion',
    image: 'uploads/img_6a0222586afd32.67139827.jpg',
    user: 'Mira Style',
    initials: 'MS',
    likes: 63
  },
  {
    id: 7,
    title: 'Sketsa poster acara',
    desc: 'Ide visual poster dengan tipografi besar dan komposisi yang mudah dibaca.',
    category: 'seni',
    image: 'uploads/img_6a01dce9b973c1.86645048.jpg',
    user: 'Arka Art',
    initials: 'AA',
    likes: 147
  },
  {
    id: 8,
    title: 'Catatan perjalanan singkat',
    desc: 'Cara menyusun dokumentasi perjalanan agar rapi dan enak dilihat.',
    category: 'perjalanan',
    image: 'uploads/img_6a0222586afd32.67139827.jpg',
    user: 'Lana Trip',
    initials: 'LT',
    likes: 104
  }
];

const notifications = [
  'Naya menyukai pin kamu.',
  'Rafi mulai mengikuti profil kamu.',
  'Pin "Ruang kerja minimalis" disimpan 5 orang.'
];

let activeCategory = 'semua';
let searchTerm = '';
let visibleCount = 6;
let activePin = null;

const pinGrid = document.getElementById('pinGrid');
const emptyState = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');
const searchInfo = document.getElementById('searchInfo');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const toast = document.getElementById('toast');

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

function filteredPins() {
  return pins.filter((pin) => {
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
        <img src="${pin.image}" alt="${escapeHtml(pin.title)}" loading="lazy">
        <div class="pin-overlay">
          <button class="btn-save" type="button" data-action="save" data-pin-id="${pin.id}">Simpan</button>
          <div class="overlay-actions">
            <button class="icon-btn" type="button" data-action="like" data-pin-id="${pin.id}">Suka ${pin.likes}</button>
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
  activePin = pins.find((pin) => pin.id === Number(pinId));
  if (!activePin) return;

  document.getElementById('modalTitle').textContent = activePin.title;
  document.getElementById('modalDesc').textContent = activePin.desc;
  document.getElementById('modalImg').src = activePin.image;
  document.getElementById('modalImg').alt = activePin.title;
  document.getElementById('modalAvatar').textContent = activePin.initials;
  document.getElementById('modalUser').textContent = activePin.user;
  document.getElementById('modalLikeCount').textContent = activePin.likes;
  document.getElementById('pinModal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
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
    const pin = pins.find((item) => item.id === Number(actionButton.dataset.pinId));
    const label = actionButton.dataset.action;
    if (label === 'like') {
      pin.likes += 1;
      showToast('Pin disukai.');
      renderPins();
    }
    if (label === 'save') showToast('Pin disimpan.');
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
  activePin.likes += 1;
  document.getElementById('modalLikeCount').textContent = activePin.likes;
  showToast('Pin disukai.');
  renderPins();
});

document.getElementById('modalSaveBtn').addEventListener('click', () => showToast('Pin disimpan.'));
document.getElementById('followBtn').addEventListener('click', (event) => {
  event.target.textContent = event.target.textContent === 'Ikuti' ? 'Mengikuti' : 'Ikuti';
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
