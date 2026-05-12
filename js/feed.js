// =============================================
// feed.js — JS untuk Upload, Komentar, Profil
// =============================================

// -----------------------------------------------
// UPLOAD PIN — Preview Gambar
// -----------------------------------------------
const uploadArea  = document.getElementById('uploadArea');
const fileInput   = document.getElementById('fileInput');
const previewImg  = document.getElementById('previewImg');
const uploadPlaceholder = document.getElementById('uploadPlaceholder');

if (uploadArea) {

  // Klik area untuk memilih file
  uploadArea.addEventListener('click', () => fileInput.click());

  // Drag & Drop
  uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('drag-over');
  });

  uploadArea.addEventListener('dragleave', function() {
    this.classList.remove('drag-over');
  });

  uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) handleFileSelect(file);
  });

  // Input file berubah
  fileInput && fileInput.addEventListener('change', function() {
    if (this.files[0]) handleFileSelect(this.files[0]);
  });
}

function handleFileSelect(file) {
  // Validasi tipe file
  const allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!allowed.includes(file.type)) {
    showToast('Format tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.', 'error');
    return;
  }

  // Validasi ukuran (maks 5MB)
  if (file.size > 5 * 1024 * 1024) {
    showToast('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
    return;
  }

  // Tampilkan preview
  const reader = new FileReader();
  reader.onload = function(e) {
    if (previewImg) {
      previewImg.src = e.target.result;
      previewImg.style.display = 'block';
    }
    if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
  };
  reader.readAsDataURL(file);

  // Transfer ke input file form yang sebenarnya
  const dt = new DataTransfer();
  dt.items.add(file);
  if (fileInput) fileInput.files = dt.files;
}

// Validasi form upload sebelum submit
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
  uploadForm.addEventListener('submit', function(e) {
    const title = document.getElementById('pinTitle');
    const img   = fileInput;

    if (!img || img.files.length === 0) {
      e.preventDefault();
      showToast('Pilih gambar terlebih dahulu!', 'error');
      return;
    }

    if (!title || title.value.trim() === '') {
      e.preventDefault();
      showToast('Judul pin tidak boleh kosong!', 'error');
      return;
    }

    // Tampilkan loading state
    const submitBtn = this.querySelector('[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '⏳ Mengupload...';
    }
  });
}

// -----------------------------------------------
// HALAMAN DETAIL PIN — KOMENTAR
// -----------------------------------------------
const commentForm  = document.getElementById('commentForm');
const commentInput = document.getElementById('commentInput');
const commentList  = document.getElementById('commentList');

if (commentForm) {
  commentForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const text = commentInput.value.trim();
    if (!text) return;

    const pinId    = this.dataset.pinId;
    const parentId = this.dataset.parentId || '';

    // Disable tombol saat mengirim
    const sendBtn = this.querySelector('.comment-send');
    if (sendBtn) sendBtn.disabled = true;

    fetch('php/add_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `pin_id=${pinId}&content=${encodeURIComponent(text)}&parent_id=${parentId}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'ok') {
        commentInput.value = '';
        // Tambahkan komentar baru ke DOM
        appendComment(data.comment, parentId);

        // Reset parent_id jika ini reply
        if (parentId) {
          commentForm.dataset.parentId = '';
          const replyInfo = document.getElementById('replyInfo');
          if (replyInfo) replyInfo.remove();
        }

        showToast('Komentar terkirim! 💬', 'success', 1500);
      } else {
        showToast(data.message || 'Gagal mengirim komentar', 'error');
      }
    })
    .catch(() => showToast('Koneksi error', 'error'))
    .finally(() => {
      if (sendBtn) sendBtn.disabled = false;
    });
  });
}

