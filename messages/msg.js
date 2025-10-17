const messageForm = document.getElementById('message-form');
const messagesDiv = document.getElementById('messages');
const transactionsDiv = document.getElementById('transactions');

messageForm.addEventListener('submit', async function(e) {
  e.preventDefault();
  const userInput = document.getElementById('user-message');
  const messageText = userInput.value.trim();
  
  if (messageText === "") return;

  // Add user's message locally for immediate feedback
  addMessage('user', messageText);

  // Determine sender and recipient IDs
  let senderId = 0;
  let recipientId = 0;
  if (window.app && window.app.currentUser && window.app.currentUser.id) {
    senderId = window.app.currentUser.id;
  } else {
    // If not logged in, ask for a sender id to use for testing
    const s = prompt('Enter your user id for this message (numeric) or leave blank to use 0:');
    senderId = s ? parseInt(s) || 0 : 0;
  }

  // For demo/testing, ask for recipient id if not set on the page
  const r = prompt('Enter recipient user id (farmer) to send message to (numeric). For demo use 1:', '1');
  recipientId = r ? parseInt(r) || 0 : 0;

  // Send message to server
  try {
    let json;
    if (window.app && typeof window.app.apiCall === 'function') {
      json = await window.app.apiCall('messages/send.php', 'POST', { sender_id: senderId, recipient_id: recipientId, content: messageText });
    } else {
      const res = await fetch('../api/messages/send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sender_id: senderId, recipient_id: recipientId, content: messageText })
      });
      json = await res.json();
    }

    if (json && json.success) {
      addMessage('system', 'Message sent.');
      trackTransaction(messageText);
    } else {
      addMessage('system', 'Failed to send message to server.');
      console.error('Message API error', json);
    }
  } catch (err) {
    addMessage('system', 'Network error while sending message.');
    console.error('Message send failed', err);
  }

  userInput.value = '';
});

function addMessage(sender, text) {
  const msg = document.createElement('div');
  msg.className = `message ${sender}`;
  msg.textContent = text;
  messagesDiv.appendChild(msg);
  messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function trackTransaction(orderDetails) {
  const track = document.createElement('div');
  track.className = 'track-item';
  track.innerHTML = `<strong>Order Question/Request:</strong> ${orderDetails}`;
  transactionsDiv.appendChild(track);
}
