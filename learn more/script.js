document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Define the detailed content for each pillar
    const pillarDetails = {
        local: {
            title: "Hyper-Local Sourcing: The 50-Mile Promise",
            text: "Our commitment to hyper-local sourcing means fresher food, less travel time, and a minimal environmental impact. This practice ensures that 100% of the revenue from your fresh produce purchases stays within our immediate community, directly supporting your neighbors who farm the land."
        },
        sustainability: {
            title: "Sustainable Practices: Farm-to-Fork Responsibility",
            text: "We champion farmers who prioritize the long-term health of the soil. This includes promoting crop rotation, natural pest control, and reducing plastic usage. We strive to be a zero-waste market, encouraging reusable containers and composting programs."
        },
        community: {
            title: "Community Connection: Building a Stronger Table",
            text: "We believe food is a universal connector. Join our free gardening workshops, seasonal recipe demonstrations, and our 'Share the Harvest' program, which donates surplus food to local shelters, ensuring everyone has access to nutritious, local ingredients."
        }
    };

    // 2. Get DOM elements for the modal
    const modal = document.getElementById('infoModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalText = document.getElementById('modalText');
    const closeBtn = document.querySelector('.close-btn');
    const learnMoreBtns = document.querySelectorAll('.learn-more-btn');

    // 3. Attach click handler to all "Read More" buttons
    learnMoreBtns.forEach(button => {
        button.addEventListener('click', () => {
            const pillarKey = button.getAttribute('data-info');
            const details = pillarDetails[pillarKey];

            // Update modal content
            modalTitle.textContent = details.title;
            modalText.textContent = details.text;

            // Display the modal
            modal.style.display = 'block';
        });
    });

    // 4. Close the modal when the 'X' is clicked
    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // 5. Close the modal when the user clicks anywhere outside of it
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

});