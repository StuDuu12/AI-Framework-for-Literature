/**
 * ============================================
 * CLASS LIST ENHANCED - JavaScript
 * Xử lý Sort, Filter, View Toggle
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // VIEW TOGGLE - Grid/List View
    // ==========================================
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
    const classList = document.querySelector('.class-list');

    viewToggleBtns.forEach((btn) => {
        btn.addEventListener('click', function () {
            // Remove active from all buttons
            viewToggleBtns.forEach((b) => b.classList.remove('active'));
            // Add active to clicked button
            this.classList.add('active');

            const view = this.getAttribute('data-view');
            if (view === 'grid') {
                classList.classList.remove('class-list-list');
                classList.classList.add('class-list-grid');
            } else {
                classList.classList.remove('class-list-grid');
                classList.classList.add('class-list-list');
            }

            // Save preference to localStorage
            localStorage.setItem('classListView', view);
        });
    });

    // Load saved view preference
    const savedView = localStorage.getItem('classListView') || 'grid';
    const activeViewBtn = document.querySelector(`.view-toggle-btn[data-view="${savedView}"]`);
    if (activeViewBtn) {
        activeViewBtn.click();
    }

    // ==========================================
    // SORT DROPDOWN
    // ==========================================
    const sortBtn = document.querySelector('.sort-btn');
    const sortMenu = document.querySelector('.sort-menu');
    const sortOptions = document.querySelectorAll('.sort-option');

    if (sortBtn && sortMenu) {
        sortBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sortMenu.classList.toggle('show');
            // Close filter menu if open
            const filterMenu = document.querySelector('.filter-menu');
            if (filterMenu) {
                filterMenu.classList.remove('show');
            }
        });

        sortOptions.forEach((option) => {
            option.addEventListener('click', function () {
                // Remove active from all
                sortOptions.forEach((opt) => opt.classList.remove('active'));
                // Add active to clicked
                this.classList.add('active');

                const sortType = this.getAttribute('data-sort');
                sortClassList(sortType);

                // Update button text
                const sortText = this.querySelector('span').textContent;
                sortBtn.querySelector('span').textContent = sortText;

                // Close menu
                sortMenu.classList.remove('show');
            });
        });
    }

    // ==========================================
    // FILTER DROPDOWN
    // ==========================================
    const filterBtn = document.querySelector('.filter-btn');
    const filterMenu = document.querySelector('.filter-menu');
    const filterOptions = document.querySelectorAll('.filter-option');

    if (filterBtn && filterMenu) {
        filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            filterMenu.classList.toggle('show');
            // Close sort menu if open
            const sortMenu = document.querySelector('.sort-menu');
            if (sortMenu) {
                sortMenu.classList.remove('show');
            }
        });

        filterOptions.forEach((option) => {
            option.addEventListener('click', function () {
                // Remove active from all
                filterOptions.forEach((opt) => opt.classList.remove('active'));
                // Add active to clicked
                this.classList.add('active');

                const filterType = this.getAttribute('data-filter');
                filterClassList(filterType);

                // Update button text
                const filterText = this.querySelector('span').textContent;
                filterBtn.querySelector('span').textContent = filterText;

                // Close menu
                filterMenu.classList.remove('show');
            });
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function () {
        if (sortMenu) sortMenu.classList.remove('show');
        if (filterMenu) filterMenu.classList.remove('show');
    });

    // ==========================================
    // SEARCH FUNCTIONALITY
    // ==========================================
    const searchInput = document.querySelector('.class-search input');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            searchClasses(searchTerm);
        });
    }

    // ==========================================
    // SORT FUNCTION
    // ==========================================
    function sortClassList(sortType) {
        const classCards = Array.from(document.querySelectorAll('.class-card'));
        const container = classList;

        classCards.sort((a, b) => {
            let aValue, bValue;

            switch (sortType) {
                case 'name-asc':
                    aValue = a.querySelector('.class-card-title').textContent.toLowerCase();
                    bValue = b.querySelector('.class-card-title').textContent.toLowerCase();
                    return aValue.localeCompare(bValue);

                case 'name-desc':
                    aValue = a.querySelector('.class-card-title').textContent.toLowerCase();
                    bValue = b.querySelector('.class-card-title').textContent.toLowerCase();
                    return bValue.localeCompare(aValue);

                case 'date-newest':
                    aValue = a.getAttribute('data-created') || '0';
                    bValue = b.getAttribute('data-created') || '0';
                    return bValue.localeCompare(aValue);

                case 'date-oldest':
                    aValue = a.getAttribute('data-created') || '0';
                    bValue = b.getAttribute('data-created') || '0';
                    return aValue.localeCompare(bValue);

                case 'students-most':
                    aValue = parseInt(a.querySelector('.class-stat-value[data-type="students"]')?.textContent || '0');
                    bValue = parseInt(b.querySelector('.class-stat-value[data-type="students"]')?.textContent || '0');
                    return bValue - aValue;

                case 'students-least':
                    aValue = parseInt(a.querySelector('.class-stat-value[data-type="students"]')?.textContent || '0');
                    bValue = parseInt(b.querySelector('.class-stat-value[data-type="students"]')?.textContent || '0');
                    return aValue - bValue;

                default:
                    return 0;
            }
        });

        // Re-append sorted cards with animation
        classCards.forEach((card, index) => {
            card.style.animation = 'none';
            setTimeout(() => {
                card.style.animation = `fadeInUp 0.4s ease ${index * 0.05}s forwards`;
                container.appendChild(card);
            }, 10);
        });
    }

    // ==========================================
    // FILTER FUNCTION
    // ==========================================
    function filterClassList(filterType) {
        const classCards = document.querySelectorAll('.class-card');

        classCards.forEach((card) => {
            const status = card.getAttribute('data-status');
            const role = card.getAttribute('data-role');

            let shouldShow = true;

            switch (filterType) {
                case 'all':
                    shouldShow = true;
                    break;

                case 'active':
                    shouldShow = status === 'active';
                    break;

                case 'archived':
                    shouldShow = status === 'archived';
                    break;

                case 'teacher':
                    shouldShow = role === 'teacher';
                    break;

                case 'student':
                    shouldShow = role === 'student';
                    break;

                default:
                    shouldShow = true;
            }

            if (shouldShow) {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease forwards';
            } else {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });

        // Show empty state if no results
        checkEmptyState();
    }

    // ==========================================
    // SEARCH FUNCTION
    // ==========================================
    function searchClasses(searchTerm) {
        const classCards = document.querySelectorAll('.class-card');

        if (searchTerm === '') {
            // Show all cards
            classCards.forEach((card) => {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease forwards';
            });
        } else {
            // Filter cards based on search
            classCards.forEach((card) => {
                const title = card.querySelector('.class-card-title').textContent.toLowerCase();
                const code = card.querySelector('.class-card-code')?.textContent.toLowerCase() || '';
                const teacher = card.querySelector('.class-info-value[data-type="teacher"]')?.textContent.toLowerCase() || '';

                const matches = title.includes(searchTerm) || code.includes(searchTerm) || teacher.includes(searchTerm);

                if (matches) {
                    card.style.display = '';
                    card.style.animation = 'fadeIn 0.3s ease forwards';
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        }

        // Show empty state if no results
        checkEmptyState();
    }

    // ==========================================
    // EMPTY STATE
    // ==========================================
    function checkEmptyState() {
        const classCards = document.querySelectorAll('.class-card');
        const visibleCards = Array.from(classCards).filter((card) => card.style.display !== 'none' && getComputedStyle(card).display !== 'none');

        let emptyState = document.querySelector('.class-list-empty');

        if (visibleCards.length === 0) {
            if (!emptyState) {
                emptyState = document.createElement('div');
                emptyState.className = 'class-list-empty';
                emptyState.innerHTML = `
                    <i class="bi bi-inbox"></i>
                    <h3>Không tìm thấy lớp học</h3>
                    <p>Thử thay đổi bộ lọc hoặc tìm kiếm khác</p>
                `;
                classList.appendChild(emptyState);
            } else {
                emptyState.style.display = 'block';
            }
        } else {
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    }

    // ==========================================
    // CARD ANIMATIONS
    // ==========================================
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.1,
        },
    );

    // Observe all class cards
    document.querySelectorAll('.class-card').forEach((card) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
});

// ==========================================
// UTILITY ANIMATIONS
// ==========================================
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
