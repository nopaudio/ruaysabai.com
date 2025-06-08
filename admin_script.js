// --- Global Data & Initial Setup ---
let galleryData = window.pageData.gallery || [];
let statsData = {};
let currentUsersPage = 1;
let currentEditingUserId = null;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-btn').forEach(btn => btn.addEventListener('click', e => showSection(e.currentTarget.dataset.section)));
    document.addEventListener('click', e => { if (e.target.classList.contains('modal') || e.target.classList.contains('close-modal')) closeModal(); });
    showSection('dashboard');
});

// --- Core Functions ---
async function apiRequest(action, formData) {
    formData.append('action', action);
    try {
        const response = await fetch('admin.php', { method: 'POST', body: formData });
        return await response.json();
    } catch (e) {
        showAlert('error', 'การเชื่อมต่อล้มเหลว');
        return { success: false };
    }
}

function showSection(sectionId) {
    document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(sectionId)?.classList.add('active');
    document.querySelector(`.nav-btn[data-section="${sectionId}"]`)?.classList.add('active');
    
    if (sectionId === 'dashboard') loadDashboard();
    else if (sectionId === 'users') loadUsers(1);
    else if (sectionId === 'gallery') loadGallery();
}

function showAlert(type, message, duration = 3000) {
    const container = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`; // ใช้ alert-success, alert-error เพื่อให้สีตรงกับ CSS
    alertDiv.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}`;
    container.appendChild(alertDiv);
    // ทำ animation ให้แสดงผล
    setTimeout(() => {
        alertDiv.style.opacity = '1';
        alertDiv.style.transform = 'translateY(0)';
    }, 10);
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        alertDiv.style.transform = 'translateY(-20px)';
        setTimeout(() => alertDiv.remove(), 300);
    }, duration);
}

function closeModal() {
    document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
}

// --- Dashboard ---
async function loadDashboard() {
    const response = await apiRequest('get_data', new FormData());
    if (response.success) {
        statsData = response.data.stats;
        galleryData = response.data.gallery;
        const container = document.getElementById('dashboard');
        container.innerHTML = `<div class="section-title"><span><i class="fas fa-tachometer-alt"></i> สรุปข้อมูลรวม</span></div>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number">${statsData.total_user_prompts || 0}</div><div class="stat-label">Prompts ทั้งหมด</div></div>
            <div class="stat-card"><div class="stat-number">${statsData.today_prompts || 0}</div><div class="stat-label">Prompts วันนี้</div></div>
            <div class="stat-card"><div class="stat-number">${statsData.total_gallery || 0}</div><div class="stat-label">ในแกลเลอรี่</div></div>
            <div class="stat-card"><div class="stat-number">${statsData.total_users || 0}</div><div class="stat-label">สมาชิกทั้งหมด</div></div>
        </div>`;
    }
}

// --- User Management ---
async function loadUsers(page = 1) {
    currentUsersPage = page;
    const formData = new FormData();
    formData.append('page', page);
    formData.append('search', document.getElementById('user-search')?.value || '');
    formData.append('filter', document.getElementById('user-filter')?.value || 'all');
    const response = await apiRequest('get_users', formData);
    if(response.success) renderUsers(response.data);
}

function renderUsers(data) {
    const tbody = document.getElementById('users-tbody');
    tbody.innerHTML = '';
    if (!data.users || data.users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">ไม่พบข้อมูล</td></tr>';
        return;
    }
    data.users.forEach(user => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${user.id}</td>
            <td><img src="${user.avatar_url || 'https://ui-avatars.com/api/?name='+user.username}" class="user-avatar"> <strong>${user.username}</strong><br><small>${user.full_name || ''}</small></td>
            <td><span class="member-badge ${user.member_type}">${user.member_type}</span></td>
            <td>${user.points_balance}</td>
            <td>${user.expire_date ? new Date(user.expire_date).toLocaleDateString('th-TH') : '-'}</td>
            <td><span class="status-badge ${user.status}">${user.status}</span></td>
            <td><button class="btn btn-sm" onclick="openUserModal(${user.id},'${user.username}')"><i class="fas fa-edit"></i></button></td>
        `;
        tbody.appendChild(row);
    });
    // Pagination logic here if needed
}

function searchUsers(page) { loadUsers(page); }

function openUserModal(userId, username) {
    currentEditingUserId = userId;
    const modal = document.getElementById('user-modal');
    modal.innerHTML = `
        <div class="modal-content"><button class="close-modal">&times;</button>
            <div class="modal-header">แก้ไขสมาชิก: ${username} (ID: ${userId})</div>
            <div class="form-group"><label>ประเภทสมาชิก</label><select id="user-member-type"><option value="free">ฟรี</option><option value="monthly">รายเดือน</option><option value="yearly">รายปี</option></select></div>
            <div class="form-group"><label>วันหมดอายุ</label><input type="date" id="user-expire-date"></div>
            <div class="form-group"><label>แต้ม</label><input type="number" id="user-points-balance"></div>
            <button class="btn btn-success" onclick="saveUser()"><i class="fas fa-save"></i> บันทึก</button>
        </div>`;
    modal.classList.add('active');
}

async function saveUser() {
    const formData = new FormData();
    formData.append('user_id', currentEditingUserId);
    formData.append('member_type', document.getElementById('user-member-type').value);
    formData.append('expire_date', document.getElementById('user-expire-date').value);
    formData.append('points_balance', document.getElementById('user-points-balance').value);
    const response = await apiRequest('update_user', formData);
    if(response.success) {
        closeModal();
        loadUsers(currentUsersPage);
    }
    showAlert(response.success ? 'success' : 'error', response.message);
}

// --- Gallery Management ---
function loadGallery() {
    const container = document.getElementById('gallery-list');
    container.innerHTML = '';
    if (galleryData.length === 0) {
        container.innerHTML = '<p class="placeholder-message">ยังไม่มีรายการในแกลเลอรี่</p>';
        return;
    }
    galleryData.forEach(item => {
        const card = document.createElement('div');
        card.className = 'item-card gallery-item-card';
        card.innerHTML = `<img src="${item.image_url}" class="gallery-image-preview" alt="${item.title}" onerror="this.src='https://via.placeholder.com/150?text=No+Image'"><div><div class="item-header"><div class="item-title">${item.title}</div><div class="item-actions"><button class="btn btn-sm" onclick="openGalleryModal(${item.id})"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger" onclick="deleteGalleryItem(${item.id})"><i class="fas fa-trash"></i></button></div></div><div class="item-content">${item.prompt}</div></div>`;
        container.appendChild(card);
    });
}

