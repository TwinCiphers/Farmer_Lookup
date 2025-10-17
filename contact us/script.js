document.getElementById('contactForm').addEventListener('submit', function(event) {
    // 1. Prevent the browser from submitting the form normally
    event.preventDefault();

    const form = event.target;
    const statusMessage = document.getElementById('statusMessage');
    const submitBtn = document.getElementById('submitBtn');

    // Basic Email Validation
    const emailInput = document.getElementById('email');
    const emailValue = emailInput.value.trim();
    // Regex for basic email format check
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(emailValue)) {
        statusMessage.textContent = '❌ Please enter a valid email address.';
        statusMessage.className = 'status-message error';
        statusMessage.style.display = 'block';
        emailInput.focus();
        return; // Stop the process
    }

    // 2. Show loading state
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;
    submitBtn.style.backgroundColor = '#6c757d'; // Temporarily grey out

    // 3. Simulate a successful network request delay (2 seconds)
    setTimeout(() => {
        // --- REAL-WORLD: You would use the 'fetch()' API here to send data to your server ---
        
        // 4. On successful simulation:
        statusMessage.textContent = '✅ Success! Your message has been sent to the market team.';
        statusMessage.className = 'status-message success';
        statusMessage.style.display = 'block';

        // 5. Reset the form and button
        form.reset();
        submitBtn.textContent = 'Send Message';
        submitBtn.disabled = false;
        submitBtn.style.backgroundColor = 'var(--primary-color)';

    }, 2000); // Wait 2 seconds
});

// Optional: Clear the status message when the user starts typing again
document.getElementById('contactForm').addEventListener('input', function() {
    document.getElementById('statusMessage').style.display = 'none';
});