/*
  Simple local data-driven list with:
  - search filter
  - edit modal (add & update)
  - remove with confirm
  - initial sample data
*/

const rowsEl = document.getElementById('rows');
const searchInput = document.getElementById('searchInput');
const modalBackdrop = document.getElementById('modalBackdrop');
const modalTitle = document.getElementById('modalTitle');
const nameInput = document.getElementById('nameInput');
const idInput = document.getElementById('idInput');
const emailInput = document.getElementById('emailInput');
const roleInput = document.getElementById('roleInput');
const modalAvatar = document.getElementById('modalAvatar');
const avatarFileInput = document.getElementById('avatarFileInput');
const btnSave = document.getElementById('btnSave');
const btnCancel = document.getElementById('btnCancel');
const btnAdd = document.getElementById('btnAdd');

let editingId = null;

document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('profile-dropdown-btn');
    const dropdown = document.querySelector('.dropdown');

    // Fungsi untuk membuka/menutup dropdown
    dropdownBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Mencegah link pindah halaman
        
        // Toggle (menambah/menghapus) class 'active' pada kontainer dropdown
        dropdown.classList.toggle('active');
    });

    // Opsional: Tutup dropdown saat pengguna mengklik di luar area menu
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
});

let users = [
    {id: genId(), name:"Hamim Tohari", idNumber:"20210123", email:"20210123.Hamim@dosen.polibatam.ac.id", role:"Dosen"},
    {id: genId(), name:"Yohan", idNumber:"3312501050", email:"3312501050.Yohan@students.polibatam.ac.id", role:"Mahasiswa"},
    {id: genId(), name:"Hanes", idNumber:"3312501023", email:"3312501023.Hanes@students.polibatam.ac.id", role:"Admin"},
    {id: genId(), name:"Blacky", idNumber:"3312501045", email:"3312501045.Blacky@students.polibatam.ac.id", role:"Mahasiswa"},
    {id: genId(), name:"Hasbiya Abrar", idNumber:"3310501035", email:"3310501035.Hasbi@students.polibatam.ac.id", role:"Mahasiswa"},
    {id: genId(), name:"Wicaksono", idNumber:"20210102", email:"20210102.Wicak@dosen.polibatam.ac.id", role:"Dosen"},
    {id: genId(), name:"Nugroho", idNumber:"20210106", email:"20210106.Nugroho1234@dosen.polibatam.ac.id", role:"Dosen"},
];

// load avatars map from localStorage: { userId: dataUrl }
let avatars = {};
try { avatars = JSON.parse(localStorage.getItem('users_avatars') || '{}'); } catch(e){ avatars = {}; }

function genId(){ return 'u_' + Math.random().toString(36).slice(2,9); }

function renderRows(filter=''){
    rowsEl.innerHTML = '';
    const q = filter.trim().toLowerCase();
    const list = users.filter(u => {
        if(!q) return true;
        return [u.name,u.idNumber,u.email,u.role].join(' ').toLowerCase().includes(q);
    });

    if(list.length===0){
        const empty = document.createElement('div');
        empty.className = 'row';
        empty.style.justifyContent='center';
        empty.textContent = 'No users found.';
        rowsEl.appendChild(empty);
        return;
    }

    for(const u of list){
        const r = document.createElement('div');
        r.className = 'row';
        r.dataset.id = u.id;

        const av = document.createElement('div');
        av.className = 'avatar';
        // if avatar exists in map, use it as background image
        if(avatars[u.id]){
            av.style.backgroundImage = `url(${avatars[u.id]})`;
            av.style.backgroundSize = 'cover';
            av.style.color = 'transparent';
            av.textContent = '';
        } else {
            av.textContent = initials(u.name);
        }

        const name = document.createElement('div');
        name.className = 'name'; name.textContent = u.name;

        const idnum = document.createElement('div');
        idnum.className = 'idnum'; idnum.textContent = u.idNumber;

        const email = document.createElement('div');
        email.className = 'email'; email.textContent = u.email;

        const role = document.createElement('div');
        role.className = 'role'; role.textContent = u.role;

        const actions = document.createElement('div');
        actions.className = 'actions-cell'; // Menggunakan class actions-cell dari CSS
        // actions.style.display='flex'; actions.style.gap='8px'; actions.style.justifyContent='flex-end'; // Tidak perlu jika actions-cell ada

        const btnRemove = document.createElement('button');
        btnRemove.className='btn-remove';
        btnRemove.textContent='Remove';
        btnRemove.onclick = ()=> removeUser(u.id);

        const btnEdit = document.createElement('button');
        btnEdit.className='btn-edit';
        btnEdit.textContent='Edit';
        btnEdit.onclick = ()=> openEditModal(u.id);

        actions.appendChild(btnRemove);
        actions.appendChild(btnEdit);

        r.appendChild(av);
        r.appendChild(name);
        r.appendChild(idnum);
        r.appendChild(email);
        r.appendChild(role);
        r.appendChild(actions);

        rowsEl.appendChild(r);
    }
}

