// Biến lưu vị trí cuộn trước đó
let lastScrollTop = 0;

// Xử lý sự kiện cuộn
window.addEventListener('scroll', function() {
    const headerMobile = document.querySelector('.header-mobile');
    if (!headerMobile) return;

    const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
    
    // Nếu đang cuộn xuống và đã cuộn quá 100px
    if (currentScroll > lastScrollTop && currentScroll > 100) {
        headerMobile.classList.add('hide');
    } 
    // Nếu đang cuộn lên
    else if (currentScroll < lastScrollTop) {
        headerMobile.classList.remove('hide');
    }
    
    lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
}, false);

// Xử lý voice search
document.querySelector('.voice-icon')?.addEventListener('click', function() {
    if ('webkitSpeechRecognition' in window) {
        const recognition = new webkitSpeechRecognition();
        recognition.lang = 'vi-VN';
        recognition.continuous = false;
        recognition.interimResults = false;

        recognition.onresult = function(event) {
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.value = event.results[0][0].transcript;
                searchInput.form.submit();
            }
        };

        recognition.start();
    }
});
