<!-- Premium Floating Chat Widget -->
<div id="floating-chat-widget">
  <!-- Main Chat Button -->
  <div id="main-chat-container" class="main-chat-container">
    <div class="chat-ripple-effect"></div>
    <button id="main-chat-btn" class="main-chat-btn" title="Tư vấn sức khỏe">
      <div class="chat-icon-wrapper">
        <i class="fas fa-stethoscope"></i>
        <div class="notification-dot"></div>
      </div>
      <span class="chat-text">Tư vấn</span>
    </button>
  </div>

  <!-- Suggestion Bubble -->
  <div id="chat-bubble" class="chat-bubble" style="display: none;">
    <div class="bubble-content">
      <span class="bubble-text"></span>
      <div class="bubble-close" onclick="hideBubble()">×</div>
    </div>
    <div class="bubble-arrow"></div>
  </div>

  <!-- Chat Options Menu -->
  <div id="chat-options" class="chat-options">
    <div class="chat-option chat-ai" onclick="openAIChat()">
      <div class="option-icon">
        <i class="fas fa-robot"></i>
      </div>
      <span class="option-label">AI Tư vấn</span>
    </div>
    
    <div class="chat-option chat-zalo" onclick="openZalo()">
      <div class="option-icon">
        <i class="fab fa-telegram"></i>
      </div>
      <span class="option-label">Zalo Chat</span>
    </div>
    
    <div class="chat-option chat-messenger" onclick="openMessenger()">
      <div class="option-icon">
        <i class="fab fa-facebook-messenger"></i>
      </div>
      <span class="option-label">Messenger</span>
    </div>
    
    <div class="chat-option chat-phone" onclick="makeCall()">
      <div class="option-icon">
        <i class="fas fa-phone"></i>
      </div>
      <span class="option-label">Gọi ngay</span>
    </div>
  </div>
</div>
<style>
/* === PREMIUM FLOATING CHAT WIDGET === */
#floating-chat-widget {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Main Chat Container */
.main-chat-container {
  position: relative;
  display: inline-block;
}

.chat-ripple-effect {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 120px;
  height: 120px;
  background: radial-gradient(circle, 
    rgba(255, 107, 107, 0.15) 0%, 
    rgba(78, 205, 196, 0.1) 30%,
    rgba(69, 183, 209, 0.08) 60%,
    transparent 80%);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  animation: rainbowRipple 4s infinite;
  pointer-events: none;
}

@keyframes rainbowRipple {
  0% { 
    transform: translate(-50%, -50%) scale(0.7); 
    opacity: 1;
    background: radial-gradient(circle, 
      rgba(255, 107, 107, 0.15) 0%, 
      transparent 70%);
  }
  25% { 
    background: radial-gradient(circle, 
      rgba(78, 205, 196, 0.15) 0%, 
      transparent 70%);
  }
  50% { 
    transform: translate(-50%, -50%) scale(1.3); 
    opacity: 0.6;
    background: radial-gradient(circle, 
      rgba(69, 183, 209, 0.15) 0%, 
      transparent 70%);
  }
  75% { 
    background: radial-gradient(circle, 
      rgba(254, 202, 87, 0.15) 0%, 
      transparent 70%);
  }
  100% { 
    transform: translate(-50%, -50%) scale(1.8); 
    opacity: 0;
    background: radial-gradient(circle, 
      rgba(150, 206, 180, 0.15) 0%, 
      transparent 70%);
  }
}

/* Main Chat Button */
.main-chat-btn {
  position: relative;
  background: linear-gradient(135deg, 
    #ff6b6b 0%, 
    #4ecdc4 25%, 
    #45b7d1 50%, 
    #96ceb4 75%, 
    #feca57 100%);
  border: none;
  border-radius: 20px;
  padding: 14px 24px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 
    0 8px 32px rgba(255, 107, 107, 0.3),
    0 4px 16px rgba(78, 205, 196, 0.2),
    0 0 0 1px rgba(255, 255, 255, 0.2) inset;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(15px);
  overflow: hidden;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.main-chat-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, 
    transparent, 
    rgba(255, 255, 255, 0.3), 
    transparent);
  transition: left 0.6s;
}

