document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById("logoutBtn");
    
    if (logoutBtn) {
        logoutBtn.addEventListener("click", async () => {
            const btn = logoutBtn;
            const originalText = btn.innerHTML;
            
            // Hiển thị loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang đăng xuất...';
            btn.disabled = true;
            
            try {
                // Gọi logout.php để server logout
                const response = await fetch("logout.php", {
                    method: "POST",
                    credentials: "include" // giữ cookie/session
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Đóng modal xác nhận
                    const logoutModal = bootstrap.Modal.getInstance(document.getElementById('logoutModal'));
                    if (logoutModal) {
                        logoutModal.hide();
                    }
                    
                    // Xóa localStorage
                    localStorage.removeItem("userInfo");
                    
                    // Hiển thị modal thành công
                    const successModal = new bootstrap.Modal(document.getElementById('logoutSuccessModal'));
                    successModal.show();
                    
                    // Redirect sau 1.5 giây
                    setTimeout(() => {
                        window.location.href = "login.php";
                    }, 1500);
                    
                } else {
                    throw new Error('Đăng xuất thất bại');
                }
                
            } catch (err) {
                console.error("Lỗi đăng xuất:", err);
                alert("Có lỗi khi logout: " + err.message);
                
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }
});
  