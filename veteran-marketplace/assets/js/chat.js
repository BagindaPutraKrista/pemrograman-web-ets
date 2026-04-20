// chat.js - Logika chat realtime untuk Veteran Marketplace
// Variabel INITIAL_ROOM_ID dan currentUserId diset dari chat.php

// Variabel global untuk menyimpan state chat
var roomId = (typeof INITIAL_ROOM_ID !== 'undefined') ? INITIAL_ROOM_ID : null;
var lastId = 0;
var pollingTimer = null;

// Ambil ID user yang sedang login dari hidden input
var currentUserIdEl = document.getElementById('currentUserId');
var currentUserId = currentUserIdEl ? parseInt(currentUserIdEl.value) : 0;

// Referensi ke elemen HTML
var messagesArea = document.getElementById('messagesArea');
var messageInput = document.getElementById('messageInput');
var sendBtn = document.getElementById('sendBtn');

// Scroll ke bawah area pesan
function scrollToBottom() {
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }
}

// Tambahkan bubble pesan ke area chat
function appendMessage(msg) {
    if (!messagesArea) return;

    var isMine = msg.is_mine;

    // Buat wrapper luar
    var wrapper = document.createElement('div');
    wrapper.className = 'chat-bubble-wrapper ' + (isMine ? 'sent' : 'received');

    // Buat div dalam yang membatasi lebar bubble
    var inner = document.createElement('div');
    inner.style.cssText = 'max-width:70%;display:flex;flex-direction:column;align-items:' + (isMine ? 'flex-end' : 'flex-start') + ';';

    // Buat bubble pesan
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble ' + (isMine ? 'sent' : 'received');
    bubble.textContent = msg.message;

    // Buat timestamp
    var timestamp = document.createElement('div');
    timestamp.className = 'chat-timestamp' + (isMine ? ' sent' : '');
    timestamp.textContent = msg.time;

    // Susun elemen
    inner.appendChild(bubble);
    inner.appendChild(timestamp);
    wrapper.appendChild(inner);
    messagesArea.appendChild(wrapper);

    scrollToBottom();
}

// Tandai semua pesan di room ini sebagai sudah dibaca
function markAsRead() {
    if (!roomId) return;
    fetch('../api/chat_mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'room_id=' + roomId
    });
}

// Hentikan polling
function stopPolling() {
    if (pollingTimer !== null) {
        clearInterval(pollingTimer);
        pollingTimer = null;
    }
}

// Mulai polling pesan baru setiap 3 detik
function startPolling() {
    stopPolling();
    pollingTimer = setInterval(function() {
        if (!roomId) return;

        fetch('../api/chat_poll.php?room_id=' + roomId + '&last_id=' + lastId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(appendMessage);
                    lastId = data.last_id;
                }
            });
    }, 3000);
}

// Muat semua pesan di room tertentu
function loadRoom(newRoomId) {
    roomId = newRoomId;
    lastId = 0;

    if (messagesArea) {
        messagesArea.innerHTML = '';
    }

    fetch('../api/chat_poll.php?room_id=' + roomId + '&last_id=0')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(appendMessage);
                lastId = data.last_id;
            }
            markAsRead();
            startPolling();
        });
}

// Kirim pesan
function sendMessage() {
    if (!roomId || !messageInput) return;

    var text = messageInput.value.trim();
    if (text === '') return;

    // Nonaktifkan input sementara
    messageInput.disabled = true;
    if (sendBtn) sendBtn.disabled = true;

    fetch('../api/chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'room_id=' + roomId + '&message=' + encodeURIComponent(text)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success && data.message) {
            messageInput.value = '';
            appendMessage(data.message);
            lastId = data.message.id;
        }
    })
    .finally(function() {
        messageInput.disabled = false;
        if (sendBtn) sendBtn.disabled = false;
        messageInput.focus();
    });
}

// Event: klik tombol kirim
if (sendBtn) {
    sendBtn.addEventListener('click', sendMessage);
}

// Event: tekan Enter untuk kirim (Shift+Enter = baris baru)
if (messageInput) {
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

// Inisialisasi: muat room jika sudah ada room_id
if (roomId) {
    loadRoom(roomId);
}
