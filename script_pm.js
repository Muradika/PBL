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