function initials(name){
    return name.split(' ').slice(0,2).map(x=>x[0] ? x[0].toUpperCase() : '').join('');
}

function openEditModal(id){
    editingId = id;
    const u = users.find(x=>x.id===id);
    if(!u) return;
    modalTitle.textContent = 'Edit User';
    nameInput.value = u.name;
    idInput.value = u.idNumber;
    emailInput.value = u.email;
    roleInput.value = u.role;
    // show avatar or initials
    if(avatars[u.id]){
        modalAvatar.style.backgroundImage = `url(${avatars[u.id]})`;
        modalAvatar.style.backgroundSize = 'cover';
        modalAvatar.textContent = '';
    } else {
        modalAvatar.style.backgroundImage = '';
        modalAvatar.textContent = initials(u.name);
    }
    showModal();
}

function openAddModal(){
    editingId = null;
    modalTitle.textContent = 'Add User';
    nameInput.value = '';
    idInput.value = '';
    emailInput.value = '';
    roleInput.value = 'Mahasiswa'; // Default role
    modalAvatar.textContent = 'U';
    modalAvatar.style.backgroundImage = '';
    showModal();
}

function showModal(){
    modalBackdrop.style.display = 'flex';
    nameInput.focus();
}

function hideModal(){
    modalBackdrop.style.display = 'none';
}

btnAdd.addEventListener('click', openAddModal);
btnCancel.addEventListener('click', hideModal);
modalBackdrop.addEventListener('click', (e)=>{
    if(e.target===modalBackdrop) hideModal();
});

btnSave.addEventListener('click', ()=>{
    const name = nameInput.value.trim();
    const idNumber = idInput.value.trim();
    const email = emailInput.value.trim();
    const role = roleInput.value;

    if(!name || !idNumber || !email){
        alert('Isi semua field (Name, ID Number, Email).');
        return;
    }

    if(editingId){
        // update
        const u = users.find(x=>x.id===editingId);
        if(u){
        u.name = name; u.idNumber = idNumber; u.email = email; u.role = role;
        }
    } else {
        // add
        const newId = genId();
        users.unshift({id: newId, name, idNumber, email, role});
        // if modalAvatar has an image (data-url) and we were adding, save it for the new user
        const bg = modalAvatar.style.backgroundImage;
        if(bg && bg.startsWith('url(')){
            const dataUrl = bg.slice(5, -2);
            avatars[newId] = dataUrl;
            localStorage.setItem('users_avatars', JSON.stringify(avatars));
        }
    }
    renderRows(searchInput.value);
    hideModal();
});

// allow clicking the avatar in modal to trigger file input
modalAvatar.addEventListener('click', ()=> avatarFileInput.click());

avatarFileInput.addEventListener('change', function(){
    const file = this.files && this.files[0];
    if(!file) return;
    // validate type and size (max 2MB)
    if(!file.type.startsWith('image/')){ alert('Pilih file gambar.'); return; }
    if(file.size > 2*1024*1024){ alert('Ukuran file terlalu besar (maks 2MB).'); return; }

    const reader = new FileReader();
    reader.onload = function(ev){
        const dataUrl = ev.target.result;
        // preview in modal
        modalAvatar.style.backgroundImage = `url(${dataUrl})`;
        modalAvatar.style.backgroundSize = 'cover';
        modalAvatar.textContent = '';

        // if editing existing user, persist immediately to avatars map
        if(editingId){
            avatars[editingId] = dataUrl;
            localStorage.setItem('users_avatars', JSON.stringify(avatars));
            // also update rows immediately
            renderRows(searchInput.value);
        }
    };
    reader.readAsDataURL(file);
    // reset input so same file can be chosen again if needed
    this.value = '';
});

// remove with confirmation
function removeUser(id){
    const u = users.find(x=>x.id===id);
    if(!u) return;
    const confirmDel = confirm(`Hapus user "${u.name}"?`);
    if(!confirmDel) return;
    users = users.filter(x=>x.id!==id);
    // remove avatar too
    delete avatars[id];
    localStorage.setItem('users_avatars', JSON.stringify(avatars)); 
    renderRows(searchInput.value);
}

// search filter
searchInput.addEventListener('input', ()=> renderRows(searchInput.value));

// initial render
renderRows();

/* keyboard: Enter on search focuses first result (UX nicety) */
searchInput.addEventListener('keydown', (e)=>{
    if(e.key==='Enter'){
    const first = rowsEl.querySelector('.row');
    if(first) first.scrollIntoView({behavior:'smooth',block:'center'});
}
});