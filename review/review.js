document.getElementById('review-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const name = document.getElementById('name').value.trim();
  const rating = document.getElementById('rating').value;
  const comment = document.getElementById('comment').value.trim();

  if (!name || !rating || !comment) {
    alert('Please fill all fields.');
    return;
  }

  const reviewsDisplay = document.getElementById('reviews-display');
  
  // Create review card
  const reviewCard = document.createElement('div');
  reviewCard.classList.add('review-card');
  
  // Create review header with name and star rating
  const reviewHeader = document.createElement('div');
  reviewHeader.classList.add('review-header');
  
  const reviewerName = document.createElement('span');
  reviewerName.classList.add('reviewer-name');
  reviewerName.textContent = name;

  const stars = document.createElement('span');
  stars.classList.add('stars');
  stars.innerHTML = '★'.repeat(rating) + '☆'.repeat(5 - rating);

  reviewHeader.appendChild(reviewerName);
  reviewHeader.appendChild(stars);
  
  // Create comment
  const reviewComment = document.createElement('p');
  reviewComment.classList.add('review-comment');
  reviewComment.textContent = comment;

  reviewCard.appendChild(reviewHeader);
  reviewCard.appendChild(reviewComment);
  
  // Add new review at top
  reviewsDisplay.insertBefore(reviewCard, reviewsDisplay.children[1]);

  // Clear form
  // Send review to server
  const payload = {
    reviewer_id: 0,
    reviewed_user_id: 0,
    product_id: 0,
    rating: parseInt(rating),
    comment: comment
  };
  if (window.app && window.app.currentUser && window.app.currentUser.id) payload.reviewer_id = window.app.currentUser.id;
  // Ask for product and reviewed user in demo mode
  const pid = prompt('Enter product id for this review (numeric) or leave blank for 0:', '0');
  payload.product_id = pid ? parseInt(pid) || 0 : 0;
  const rid = prompt('Enter reviewed user id (farmer) or leave blank for 0:', '0');
  payload.reviewed_user_id = rid ? parseInt(rid) || 0 : 0;

  // Prefer the app.apiCall utility for consistent API base paths
  (async function() {
    try {
      let json;
      if (window.app && typeof window.app.apiCall === 'function') {
        json = await window.app.apiCall('reviews.php', 'POST', payload);
      } else {
        const res = await fetch('../api/reviews.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        json = await res.json();
      }

      if (json && json.success) {
        reviewsDisplay.insertBefore(reviewCard, reviewsDisplay.children[1]);
        reviewCard.dataset.remoteId = json.id || '';
        alert('Review submitted');
        document.getElementById('review-form').reset();
      } else {
        alert('Failed to submit review');
      }
    } catch (err) {
      console.error('Review API error', err);
      alert('Network error submitting review');
    }
  })();
});