function appendComment(c, parentId = null) {
  const html = `
    <div class="comment-item" id="comment-${c.id}">
      <div class="comment-av-default" style="background:${c.color}">
        ${c.username.slice(0,2).toUpperCase()}
      </div>
      <div class="comment-bubble">
        <div class="comment-username">@${escapeHtml(c.username)}</div>
        <div class="comment-text">${escapeHtml(c.content)}</div>
        <div class="comment-meta">
          <span>Baru saja</span>
          <button class="comment-reply-btn" onclick="startReply(${c.id}, '${escapeHtml(c.username)}')">
            Balas
          </button>
        </div>
      </div>
    </div>`;

  if (parentId) {
    // Tambahkan ke dalam replies dari parent
    let repliesContainer = document.getElementById(`replies-${parentId}`);
    if (!repliesContainer) {
      repliesContainer = document.createElement('div');
      repliesContainer.className = 'comment-replies';
      repliesContainer.id = `replies-${parentId}`;
      const parentComment = document.getElementById(`comment-${parentId}`);
      if (parentComment) parentComment.after(repliesContainer);
    }
    repliesContainer.insertAdjacentHTML('beforeend', html);
  } else {
    if (commentList) {
      commentList.insertAdjacentHTML('afterbegin', html);
    }
  }

  // Update jumlah komentar
  const countEl = document.getElementById('commentCount');
  if (countEl) {
    countEl.textContent = parseInt(countEl.textContent || 0) + 1;
  }
}

function startReply(parentId, username) {
  if (!commentForm) return;

  commentForm.dataset.parentId = parentId;
  commentInput.focus();
  commentInput.placeholder = `Membalas @${username}...`;

  // Tampilkan info sedang reply
  let replyInfo = document.getElementById('replyInfo');
  if (!replyInfo) {
    replyInfo = document.createElement('div');
    replyInfo.id = 'replyInfo';
    replyInfo.style.cssText = 'font-size:13px;color:var(--accent);margin-bottom:6px;display:flex;align-items:center;gap:8px;';
    commentForm.parentNode.insertBefore(replyInfo, commentForm);
  }
  replyInfo.innerHTML = `
    ↩️ Membalas komentar @${escapeHtml(username)}
    <button onclick="cancelReply()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:12px;">Batal</button>
  `;
}

function cancelReply() {
  if (!commentForm) return;
  commentForm.dataset.parentId = '';
  commentInput.placeholder = 'Tulis komentar...';
  const replyInfo = document.getElementById('replyInfo');
  if (replyInfo) replyInfo.remove();
}

// Hapus komentar
function deleteComment(commentId) {
  if (!confirm('Hapus komentar ini?')) return;

  fetch('php/delete_comment.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `comment_id=${commentId}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const el = document.getElementById(`comment-${commentId}`);
      if (el) el.remove();
      showToast('Komentar dihapus', 'success', 1500);
    }
  });
}

// -----------------------------------------------
// PROFIL — Edit Avatar Live Preview
// -----------------------------------------------
const avatarFileInput   = document.getElementById('avatarFileInput');
const avatarPreview     = document.getElementById('avatarPreview');

if (avatarFileInput) {
  avatarFileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
      showToast('File harus berupa gambar', 'error');
      return;
    }

    const reader = new FileReader();
    reader.onload = e => {
      if (avatarPreview) {
        avatarPreview.src = e.target.result;
        avatarPreview.style.display = 'block';
        const defaultAv = document.getElementById('avatarDefault');
        if (defaultAv) defaultAv.style.display = 'none';
      }
    };
    reader.readAsDataURL(file);
  });
}

// -----------------------------------------------
// PROFIL TABS (Pin / Board / Tersimpan)
// -----------------------------------------------
document.querySelectorAll('.profile-tab').forEach(tab => {
  tab.addEventListener('click', function() {
    const target = this.dataset.tab;

    // Update tab aktif
    document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');

    // Tampilkan konten yang sesuai
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    const targetEl = document.getElementById('tab-' + target);
    if (targetEl) targetEl.classList.remove('hidden');
  });
});

// -----------------------------------------------
// KARAKTER COUNTER (textarea)
// -----------------------------------------------
document.querySelectorAll('[data-maxlength]').forEach(el => {
  const max     = parseInt(el.dataset.maxlength);
  const counter = document.getElementById(el.id + 'Counter');

  el.addEventListener('input', function() {
    const len = this.value.length;
    if (counter) {
      counter.textContent = `${len}/${max}`;
      counter.style.color = len > max * 0.9 ? 'var(--accent)' : 'var(--text-muted)';
    }
    if (len > max) this.value = this.value.slice(0, max);
  });
});