.main-chat-btn:hover::before {
  left: 100%;
}

.main-chat-btn:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 
    0 12px 40px rgba(255, 107, 107, 0.4),
    0 6px 20px rgba(78, 205, 196, 0.3),
    0 0 0 1px rgba(255, 255, 255, 0.3) inset;
  background: linear-gradient(135deg, 
    #ff5252 0%, 
    #26d0ce 25%, 
    #2196f3 50%, 
    #4caf50 75%, 
    #ff9800 100%);
}

.main-chat-btn:active {
  transform: translateY(0);
  transition: transform 0.1s;
}

/* Chat Icon Wrapper */
.chat-icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-icon-wrapper i {
  font-size: 20px;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Notification Dot */
.notification-dot {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid white;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-3px); }
  60% { transform: translateY(-2px); }
}

/* Chat Text */
.chat-text {
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.02em;
}

/* Chat Bubble */
.chat-bubble {
  position: absolute;
  bottom: 70px;
  right: 0;
  background: linear-gradient(135deg, 
    rgba(255, 235, 238, 0.95) 0%, 
    rgba(240, 253, 252, 0.9) 30%,
    rgba(235, 248, 255, 0.9) 70%,
    rgba(254, 252, 232, 0.95) 100%);
  color: #1e293b;
  border-radius: 24px 24px 24px 8px;
  min-width: 240px;
  max-width: 320px;
  box-shadow: 
    0 25px 80px rgba(255, 107, 107, 0.12),
    0 10px 40px rgba(78, 205, 196, 0.08),
    0 5px 20px rgba(69, 183, 209, 0.06),
    0 0 0 1px rgba(255, 107, 107, 0.1) inset;
  backdrop-filter: blur(35px);
  animation: slideInBubble 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
  z-index: 1002;
  border: 2px solid transparent;
  background-clip: padding-box;
}

