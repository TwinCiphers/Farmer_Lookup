document.addEventListener('DOMContentLoaded', () => {
    const localHeading = document.getElementById('local-heading');
    const findLocationBtn = document.getElementById('find-location-btn');
    const cityPlaceholder = document.querySelector('.city-placeholder');
    const initialCity = 'Your City'; // Default city/region

    // Function to update the heading and button state
    function updateLocalDisplay(cityName) {
        cityPlaceholder.textContent = cityName;
        findLocationBtn.textContent = 'Personalized Insights Loaded! 🎉';
        findLocationBtn.style.backgroundColor = '#28a745'; // Success color
        findLocationBtn.disabled = true;

        // In a real application, this is where you'd trigger an AJAX call
        // to filter or load blog posts specific to the 'cityName'.
        console.log(`Content personalized for: ${cityName}`);
    }

    // Advanced: Use Geolocation API to get user's position
    const personalizeByLocation = () => {
        if ("geolocation" in navigator) {
            findLocationBtn.textContent = 'Locating Area...';
            findLocationBtn.style.backgroundColor = '#ffc107'; // Loading color

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    // --- REVERSE GEOCODING SIMULATION ---
                    // In a production environment, you MUST use a Reverse Geocoding API 
                    // (like Google Maps Geocoding API or OpenCage) to convert (lat, lon) 
                    // into a readable city/town name. API keys are required for this step.

                    // Simulating a successful API call:
                    setTimeout(() => {
                        // Example: Simulate receiving the city name from a backend service
                        const simulatedCity = "Austin, TX"; // Replace with API result
                        updateLocalDisplay(simulatedCity);
                    }, 2000); // 2-second delay to simulate network latency

                },
                (error) => {
                    console.error("Geolocation error:", error.code, error.message);
                    
                    // Handle various errors gracefully
                    let errorMessage = 'Could not determine location.';
                    if (error.code === error.PERMISSION_DENIED) {
                        errorMessage = 'Location permission denied.';
                    }

                    findLocationBtn.textContent = `${errorMessage} (Using ${initialCity})`;
                    findLocationBtn.style.backgroundColor = '#dc3545'; // Error color
                    findLocationBtn.disabled = true;
                    cityPlaceholder.textContent = initialCity; // Fallback
                },
                {
                    // Options for a better result
                    enableHighAccuracy: true,
                    timeout: 7000,
                    maximumAge: 0 
                }
            );
        } else {
            // Geolocation not supported
            findLocationBtn.textContent = 'Geolocation Not Supported';
            findLocationBtn.style.backgroundColor = '#6c757d'; 
            findLocationBtn.disabled = true;
        }
    };

    // Event listener for the CTA button
    findLocationBtn.addEventListener('click', personalizeByLocation);

    // Set initial state
    cityPlaceholder.textContent = initialCity;
});