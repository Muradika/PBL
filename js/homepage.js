        // 1. Ambil semua elemen penting
        const searchInput = document.getElementById('searchInput');
        const startDateInput = document.getElementById('startDateInput');
        const endDateInput = document.getElementById('endDateInput');
        const filterButton = document.getElementById('filterButton');
        const filterContainer = document.querySelector('.filter-dropdown-container');
        const filterOptions = document.querySelectorAll('.filter-option');
        const cards = document.querySelectorAll('.announcement-card');

        let currentFilter = 'All'; 
        let currentSearchTerm = ''; 
        // Tanggal akan diambil langsung dari input saat updateCardsVisibility dipanggil

        /**
         * Mengonversi format tanggal non-standar (e.g., "18 Oktober 2025") 
         * menjadi format standar YYYY-MM-DD (e.g., "2025-10-18") untuk perbandingan.
         * * CATATAN PENTING: Untuk mempermudah dan mempercepat, semua data-date pada HTML
         * telah saya ubah secara manual ke format YYYY-MM-DD. Fungsi ini hanya sebagai 
         * referensi jika format tanggal di card *tidak* diubah, namun perbandingan 
         * string dengan YYYY-MM-DD adalah yang paling efisien.
         * * @param {string} dateString - Tanggal dalam format teks.
         * @returns {string} Tanggal dalam format YYYY-MM-DD.
         */
        function parseDate(dateString) {
            // Kita akan menggunakan format YYYY-MM-DD yang sudah ada di data-attribute
            return dateString; 
        }

        // --- FUNGSI UTAMA UNTUK MENGUPDATE TAMPILAN CARD ---
        function updateCardsVisibility() {
            // 1. Ambil nilai filter saat ini
            const searchTermLower = currentSearchTerm.toLowerCase().trim();
            const startDate = startDateInput.value; // Format YYYY-MM-DD atau ""
            const endDate = endDateInput.value;     // Format YYYY-MM-DD atau ""

            cards.forEach(card => {
                const title = card.getAttribute('data-title')?.toLowerCase() || '';
                const category = card.getAttribute('data-category');
                const cardDateString = card.getAttribute('data-date'); // Format YYYY-MM-DD
                
                let matchesSearch = false;
                let matchesCategory = false;
                let matchesDateRange = true;

                // A. Logika Pencarian Teks
                matchesSearch = 
                    title.includes(searchTermLower) || 
                    (category && category.toLowerCase().includes(searchTermLower)) ||
                    cardDateString.includes(searchTermLower); // Memungkinkan pencarian tanggal

                // B. Logika Filter Kategori
                matchesCategory = (currentFilter === 'All' || category === currentFilter);

                // C. Logika Filter Tanggal
                if (cardDateString) {
                    const cardDate = cardDateString; 

                    // C1. Cek Tanggal Mulai
                    if (startDate && cardDate < startDate) {
                        matchesDateRange = false;
                    }

                    // C2. Cek Tanggal Akhir (Hanya jika belum difilter oleh tanggal mulai)
                    if (matchesDateRange && endDate && cardDate > endDate) {
                        matchesDateRange = false;
                    }
                } else {
                    // Jika data-date tidak ada, abaikan filter tanggal
                    matchesDateRange = true;
                }

                // Tampilkan card hanya jika cocok dengan Search AND Kategori AND Tanggal
                if (matchesSearch && matchesCategory && matchesDateRange) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // --- EVENT LISTENER UNTUK SEARCH (KETIKA PENGGUNA MENGETIK) ---
        searchInput.addEventListener('keyup', (e) => {
            currentSearchTerm = e.target.value;
            updateCardsVisibility();
        });

        // --- EVENT LISTENER UNTUK FILTER TANGGAL (KETIKA NILAI BERUBAH) ---
        startDateInput.addEventListener('change', updateCardsVisibility);
        endDateInput.addEventListener('change', updateCardsVisibility);

        // --- EVENT LISTENER UNTUK TOMBOL FILTER (TOGGLE DROPDOWN) ---
        filterButton.addEventListener('click', (e) => {
            e.stopPropagation();
            filterContainer.classList.toggle('show');
        });

        // --- EVENT LISTENER UNTUK PILIHAN FILTER KATEGORI ---
        filterOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                
                // 1. Ambil nilai filter baru
                const newFilter = e.target.getAttribute('data-filter');

                // 2. Update status aktif (visual)
                filterOptions.forEach(opt => opt.classList.remove('active'));
                e.target.classList.add('active');

                // 3. Set filter baru dan update tampilan
                currentFilter = newFilter;
                updateCardsVisibility();
                
                // 4. Tutup dropdown
                filterContainer.classList.remove('show');
            });
        });

        // Tutup dropdown jika klik di luar area filter
        document.addEventListener('click', (e) => {
            if (!filterContainer.contains(e.target) && !filterButton.contains(e.target)) {
                filterContainer.classList.remove('show');
            }
        });

        // Panggil fungsi sekali saat halaman dimuat
        updateCardsVisibility();