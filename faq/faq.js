document.addEventListener('DOMContentLoaded', function() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            
            // Toggle active class
            faqItem.classList.toggle('active');
            
            // Optional: Close other items when one is opened
            // Uncomment the code below if you want only one item open at a time
            /*
            const allItems = document.querySelectorAll('.faq-item');
            allItems.forEach(item => {
                if (item !== faqItem) {
                    item.classList.remove('active');
                }
            });
            */
        });
    });
});
