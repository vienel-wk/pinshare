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
let selectedFile = null;

if (fileInput) {
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) {
      showToast('File harus berupa gambar.', 'error');
      return;
    }
    selectedFile = file;
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
  publishDemo.addEventListener('click', async () => {
    const session = typeof getSession === 'function' ? getSession() : null;
    const title = document.getElementById('pinTitle').value.trim();
    const desc = document.getElementById('pinDesc').value.trim();
    const category = document.getElementById('pinCategory').value.toLowerCase();

    if (!session) {
      window.location.href = 'login.html';
      return;
    }

    if (!selectedFile || !selectedImage) {
      showToast('Pilih foto terlebih dahulu.', 'error');
      return;
    }

    if (!title) {
      showToast('Judul pin wajib diisi.', 'error');
      return;
    }

    publishDemo.disabled = true;
    publishDemo.textContent = 'Mengunggah...';

    try {
      if (!window.pinshareFirebase) {
        throw new Error('Firebase belum siap.');
      }

      const safeName = selectedFile.name.replace(/[^a-z0-9._-]/gi, '-');
      const filePath = `pins/${session.email}/${Date.now()}-${safeName}`;
      const fileRef = window.pinshareFirebase.storage.ref().child(filePath);
      await fileRef.put(selectedFile, { contentType: selectedFile.type });
      const imageUrl = await fileRef.getDownloadURL();

      await window.pinshareFirebase.db.collection('pins').add({
        title,
        desc,
        category,
        image: imageUrl,
        storagePath: filePath,
        ownerEmail: session.email,
        ownerName: session.name,
        createdAt: firebase.firestore.FieldValue.serverTimestamp()
      });

      showToast('Pin berhasil diterbitkan online.');
      window.setTimeout(() => {
        window.location.href = 'profile.html';
      }, 900);
    } catch (error) {
      console.error(error);
      showToast('Gagal upload ke Firebase. Cek rules Storage/Firestore.', 'error');
      publishDemo.disabled = false;
      publishDemo.textContent = 'Terbitkan Pin';
    }
  });
}
