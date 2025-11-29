document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('profile-dropdown-btn');
    const dropdown = document.querySelector('.dropdown');
    const filterButton = document.getElementById("filterButton");
    const filterOptionsContainer = document.getElementById("filterOptions");
    const filterContainer = document.querySelector(".filter-dropdown-container");

    // ========== DROPDOWN PROFILE ==========
    if (dropdownBtn && dropdown) {
        dropdownBtn.addEventListener('click', function(e) {
            e.preventDefault();
            dropdown.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }

    // ========== FILTER DROPDOWN ==========
    if (filterButton && filterContainer) {
        filterButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            filterContainer.classList.toggle("show");
        });
    }

    if (filterOptionsContainer) {
        const options = filterOptionsContainer.querySelectorAll(".filter-option");
        options.forEach((opt) => {
            opt.addEventListener("click", function (e) {
                e.stopPropagation();
                const href = this.getAttribute("href");
                if (href && href !== "#") {
                    window.location.href = href;
                }
            });
        });
    }

    function applyFallbackVisibility() {
        if (!filterContainer || !filterOptionsContainer) return;
        if (filterContainer.classList.contains("show")) {
            filterOptionsContainer.style.display = "block";
            filterOptionsContainer.style.opacity = "1";
            filterOptionsContainer.style.transform = "translateY(0)";
            filterOptionsContainer.style.pointerEvents = "auto";
        } else {
            filterOptionsContainer.style.display = "";
            filterOptionsContainer.style.opacity = "";
            filterOptionsContainer.style.transform = "";
            filterOptionsContainer.style.pointerEvents = "";
        }
    }

    if (filterContainer && filterOptionsContainer && window.MutationObserver) {
        const obs = new MutationObserver(() => applyFallbackVisibility());
        obs.observe(filterContainer, {
            attributes: true,
            attributeFilter: ["class"],
        });
    }

    applyFallbackVisibility();

    document.addEventListener("click", (e) => {
        if (filterContainer && !filterContainer.contains(e.target)) {
            filterContainer.classList.remove("show");
        }
    });

    // ========== DOWNLOAD BUTTON ==========
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const docUrl = this.getAttribute('data-doc');
            if (docUrl && docUrl !== '#') {
                const link = document.createElement('a');
                link.href = docUrl;
                link.download = '';
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                this.style.background = '#28a745';
                setTimeout(() => {
                    this.style.background = '';
                }, 1000);
            }
        });
    });

    // ========== REMOVE FROM FAVORITES ==========
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const announcementId = this.getAttribute('data-id');
            const card = this.closest('.announcement-card');
            
            if (!confirm('Remove this announcement from favorites?')) {
                return;
            }
            
            fetch('../database/favorites_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove&announcement_id=${announcementId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    card.style.transition = 'all 0.3s ease-out';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        const remainingCards = document.querySelectorAll('.announcement-card');
                        if (remainingCards.length === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    showToast('✓ Removed from favorites', 'success');
                } else {
                    showToast('✗ Failed to remove', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('✗ Connection error', 'error');
            });
        });
    });

    // ========== TOAST NOTIFICATION ==========
    function showToast(message, type = 'info') {
        const existingToast = document.querySelector('.custom-toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        toast.textContent = message;
        
        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: 'bold',
            fontSize: '14px',
            zIndex: '9999',
            boxShadow: '0 4px 12px rgba(0,0,0,0.3)',
            animation: 'slideIn 0.3s ease-out',
            maxWidth: '300px'
        });

        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        toast.style.background = colors[type] || colors.info;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    if (!document.querySelector('#toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
});