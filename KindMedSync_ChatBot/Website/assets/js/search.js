document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    let debounceTimer;

    // Xử lý sự kiện nhập liệu
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchSuggestions.classList.remove('active');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, 300);
    });

    // Xử lý sự kiện focus
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            searchSuggestions.classList.add('active');
        }
    });

    // Đóng suggestions khi click ra ngoài
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
            searchSuggestions.classList.remove('active');
        }
    });

    // Fetch suggestions từ server
    async function fetchSuggestions(query) {
        try {
            const response = await fetch(`/includes/ajax/search_suggestions.php?q=${encodeURIComponent(query)}`);
            const suggestions = await response.json();
            
            if (suggestions.length > 0) {
                renderSuggestions(suggestions);
                searchSuggestions.classList.add('active');
            } else {
                searchSuggestions.classList.remove('active');
            }
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            searchSuggestions.classList.remove('active');
        }
    }

    // Render suggestions
    function renderSuggestions(suggestions) {
        searchSuggestions.innerHTML = suggestions.map(item => `
            <a href="${item.url}" class="suggestion-item">
                <div class="suggestion-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="suggestion-content">
                    <div class="suggestion-title">${escapeHtml(item.title)}</div>
                    <div class="suggestion-category">${escapeHtml(item.category)}</div>
                </div>
            </a>
        `).join('');
    }

    // Escape HTML để tránh XSS
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}); 