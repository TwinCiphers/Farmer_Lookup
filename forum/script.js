document.addEventListener('DOMContentLoaded', () => {
    const threadListElement = document.getElementById('threadList');
    const newThreadForm = document.getElementById('newThreadForm');
    const formStatus = document.getElementById('formStatus');

    // 1. Initial Sample Data
    let threads = [
        {
            title: "Upcoming Vendor List for Saturday Market?",
            author: "Local Shopper",
            date: "October 15, 2025",
            replies: 5
        },
        {
            title: "Recipe Share: The best way to preserve tomatoes.",
            author: "Vendor: Mama Mia Jams",
            date: "October 14, 2025",
            replies: 12
        },
        {
            title: "Question: Does anyone sell organic honey at the market?",
            author: "Beekeeper Fan",
            date: "October 12, 2025",
            replies: 8
        }
    ];

    // 2. Function to render the thread list
    function renderThreads() {
        threadListElement.innerHTML = ''; // Clear existing threads
        
        // Sort newest threads first
        threads.sort((a, b) => new Date(b.date) - new Date(a.date));

        threads.forEach(thread => {
            const threadItem = document.createElement('div');
            threadItem.className = 'thread-item';
            
            threadItem.innerHTML = `
                <div class="thread-title">${thread.title}</div>
                <div class="thread-meta">
                    <span>Posted by: ${thread.author}</span>
                    <span>Last Activity: ${thread.date}</span>
                    <span>Replies: ${thread.replies}</span>
                </div>
            `;
            threadListElement.appendChild(threadItem);
        });
    }

    // 3. Handle Form Submission
    newThreadForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const title = document.getElementById('threadTitle').value.trim();
        const author = document.getElementById('threadAuthor').value.trim();
        const content = document.getElementById('threadContent').value.trim();
        
        // Simple form validation
        if (!title || !author || !content) {
            formStatus.textContent = '❌ Please fill out all fields.';
            formStatus.className = 'status-message error';
            formStatus.style.display = 'block';
            return;
        }

        // Create the new thread object
        const newThread = {
            title: title,
            author: author,
            date: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
            replies: 0 // New thread starts with 0 replies
        };

        // 4. Add to the data array and re-render
        threads.push(newThread);
        renderThreads();
        
        // 5. Provide feedback and reset the form
        formStatus.textContent = '✅ New thread posted successfully!';
        formStatus.className = 'status-message success';
        formStatus.style.display = 'block';
        newThreadForm.reset();

        // Hide status message after 3 seconds
        setTimeout(() => {
            formStatus.style.display = 'none';
        }, 3000);
    });

    // Initial render when the page loads
    renderThreads();
});