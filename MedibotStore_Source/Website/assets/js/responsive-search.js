// Mobile search functionality
function openMobileSearch() {
    const overlay = document.querySelector('.mobile-search-overlay');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        document.querySelector('.mobile-search-input').focus();
    }, 300);
}

function closeMobileSearch() {
    const overlay = document.querySelector('.mobile-search-overlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close search on overlay click
    const overlay = document.querySelector('.mobile-search-overlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeMobileSearch();
            }
        });
    }

    // Handle search input
    const searchInputs = document.querySelectorAll('.search-input, .mobile-search-input');
    searchInputs.forEach(input => {
        let searchTimeout;
        let resultsContainer;

        // Create results container if it doesn't exist
        if (!input.nextElementSibling?.classList.contains('search-results')) {
            resultsContainer = document.createElement('div');
            resultsContainer.className = 'search-results';
            input.parentNode.insertBefore(resultsContainer, input.nextSibling);
        } else {
            resultsContainer = input.nextElementSibling;
        }

        input.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const value = e.target.value.trim();
            
            if (value.length > 2) {
                searchTimeout = setTimeout(() => {
                    // Here you would typically make an AJAX call to your search endpoint
                    // For now, we'll just show a sample result
                    resultsContainer.innerHTML = `
                        <div class="search-result-item">Đang tìm kiếm: "${value}"...</div>
                    `;
                    resultsContainer.classList.add('active');
                }, 300);
            } else {
                resultsContainer.classList.remove('active');
            }
        });

        // Close results when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.remove('active');
            }
        });
    });

    // Handle Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileSearch();
            document.querySelectorAll('.search-results').forEach(container => {
                container.classList.remove('active');
            });
        }
    });
});