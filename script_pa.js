const rowsEl = document.getElementById('rows');
const searchInput = document.getElementById('searchInput');
const modalBackdrop = document.getElementById('modalBackdrop');
const modalTitle = document.getElementById('modalTitle');
const nameInput = document.getElementById('nameInput');
const idInput = document.getElementById('idInput');
const emailInput = document.getElementById('emailInput');
const roleInput = document.getElementById('roleInput');
const modalAvatar = document.getElementById('modalAvatar');
const btnSave = document.getElementById('btnSave');
const btnCancel = document.getElementById('btnCancel');
const btnAdd = document.getElementById('btnAdd');

let editingId = null;

let users = [
    {id: genId(), name:"Hamim Tohari", idNumber:"55123476", email:"hamim@gmail.com", role:"Dosen"},
    {id: genId(), name:"Yohan", idNumber:"55126534", email:"yohan@gmail.com ", role:"Mahasiswa"},
    {id: genId(), name:"Hanes", idNumber:"6674328", email:"hanes@gmail.com ", role:"Admin"},
    {id: genId(), name:"Blacky", idNumber:"20210104", email:"blacky@gmail.com", role:"Mahasiswa"},
    {id: genId(), name:"Hasbiya Abrar", idNumber:"220906", email:"hasbi@gmail.com", role:"Mahasiswa"},
    {id: genId(), name:"Wicaksono", idNumber:"20210106", email:"wicak77@gmail.com", role:"Dosen"},
    {id: genId(), name:"Nugroho", idNumber:"20210106", email:"nugroho1234@gmail.com", role:"Dosen"},
];

function genId(){ return 'u_' + Math.random().toString(36).slice(2,9); }

function initials(name){
    return name.split(' ').slice(0,2).map(x=>x[0] ? x[0].toUpperCase() : '').join('');
}

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
        av.textContent = initials(u.name);

        const name = document.createElement('div');
        name.className = 'name'; name.textContent = u.name;

        const idnum = document.createElement('div');
        idnum.className = 'idnum'; idnum.textContent = u.idNumber;

        const email = document.createElement('div');
        email.className = 'email'; email.textContent = u.email;

        const role = document.createElement('div');
        role.className = 'role'; role.textContent = u.role;

        const actions = document.createElement('div');
        actions.style.display='flex'; actions.style.gap='8px'; actions.style.justifyContent='flex-end';

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

function openEditModal(id){
    editingId = id;
    const u = users.find(x=>x.id===id);
    if(!u) return;
    modalTitle.textContent = 'Edit User';
    nameInput.value = u.name;
    idInput.value = u.idNumber;
    emailInput.value = u.email;
    roleInput.value = u.role;
    modalAvatar.textContent = initials(u.name);
    showModal();
}

function openAddModal(){
    editingId = null;
    modalTitle.textContent = 'Add User';
    nameInput.value = '';
    idInput.value = '';
    emailInput.value = '';
    roleInput.value = 'User';
    modalAvatar.textContent = 'U';
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
        users.unshift({id: genId(), name, idNumber, email, role});
    }
    renderRows(searchInput.value);
    hideModal();
});

// remove with confirmation
function removeUser(id){
    const u = users.find(x=>x.id===id);
    if(!u) return;
    const confirmDel = confirm(`Hapus user "${u.name}"?`);
    if(!confirmDel) return;
    users = users.filter(x=>x.id!==id);
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