@keyframes slideInBubble {
  from {
    opacity: 0;
    transform: translateY(10px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.bubble-content {
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.bubble-text {
  font-size: 14px;
  line-height: 1.5;
  color: #334155;
  font-weight: 500;
}

.bubble-close {
  background: linear-gradient(135deg, 
    rgba(255, 107, 107, 0.1) 0%, 
    rgba(78, 205, 196, 0.08) 100%);
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  font-size: 16px;
  line-height: 1;
  transition: all 0.3s;
  flex-shrink: 0;
}

.bubble-close:hover {
  background: linear-gradient(135deg, 
    rgba(255, 107, 107, 0.2) 0%, 
    rgba(78, 205, 196, 0.15) 100%);
  color: #ff6b6b;
  transform: scale(1.15) rotate(90deg);
  box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.bubble-arrow {
  position: absolute;
  bottom: -9px;
  right: 24px;
  width: 18px;
  height: 18px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(240, 248, 255, 0.9) 100%);
  transform: rotate(45deg);
  border-radius: 0 0 4px 0;
  border-right: 1px solid rgba(255, 255, 255, 0.3);
  border-bottom: 1px solid rgba(255, 255, 255, 0.3);
}

/* Chat Options Menu */
.chat-options {
  position: absolute;
  bottom: 70px;
  right: 0;
  background: linear-gradient(135deg, 
    rgba(255, 240, 245, 0.98) 0%, 
    rgba(240, 248, 255, 0.97) 15%,
    rgba(245, 255, 245, 0.97) 30%,
    rgba(255, 248, 240, 0.97) 45%,
    rgba(248, 240, 255, 0.97) 60%,
    rgba(240, 255, 248, 0.97) 75%,
    rgba(255, 245, 240, 0.98) 100%);
  backdrop-filter: blur(25px);
  border-radius: 24px;
  padding: 20px;
  min-width: 200px;
  box-shadow: 
    0 25px 80px rgba(255, 107, 107, 0.1),
    0 15px 40px rgba(78, 205, 196, 0.08),
    0 8px 20px rgba(69, 183, 209, 0.06),
    0 4px 10px rgba(150, 206, 180, 0.04),
    0 0 0 1px rgba(255, 255, 255, 0.3) inset;
  opacity: 0;
  visibility: hidden;
  transform: translateY(15px) scale(0.9);
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  z-index: 1001;
  border: 1px solid rgba(255, 255, 255, 0.4);
}

#floating-chat-widget.open .chat-options {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}

/* Chat Option Item */
.chat-option {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 18px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
  margin-bottom: 10px;
  background: linear-gradient(135deg, 
    rgba(255, 255, 255, 0.6) 0%,
    rgba(248, 252, 255, 0.5) 50%,
    rgba(255, 248, 252, 0.6) 100%);
  border: 1px solid rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(8px);
  box-shadow: 
    0 4px 15px rgba(0, 0, 0, 0.06),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.chat-option::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, 
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent);
  transition: left 0.6s ease;
}

.chat-option:last-child {
  margin-bottom: 0;
}

.chat-option:hover {
  background: linear-gradient(135deg, 
    rgba(255, 107, 107, 0.12) 0%, 
    rgba(255, 154, 158, 0.1) 20%,
    rgba(78, 205, 196, 0.1) 40%,
    rgba(69, 183, 209, 0.1) 60%,
    rgba(150, 206, 180, 0.1) 80%,
    rgba(254, 202, 87, 0.12) 100%);
  transform: translateX(8px) scale(1.05);
  box-shadow: 
    0 8px 25px rgba(255, 107, 107, 0.15),
    0 4px 15px rgba(78, 205, 196, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.9);
  border-color: rgba(255, 107, 107, 0.3);
  backdrop-filter: blur(12px);
}

.chat-option:hover::before {
  left: 100%;
}

.option-icon {
  width: 44px;
  height: 44px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    0 6px 20px rgba(0, 0, 0, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
  position: relative;
  overflow: hidden;
}

.option-icon::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: conic-gradient(
    transparent, 
    rgba(255, 255, 255, 0.15), 
    transparent
  );
  animation: iconRotate 3s linear infinite;
  opacity: 0;
  transition: opacity 0.3s;
}

@keyframes iconRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.chat-ai .option-icon {
  background: linear-gradient(135deg, 
    #ff6b9d 0%, 
    #ff8a80 20%,
    #c44cff 40%, 
    #8b5cf6 60%, 
    #7c3aed 80%,
    #d946ef 100%);
  color: white;
  box-shadow: 
    0 8px 25px rgba(255, 107, 157, 0.4),
    0 4px 12px rgba(196, 76, 255, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.chat-zalo .option-icon {
  background: linear-gradient(135deg, 
    #00d4ff 0%, 
    #22d3ee 20%,
    #06b6d4 40%, 
    #0891b2 60%, 
    #0e7490 80%,
    #67e8f9 100%);
  color: white;
  box-shadow: 
    0 8px 25px rgba(0, 212, 255, 0.4),
    0 4px 12px rgba(6, 182, 212, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.chat-messenger .option-icon {
  background: linear-gradient(135deg, 
    #667eea 0%, 
    #818cf8 20%,
    #3b82f6 40%, 
    #2563eb 60%, 
    #1d4ed8 80%,
    #8b5cf6 100%);
  color: white;
  box-shadow: 
    0 8px 25px rgba(102, 126, 234, 0.4),
    0 4px 12px rgba(59, 130, 246, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.chat-phone .option-icon {
  background: linear-gradient(135deg, 
    #00f260 0%, 
    #34d399 20%,
    #10b981 40%, 
    #059669 60%, 
    #047857 80%,
    #6ee7b7 100%);
  color: white;
  box-shadow: 
    0 8px 25px rgba(0, 242, 96, 0.4),
    0 4px 12px rgba(16, 185, 129, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.4);
}

.option-label {
  font-weight: 600;
  font-size: 15px;
  color: #334155;
  letter-spacing: 0.01em;
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
}

.chat-option:hover .option-icon {
  transform: scale(1.15) rotate(10deg);
  box-shadow: 
    0 10px 30px rgba(0, 0, 0, 0.2),
    0 4px 15px rgba(255, 255, 255, 0.3) inset,
    0 0 0 2px rgba(255, 255, 255, 0.4);
}

.chat-option:hover .option-icon::before {
  opacity: 1;
}

.chat-option:hover .option-label {
  color: #1e293b;
  transform: translateX(2px);
}

/* Mobile Responsive */
@media (max-width: 768px) {
  #floating-chat-widget {
    bottom: 80px;
    right: 15px;
  }
  
  .main-chat-btn {
    padding: 10px 16px;
    border-radius: 14px;
  }
  
  .chat-text {
    font-size: 13px;
  }
  
          .chat-options {
      right: -20px;
      bottom: 60px;
      min-width: 180px;
      padding: 16px;
      border-radius: 20px;
    }
    
    .chat-option {
      padding: 12px 14px;
      margin-bottom: 8px;
    }
    
    .option-icon {
      width: 36px;
      height: 36px;
      font-size: 16px;
    }
  
  .bubble-text {
    font-size: 13px;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .chat-options {
    background: rgba(31, 41, 55, 0.95);
  }
  
  .option-label {
    color: #e5e7eb;
  }
}
</style>
<script>
// Premium Floating Chat Widget Controller
(function() {
    const widget = document.getElementById('floating-chat-widget');
    const mainBtn = document.getElementById('main-chat-btn');
    const chatBubble = document.getElementById('chat-bubble');
    const bubbleText = document.querySelector('.bubble-text');
    
    // Suggestion messages
    const suggestions = [
        '💊 Bạn cần tư vấn về thuốc?',
        '🤖 Chat với AI bác sĩ miễn phí!',
        '📱 Kết nối Zalo, Messenger dễ dàng!',
        '🩺 Đặt lịch khám bệnh online!',
        '💬 Hỏi đáp sức khỏe 24/7!',
        '📞 Gọi trực tiếp để tư vấn!',
        '❤️ Chăm sóc sức khỏe gia đình!',
        '⚡ Hỗ trợ khẩn cấp, click ngay!'
    ];
    
    let bubbleTimeout;
    let isMenuOpen = false;
    
    // Show suggestion bubble
    function showBubble() {
        if (isMenuOpen) return;
        
        const randomMsg = suggestions[Math.floor(Math.random() * suggestions.length)];
        bubbleText.textContent = randomMsg;
        chatBubble.style.display = 'block';
        
        // Auto hide after 5 seconds
        clearTimeout(bubbleTimeout);
        bubbleTimeout = setTimeout(hideBubble, 5000);
    }
    
    // Hide suggestion bubble
    window.hideBubble = function() {
        chatBubble.style.display = 'none';
        clearTimeout(bubbleTimeout);
    };
    
    // Toggle chat options menu
    function toggleMenu() {
        isMenuOpen = !isMenuOpen;
        widget.classList.toggle('open', isMenuOpen);
        
        // Hide bubble when menu opens
        if (isMenuOpen) {
            hideBubble();
        }
    }
    
    // Chat option handlers
    window.openAIChat = function() {
        window.open('/Chat/health-ai-chat.php', '_blank');
        toggleMenu();
    };
    
    window.openZalo = function() {
        window.open('https://zalo.me/pc', '_blank');
        toggleMenu();
    };
    
    window.openMessenger = function() {
        window.open('https://m.me/', '_blank');
        toggleMenu();
    };
    
    window.makeCall = function() {
        window.location.href = 'tel:+84123456789';
        toggleMenu();
    };
    
    // Event listeners
    mainBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleMenu();
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!widget.contains(e.target) && isMenuOpen) {
            toggleMenu();
        }
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isMenuOpen) {
            toggleMenu();
        }
    });
    
    // Show bubble periodically
    setInterval(showBubble, 20000); // Every 20 seconds
    
    // Show first bubble after page loads
    setTimeout(showBubble, 3000);
    
    // Listen for AI chat open event
    window.addEventListener('open-ai-chatbox', function() {
        openAIChat();
    });
    
})();
</script> 
