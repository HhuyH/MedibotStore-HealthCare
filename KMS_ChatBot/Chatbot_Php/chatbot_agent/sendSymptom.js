// Hàm gọi API PHP gửi triệu chứng mới lên server
function sendSymptom(symptom_id, symptom_name) {
  fetch('Bot_Col_symp.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: new URLSearchParams({
      symptom_id: symptom_id,
      symptom_name: symptom_name
    })
  })
  .then(response => response.text())
  .then(data => {
    console.log('Server trả về:', data);
    // Ở đây có thể cập nhật UI hoặc xử lý tiếp
  })
  .catch(err => console.error('Lỗi gửi symptom:', err));
}