function openGalleryModal(id = null) {
    const item = id ? galleryData.find(g => g.id == id) : {};
    const modal = document.getElementById('gallery-modal');
    modal.innerHTML = `
        <div class="modal-content"><button class="close-modal">&times;</button>
            <div class="modal-header">${id ? 'แก้ไข' : 'เพิ่ม'}แกลเลอรี่</div>
            <input type="hidden" id="gallery-id" value="${item.id || ''}">
            <div class="form-group"><label>หัวข้อ</label><input type="text" id="gallery-title" value="${item.title || ''}"></div>
            <div class="form-group"><label>รูปภาพ</label><input type="file" id="gallery-image-upload" accept="image/*" onchange="uploadGalleryImage(this)"><div id="gallery-upload-status"></div><input type="hidden" id="gallery-image-url" value="${item.image_url || ''}"><img id="gallery-preview" class="image-preview" src="${item.image_url || ''}" style="display:${item.image_url ? 'block':'none'}"></div>
            <div class="form-group"><label>Prompt</label><textarea id="gallery-prompt">${item.prompt || ''}</textarea></div>
            <button class="btn btn-success" onclick="saveGalleryItem()"><i class="fas fa-save"></i> บันทึก</button>
        </div>`;
    modal.classList.add('active');
}

async function uploadGalleryImage(input) {
    if (!input.files[0]) return;
    const statusEl = document.getElementById('gallery-upload-status');
    statusEl.textContent = 'กำลังอัปโหลด...';
    statusEl.style.color = '#333';

    const formData = new FormData();
    formData.append('image', input.files[0]);

    try {
        const response = await fetch('image_upload.php', { method: 'POST', body: formData });
        const result = await response.json(); // บรรทัดนี้ที่เคย error เพราะ response ไม่ใช่ JSON

        if (result.success) {
            statusEl.textContent = 'สำเร็จ!';
            statusEl.style.color = 'green';
            document.getElementById('gallery-image-url').value = result.data.url;
            const preview = document.getElementById('gallery-preview');
            preview.src = result.data.url;
            preview.style.display = 'block';
        } else {
            statusEl.textContent = 'ล้มเหลว: ' + result.message;
            statusEl.style.color = 'red';
        }
    } catch(e) {
        statusEl.textContent = 'ล้มเหลว: ไม่สามารถประมวลผลการตอบกลับจากเซิร์ฟเวอร์ได้';
        statusEl.style.color = 'red';
        console.error("Upload failed:", e);
    }
}

async function saveGalleryItem() {
    const formData = new FormData();
    formData.append('id', document.getElementById('gallery-id').value);
    formData.append('title', document.getElementById('gallery-title').value);
    formData.append('image_url', document.getElementById('gallery-image-url').value);
    formData.append('prompt', document.getElementById('gallery-prompt').value);

    // ตรวจสอบว่ามี URL รูปภาพหรือไม่
    if (!formData.get('image_url')) {
        showAlert('error', 'กรุณาอัปโหลดรูปภาพก่อน');
        return;
    }

    const response = await apiRequest('save_gallery', formData);
    if(response.success) {
        closeModal();
        // เรียกข้อมูล gallery ใหม่หลังจากบันทึกสำเร็จ
        const dataResponse = await apiRequest('get_data', new FormData());
        if (dataResponse.success) {
            galleryData = dataResponse.data.gallery;
            loadGallery();
        }
    }
    showAlert(response.success ? 'success' : 'error', response.message);
}

async function deleteGalleryItem(id) {
    if(!confirm('ยืนยันการลบรายการนี้?')) return;
    const formData = new FormData();
    formData.append('id', id);
    const response = await apiRequest('delete_gallery', formData);
    if(response.success) {
        // ลบ item ออกจาก array ในหน้าเว็บโดยตรงเพื่อความรวดเร็ว
        galleryData = galleryData.filter(item => item.id != id);
        loadGallery();
    }
    showAlert(response.success ? 'success' : 'error', response.message);
}

// --- Settings ---
async function saveGeneralSettings() {
    const formData = new FormData();
    const settings = {
        'site_title': document.getElementById('site-title').value,
        'site_description': document.getElementById('site-description').value,
        'online_count': document.getElementById('online-count').value,
        'limit_guest': document.getElementById('limit-guest').value,
        'limit_free': document.getElementById('limit-free').value,
        'limit_monthly': document.getElementById('limit-monthly').value,
    };
    for(const key in settings) formData.append(`settings[${key}]`, settings[key]);
    const response = await apiRequest('save_settings', formData);
    showAlert(response.success ? 'success' : 'error', response.message);
}