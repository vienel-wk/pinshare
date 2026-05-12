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
    showToast('Demo berhasil. Untuk menyimpan sungguhan perlu hosting PHP/database.');
  });
}

const demoLogin = document.getElementById('demoLogin');
if (demoLogin) {
  demoLogin.addEventListener('submit', (event) => {
    event.preventDefault();
    showToast('Masuk demo berhasil.');
    window.setTimeout(() => {
      window.location.href = 'index.html';
    }, 800);
  });
}
