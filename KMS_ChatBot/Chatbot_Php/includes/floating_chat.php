<!-- Floating Chat Button with Animated Popout (Modern, Beautiful, Small, Suggestion Bubble) -->
<div id="floating-chat-group">
  <button id="main-chat-btn" class="chat-float-btn animate-glow" title="Liên hệ nhanh">
    <i class="fas fa-comments"></i>
  </button>
  <div id="chat-suggestion-bubble" class="chat-suggestion-bubble" style="display:none;"></div>
  <div id="chat-actions" class="chat-actions">
    <a href="https://zalo.me/" target="_blank" class="chat-action-btn chat-zalo" title="Chat Zalo"><img src="/assets/img/zalo-icon.png" alt="Zalo" style="width:22px;height:22px;"></a>
    <a href="https://facebook.com/" target="_blank" class="chat-action-btn chat-fb" title="Chat Facebook"><i class="fab fa-facebook-messenger"></i></a>
    <button class="chat-action-btn chat-ai" title="Chat với AI" onclick="window.dispatchEvent(new Event('open-ai-chatbox'));"><i class="fas fa-robot"></i></button>
  </div>
</div>
<style>
#floating-chat-group {
  position: fixed;
  bottom: 32px;
  right: 32px;
  z-index: 9999;
}
.chat-float-btn {
  background: linear-gradient(135deg,#1976d2 0%,#1ec0f7 100%);
  border: none;
  border-radius: 50%;
  width: 64px;
  height: 64px;
  box-shadow: 0 0 0 0 #1ec0f7, 0 4px 16px rgba(25,118,210,0.13);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.8rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
  position: relative;
  filter: drop-shadow(0 0 8px #1ec0f7aa);
  z-index: 2;
}
.animate-glow {
  animation: glow 2.2s infinite alternate;
}
@keyframes glow {
  0% { box-shadow: 0 0 0 0 #1ec0f7, 0 4px 16px rgba(25,118,210,0.13); }
  100% { box-shadow: 0 0 12px 6px #1ec0f799, 0 4px 16px rgba(25,118,210,0.13); }
}
.chat-suggestion-bubble {
  position: absolute;
  right: 74px;
  bottom: 0;
  background: linear-gradient(90deg,#1ec0f7 0%,#1976d2 100%);
  color: #fff;
  font-size: 1.1rem;
  padding: 10px 22px;
  border-radius: 20px 20px 20px 0;
  box-shadow: 0 4px 16px rgba(30,192,247,0.15);
  white-space: nowrap;
  opacity: 0.97;
  z-index: 10;
  animation: fadeInBubble 0.5s;
  pointer-events: none;
}
@keyframes fadeInBubble {
  from { opacity: 0; transform: translateY(10px) scale(0.95); }
  to { opacity: 0.97; transform: translateY(0) scale(1); }
}
.chat-actions {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 0;
  height: 0;
  pointer-events: none;
  z-index: 1;
}
.chat-action-btn {
  position: absolute;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg,#1976d2 0%,#1ec0f7 100%);
  color: #fff;
  box-shadow: 0 4px 12px rgba(30,192,247,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  opacity: 0;
  transform: scale(0.5);
  transition: all 0.35s cubic-bezier(.68,-0.55,.27,1.55);
  pointer-events: none;
  border: none;
  outline: none;
  filter: drop-shadow(0 0 6px #1ec0f7aa);
  z-index: 1;
}
#floating-chat-group.open .chat-action-btn {
  opacity: 1;
  transform: scale(1);
  pointer-events: auto;
}
#floating-chat-group.open .chat-zalo {
  right: 74px;
  bottom: 0;
  transition-delay: 0.05s;
}
#floating-chat-group.open .chat-fb {
  right: 48px;
  bottom: 48px;
  transition-delay: 0.12s;
}
#floating-chat-group.open .chat-ai {
  right: 0;
  bottom: 74px;
  transition-delay: 0.18s;
}
.chat-action-btn:hover {
  background: linear-gradient(135deg,#1ec0f7 0%,#1976d2 100%);
  color: #fff;
  box-shadow: 0 8px 24px rgba(30,192,247,0.2);
  filter: drop-shadow(0 0 10px #1ec0f7cc);
  transform: translateY(-2px);
}
.chat-action-btn[title]:hover:after {
  content: attr(title);
  position: absolute;
  left: 50%;
  top: -36px;
  transform: translateX(-50%);
  background: #1976d2;
  color: #fff;
  padding: 6px 14px;
  border-radius: 10px;
  font-size: 0.95rem;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgba(30,192,247,0.15);
  opacity: 0.95;
  pointer-events: none;
  z-index: 10;
}
@media (max-width: 600px) {
  #floating-chat-group { right: 16px; bottom: 16px; }
  .chat-float-btn { width:56px;height:56px;font-size:1.5rem; }
  .chat-action-btn { width:42px;height:42px;font-size:1.2rem; }
  #floating-chat-group.open .chat-zalo { right: 64px; }
  #floating-chat-group.open .chat-fb { right: 42px; bottom: 42px; }
  #floating-chat-group.open .chat-ai { bottom: 64px; }
  .chat-suggestion-bubble { right: 64px; font-size:1rem; padding:8px 16px; }
}
</style>
<script>
(function(){
  var group = document.getElementById('floating-chat-group');
  var mainBtn = document.getElementById('main-chat-btn');
  var bubble = document.getElementById('chat-suggestion-bubble');
  var suggestions = [
    'Bạn cần tư vấn sức khỏe?',
    'Chat với AI để được hỗ trợ!',
    'Kết nối Zalo, Facebook dễ dàng!',
    'Đặt câu hỏi cho chuyên gia!',
    'Nhận tư vấn miễn phí ngay!',
    'Bạn muốn đặt lịch khám?',
    'Hỏi đáp nhanh với AI!',
    'Hỗ trợ 24/7, click để chat!'
  ];
  function showBubble() {
    var msg = suggestions[Math.floor(Math.random()*suggestions.length)];
    bubble.textContent = msg;
    bubble.style.display = 'block';
    setTimeout(function(){ bubble.style.display = 'none'; }, 4000);
  }
  // Hiện bubble mỗi 15s
  setInterval(showBubble, 15000);
  // Hiện ngay khi load lần đầu
  setTimeout(showBubble, 2000);
  mainBtn.onclick = function(e) {
    e.stopPropagation();
    group.classList.toggle('open');
  };
  document.addEventListener('click', function(e) {
    if (!group.contains(e.target)) {
      group.classList.remove('open');
    }
  });
})();
</script> 
