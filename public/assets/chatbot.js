(function () {
  'use strict';

  const cfg = window.WooChatbotConfig || {};
  const restUrl  = cfg.restUrl  || '';
  const nonce    = cfg.nonce    || '';
  const botName  = cfg.botName  || 'Woo Chatbot Assistant';
  const greeting = cfg.greeting || 'Hi! How can I help?';
  const color    = cfg.color    || '#1a5c38';

  // --- Session ID ---
  function getSessionId() {
    let id = sessionStorage.getItem('pc_session_id');
    if (!id) {
      id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
      });
      sessionStorage.setItem('pc_session_id', id);
    }
    return id;
  }

  // --- Inject styles ---
  function injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
      #pc-bubble {
        position: fixed; bottom: 24px; right: 24px; z-index: 99999;
        width: 56px; height: 56px; border-radius: 50%;
        background: ${color}; color: #fff; border: none;
        cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,.25);
        display: flex; align-items: center; justify-content: center;
        transition: transform .2s;
      }
      #pc-bubble:hover { transform: scale(1.08); }
      #pc-bubble svg { width: 26px; height: 26px; fill: #fff; }
      #pc-panel {
        position: fixed; bottom: 92px; right: 24px; z-index: 99998;
        width: 340px; max-height: 520px;
        background: #fff; border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,.18);
        display: flex; flex-direction: column;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 14px; overflow: hidden;
        transition: opacity .2s, transform .2s;
      }
      #pc-panel.pc-hidden { opacity: 0; pointer-events: none; transform: translateY(8px); }
      #pc-header {
        background: ${color}; color: #fff;
        padding: 14px 16px; font-weight: 600; font-size: 15px;
        display: flex; align-items: center; gap: 10px;
      }
      #pc-header span { flex: 1; }
      #pc-close {
        background: none; border: none; color: #fff;
        cursor: pointer; font-size: 20px; line-height: 1; padding: 0;
      }
      #pc-messages {
        flex: 1; overflow-y: auto; padding: 14px;
        display: flex; flex-direction: column; gap: 10px;
        min-height: 200px; max-height: 340px;
      }
      .pc-msg {
        max-width: 82%; padding: 9px 12px; border-radius: 10px;
        line-height: 1.5; word-break: break-word;
      }
      .pc-msg.pc-bot {
        background: #f1f3f4; color: #222;
        align-self: flex-start; border-bottom-left-radius: 3px;
      }
      .pc-msg.pc-user {
        background: ${color}; color: #fff;
        align-self: flex-end; border-bottom-right-radius: 3px;
      }
      .pc-msg.pc-error { background: #fff0f0; color: #c00; }
      .pc-typing { display: flex; gap: 4px; padding: 10px 12px; }
      .pc-typing span {
        width: 7px; height: 7px; background: #aaa;
        border-radius: 50%; animation: pc-bounce .9s infinite;
      }
      .pc-typing span:nth-child(2) { animation-delay: .15s; }
      .pc-typing span:nth-child(3) { animation-delay: .3s; }
      @keyframes pc-bounce {
        0%,60%,100% { transform: translateY(0); }
        30% { transform: translateY(-6px); }
      }
      #pc-footer {
        padding: 10px 12px; border-top: 1px solid #eee;
        display: flex; gap: 8px;
      }
      #pc-input {
        flex: 1; border: 1px solid #ddd; border-radius: 8px;
        padding: 8px 10px; font-size: 14px; outline: none;
        resize: none; line-height: 1.4; max-height: 80px;
        font-family: inherit;
      }
      #pc-input:focus { border-color: ${color}; }
      #pc-send {
        background: ${color}; color: #fff; border: none;
        border-radius: 8px; padding: 8px 14px;
        cursor: pointer; font-size: 14px; font-weight: 600;
        transition: opacity .2s;
      }
      #pc-send:disabled { opacity: .5; cursor: not-allowed; }
      #pc-online-dot {
        width: 9px; height: 9px; background: #6ee7a0;
        border-radius: 50%; display: inline-block;
      }
      @media (max-width: 400px) {
        #pc-panel { width: calc(100vw - 24px); right: 12px; bottom: 80px; }
        #pc-bubble { right: 12px; bottom: 12px; }
      }
    `;
    document.head.appendChild(style);
  }

  // --- Build DOM ---
  function buildUI() {
    // Bubble
    const bubble = document.createElement('button');
    bubble.id = 'pc-bubble';
    bubble.setAttribute('aria-label', 'Open chat');
    bubble.innerHTML = `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"/>
    </svg>`;

    // Panel
    const panel = document.createElement('div');
    panel.id = 'pc-panel';
    panel.className = 'pc-hidden';
    panel.innerHTML = `
      <div id="pc-header">
        <span id="pc-online-dot"></span>
        <span>${escHtml(botName)}</span>
        <button id="pc-close" aria-label="Close chat">&times;</button>
      </div>
      <div id="pc-messages" role="log" aria-live="polite"></div>
      <div id="pc-footer">
        <textarea id="pc-input" rows="1" placeholder="Ask a question..." aria-label="Message"></textarea>
        <button id="pc-send">Send</button>
      </div>
    `;

    document.body.appendChild(bubble);
    document.body.appendChild(panel);

    return { bubble, panel };
  }

  // --- Markdown lite renderer ---
  function renderMarkdown(text) {
    return text
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.+?)\*/g, '<em>$1</em>')
      .replace(/\n/g, '<br>');
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // --- Append message ---
  function appendMessage(container, role, text) {
    const el = document.createElement('div');
    el.className = 'pc-msg pc-' + role;
    el.innerHTML = role === 'bot' ? renderMarkdown(text) : escHtml(text);
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
    return el;
  }

  function appendTyping(container) {
    const el = document.createElement('div');
    el.className = 'pc-msg pc-bot pc-typing';
    el.innerHTML = '<span></span><span></span><span></span>';
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
    return el;
  }

  // --- Send message ---
  async function sendMessage(text, messagesEl, inputEl, sendBtn) {
    appendMessage(messagesEl, 'user', text);
    inputEl.value = '';
    inputEl.style.height = 'auto';
    inputEl.disabled = true;
    sendBtn.disabled = true;

    const typing = appendTyping(messagesEl);

    try {
      const res = await fetch(restUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': nonce,
        },
        body: JSON.stringify({ session_id: getSessionId(), message: text }),
      });

      const data = await res.json();
      typing.remove();

      if (!res.ok || data.error) {
        appendMessage(messagesEl, 'bot pc-error', data.error || 'Something went wrong. Please try again.');
        inputEl.disabled = false;
        sendBtn.disabled = false;
        return;
      }

      appendMessage(messagesEl, 'bot', data.message);

      if (data.handoff) {
        inputEl.disabled = true;
        sendBtn.disabled = true;
        inputEl.placeholder = 'Chat ended — our team will be in touch.';
      } else {
        inputEl.disabled = false;
        sendBtn.disabled = false;
        inputEl.focus();
      }

    } catch (err) {
      typing.remove();
      appendMessage(messagesEl, 'bot pc-error', 'Connection error. Please try again.');
      inputEl.disabled = false;
      sendBtn.disabled = false;
    }
  }

  // --- Init ---
  function init() {
    injectStyles();
    const { bubble, panel } = buildUI();

    const messagesEl = panel.querySelector('#pc-messages');
    const inputEl    = panel.querySelector('#pc-input');
    const sendBtn    = panel.querySelector('#pc-send');
    const closeBtn   = panel.querySelector('#pc-close');

    let opened = false;

    bubble.addEventListener('click', function () {
      panel.classList.toggle('pc-hidden');
      if (!opened) {
        opened = true;
        appendMessage(messagesEl, 'bot', greeting);
      }
      if (!panel.classList.contains('pc-hidden')) {
        inputEl.focus();
      }
    });

    closeBtn.addEventListener('click', function () {
      panel.classList.add('pc-hidden');
    });

    sendBtn.addEventListener('click', function () {
      const text = inputEl.value.trim();
      if (text) sendMessage(text, messagesEl, inputEl, sendBtn);
    });

    inputEl.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const text = inputEl.value.trim();
        if (text) sendMessage(text, messagesEl, inputEl, sendBtn);
      }
    });

    // Auto-grow textarea
    inputEl.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
