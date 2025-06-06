document.getElementById("logoutBtn").addEventListener("click", async () => {
  try {
    // Gọi logout.php để server logout
    const response = await fetch("logout.php", {
      method: "POST",
      credentials: "include" // giữ cookie/session
    });

    if (!response.ok) throw new Error("Logout không thành công");

    // Xóa localStorage
    localStorage.removeItem("userInfo");

    // Redirect về trang login
    window.location.href = "login.php";
  } catch (err) {
    alert("Có lỗi khi logout: " + err.message);
  }
});
