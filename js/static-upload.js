const toast = document.getElementById('toast');

function showToast(message, type = 'success') {
  if (!toast) return;
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  window.setTimeout(() => {
    toast.className = 'toast hidden';
  }, 2200);
}

const fileInput = document.getElementById('fileInput');
const previewImg = document.getElementById('previewImg');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');
let selectedImage = '';

if (fileInput) {
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      showToast('File harus berupa gambar.', 'error');
      return;
    }
    const reader = new FileReader();
    reader.onload = (event) => {
      selectedImage = event.target.result;
      previewImg.src = event.target.result;
      previewImg.style.display = 'block';
      uploadPlaceholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });
}

const publishDemo = document.getElementById('publishDemo');
if (publishDemo) {
  publishDemo.addEventListener('click', () => {
    const session = typeof getSession === 'function' ? getSession() : null;
    const title = document.getElementById('pinTitle').value.trim();
    const desc = document.getElementById('pinDesc').value.trim();
    const category = document.getElementById('pinCategory').value;

    if (!session) {
      window.location.href = 'login.html';
      return;
    }

    if (!selectedImage) {
      showToast('Pilih foto terlebih dahulu.', 'error');
      return;
    }

    if (!title) {
      showToast('Judul pin wajib diisi.', 'error');
      return;
    }

    const userPins = JSON.parse(localStorage.getItem('pinshare-user-pins') || '[]');
    userPins.unshift({
      id: `user-${Date.now()}`,
      title,
      desc,
      category,
      image: selectedImage,
      ownerEmail: session.email,
      ownerName: session.name,
      createdAt: new Date().toISOString()
    });
    localStorage.setItem('pinshare-user-pins', JSON.stringify(userPins));

    showToast('Pin berhasil diterbitkan.');
    window.setTimeout(() => {
      window.location.href = 'profile.html';
    }, 800);
  });
}
