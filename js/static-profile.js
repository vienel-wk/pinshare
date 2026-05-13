const PROFILE_PINS_KEY = 'pinshare-user-pins';
const PROFILE_SAVED_KEY = 'pinshare-saved';

const samplePins = [
  {
    id: 1,
    title: 'Inspirasi ruang kerja minimalis',
    category: 'Desain',
    color: '#f6d7df'
  },
  {
    id: 2,
    title: 'Referensi foto produk kreatif',
    category: 'Fotografi',
    color: '#d7e7f6'
  },
  {
    id: 3,
    title: 'Layout aplikasi sederhana',
    category: 'Teknologi',
    color: '#dff0df'
  }
];

let activeProfileTab = 'mine';

const profileGrid = document.getElementById('profilePinGrid');
const profileEmptyState = document.getElementById('profileEmptyState');
const profileEmptyTitle = document.getElementById('profileEmptyTitle');
const profileEmptyText = document.getElementById('profileEmptyText');
const profileEmptyAction = document.getElementById('profileEmptyAction');
const profilePinCount = document.getElementById('profilePinCount');

function getUserPins() {
  return JSON.parse(localStorage.getItem(PROFILE_PINS_KEY) || '[]');
}

function getSavedPins() {
  return JSON.parse(localStorage.getItem(PROFILE_SAVED_KEY) || '[]');
}

function setSavedPins(savedPins) {
  localStorage.setItem(PROFILE_SAVED_KEY, JSON.stringify(savedPins));
}

function currentUserPins() {
  const session = typeof getSession === 'function' ? getSession() : null;
  if (!session) return [];
  return getUserPins().filter((pin) => pin.ownerEmail === session.email);
}

function allProfilePins() {
  return [...currentUserPins(), ...samplePins];
}

function isSavedProfilePin(pinId) {
  return getSavedPins().map(String).includes(String(pinId));
}

function toggleProfileSave(pinId) {
  const savedPins = getSavedPins();
  const exists = savedPins.map(String).includes(String(pinId));
  const nextSavedPins = exists
    ? savedPins.filter((item) => String(item) !== String(pinId))
    : [...savedPins, pinId];

  setSavedPins(nextSavedPins);
  authToast(exists ? 'Simpan dibatalkan.' : 'Pin disimpan.');
  renderProfile();
}

function escapeProfileHtml(value) {
  const div = document.createElement('div');
  div.textContent = value;
  return div.innerHTML;
}

function pinVisual(pin) {
  if (pin.image) {
    return `<img src="${pin.image}" alt="${escapeProfileHtml(pin.title)}">`;
  }
  return `<div class="pin-placeholder" style="background:${pin.color};"><span>${escapeProfileHtml(pin.category)}</span></div>`;
}

function pinCard(pin) {
  return `
    <article class="pin-card">
      <div class="pin-image-wrap">
        ${pinVisual(pin)}
        <div class="pin-overlay">
          <button class="btn-save ${isSavedProfilePin(pin.id) ? 'active' : ''}" type="button" data-profile-save="${pin.id}">
            ${isSavedProfilePin(pin.id) ? 'Tersimpan' : 'Simpan'}
          </button>
        </div>
      </div>
      <div class="pin-info">
        <span class="pin-tag">${escapeProfileHtml(pin.category)}</span>
        <h2 class="pin-title">${escapeProfileHtml(pin.title)}</h2>
      </div>
    </article>
  `;
}

function emptyProfile(title, text, showAction = true) {
  profileGrid.innerHTML = '';
  profileEmptyTitle.textContent = title;
  profileEmptyText.textContent = text;
  profileEmptyAction.classList.toggle('hidden', !showAction);
  profileEmptyState.classList.remove('hidden');
}

function renderProfile() {
  const mine = currentUserPins();
  profilePinCount.textContent = mine.length;

  let pins = mine;
  if (activeProfileTab === 'saved') {
    const savedIds = getSavedPins().map(String);
    pins = allProfilePins().filter((pin) => savedIds.includes(String(pin.id)));
  }

  if (activeProfileTab === 'boards') {
    emptyProfile('Board belum tersedia', 'Fitur board masih mode demo di GitHub Pages.', false);
    return;
  }

  if (pins.length === 0) {
    if (activeProfileTab === 'saved') {
      emptyProfile('Belum ada pin tersimpan', 'Klik Simpan pada pin agar muncul di menu Tersimpan.', false);
    } else {
      emptyProfile('Belum ada pin', 'Upload foto pertama kamu agar muncul di Pin Saya.', true);
    }
    return;
  }

  profileEmptyState.classList.add('hidden');
  profileGrid.innerHTML = pins.map(pinCard).join('');
}

document.querySelectorAll('[data-profile-tab]').forEach((tab) => {
  tab.addEventListener('click', () => {
    activeProfileTab = tab.dataset.profileTab;
    document.querySelectorAll('[data-profile-tab]').forEach((item) => item.classList.remove('active'));
    tab.classList.add('active');
    renderProfile();
  });
});

profileGrid.addEventListener('click', (event) => {
  const saveButton = event.target.closest('[data-profile-save]');
  if (!saveButton) return;
  toggleProfileSave(saveButton.dataset.profileSave);
});

renderProfile();
