// Farmer Lookup - Main JavaScript File

class FarmerLookupApp {
    constructor() {
        // Compute a base path that always points to the site's root /api/ folder.
        // This allows pages living in subfolders (e.g., /orders/, /messages/) to call APIs
        // without needing to use ../ in every place.
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        // If the app is served from a subfolder named FarmerLookup, include it.
        // Find index of workspace root (assume last part of path that equals project folder name)
        let rootPrefix = '/';
        if (pathParts.length > 0) {
            // Build a prefix up to the project root (assume folder 'FarmerLookup' exists in path)
            const idx = pathParts.indexOf('FarmerLookup');
            if (idx !== -1) {
                rootPrefix = '/' + pathParts.slice(0, idx + 1).join('/') + '/';
            } else {
                // Default to site root
                rootPrefix = '/';
            }
        }
        this.apiBase = rootPrefix + 'api/';
        this.currentUser = null;
        this.cart = this.getCartFromStorage();
        this.init();
    }

    init() {
        this.loadCurrentUser();
        this.initEventListeners();
        this.updateCartUI();
        this.initLocationDetection();
    }

    // User Authentication
    loadCurrentUser() {
        const userStr = localStorage.getItem('farmer_lookup_user');
        if (userStr) {
            try {
                this.currentUser = JSON.parse(userStr);
                this.updateUserUI();
            } catch (e) {
                localStorage.removeItem('farmer_lookup_user');
            }
        }
    }

    updateUserUI() {
        const userActions = document.querySelector('.user-actions');
        if (!userActions) return;

        if (this.currentUser) {
            userActions.innerHTML = `
                <div class="user-menu">
                    <span class="user-greeting">Hello, ${this.currentUser.first_name}</span>
                    <div class="dropdown">
                        <button class="btn btn-outline dropdown-toggle">
                            <span class="icon icon-user"></span>
                            ${this.currentUser.user_type === 'farmer' ? 'Farm Dashboard' : 'My Account'}
                        </button>
                        <div class="dropdown-menu">
                            ${this.currentUser.user_type === 'farmer' 
                                ? '<a href="farmer-dashboard.html">Dashboard</a><a href="manage-products.html">Manage Products</a>' 
                                : '<a href="buyer-dashboard.html">My Orders</a><a href="favorites.html">Favorites</a>'}
                            <a href="profile.html">Profile Settings</a>
                            <hr>
                            <a href="#" onclick="app.logout()">Logout</a>
                        </div>
                    </div>
                </div>
            `;
        } else {
            userActions.innerHTML = `
                <a href="login.html" class="btn btn-outline">
                    <span class="icon icon-user"></span> Login
                </a>
                <a href="register.html" class="btn btn-primary">
                    Join as Farmer/Buyer
                </a>
            `;
        }
    }

    async login(email, password, userType) {
        try {
            const response = await this.apiCall('auth/login.php', 'POST', {
                email, password, user_type: userType
            });

            if (response.success) {
                this.currentUser = response.user;
                localStorage.setItem('farmer_lookup_user', JSON.stringify(this.currentUser));
                this.updateUserUI();
                
                // Redirect based on user type
                if (userType === 'farmer') {
                    window.location.href = 'farmer-dashboard.html';
                } else {
                    window.location.href = 'marketplace.html';
                }
                return { success: true };
            } else {
                return { success: false, message: response.message };
            }
        } catch (error) {
            return { success: false, message: 'Login failed. Please try again.' };
        }
    }

    logout() {
        this.currentUser = null;
        this.cart = [];
        localStorage.removeItem('farmer_lookup_user');
        localStorage.removeItem('farmer_lookup_cart');
        this.updateUserUI();
        this.updateCartUI();
        window.location.href = 'index.html';
    }

    // Cart Management
    getCartFromStorage() {
        const cartStr = localStorage.getItem('farmer_lookup_cart');
        return cartStr ? JSON.parse(cartStr) : [];
    }

    saveCartToStorage() {
        localStorage.setItem('farmer_lookup_cart', JSON.stringify(this.cart));
    }

    addToCart(productId, quantity = 1) {
        const existingItem = this.cart.find(item => item.productId === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.cart.push({
                productId,
                quantity,
                addedAt: new Date().toISOString()
            });
        }
        
        this.saveCartToStorage();
        this.updateCartUI();
        this.showAlert('Product added to cart!', 'success');
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.productId !== productId);
        this.saveCartToStorage();
        this.updateCartUI();
    }

    updateCartQuantity(productId, newQuantity) {
        if (newQuantity <= 0) {
            this.removeFromCart(productId);
            return;
        }

        const item = this.cart.find(item => item.productId === productId);
        if (item) {
            item.quantity = newQuantity;
            this.saveCartToStorage();
            this.updateCartUI();
        }
    }

    updateCartUI() {
        const cartCount = document.querySelector('.cart-count');
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        
        if (cartCount) {
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'inline' : 'none';
        }
    }

    // Product Search and Filtering
    async searchProducts(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await this.apiCall(`products/search.php?${queryParams}`);
            
            if (response.success) {
                this.displayProducts(response.products);
                return response.products;
            }
        } catch (error) {
            console.error('Search failed:', error);
            this.showAlert('Search failed. Please try again.', 'danger');
        }
    }

    displayProducts(products) {
        const productGrid = document.querySelector('.product-grid');
        if (!productGrid) return;

        if (products.length === 0) {
            productGrid.innerHTML = `
                <div class="no-products">
                    <span class="icon icon-search" style="font-size: 3rem; color: var(--sage-light);"></span>
                    <h3>No products found</h3>
                    <p>Try adjusting your search criteria or location.</p>
                </div>
            `;
            return;
        }

        productGrid.innerHTML = products.map(product => this.createProductCard(product)).join('');
    }

    createProductCard(product) {
        const statusClass = this.getStatusClass(product.quantity_available);
        const statusText = this.getStatusText(product.quantity_available);
        
        return `
            <div class="product-card" data-product-id="${product.id}">
                <img src="${product.image_url || 'assets/images/placeholder-product.jpg'}" 
                     alt="${product.name}" class="product-image">
                <div class="product-info">
                    <h4 class="product-title">${product.name}</h4>
                    <p class="product-farmer">
                        <span class="icon icon-farm"></span>
                        ${product.farm_name}
                    </p>
                    <div class="product-price">$${parseFloat(product.price_per_unit).toFixed(2)}/${product.unit_type}</div>
                    <div class="product-meta">
                        <span class="product-status ${statusClass}">${statusText}</span>
                        ${product.growing_method ? `<span class="growing-method">${product.growing_method}</span>` : ''}
                    </div>
                    <div class="product-rating">
                        ${this.createRatingStars(product.average_rating || 0)}
                        <span class="rating-count">(${product.review_count || 0} reviews)</span>
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary btn-sm" onclick="app.addToCart(${product.id})">
                            <span class="icon icon-cart"></span> Add to Cart
                        </button>
                        <button class="btn btn-outline btn-sm" onclick="app.viewProduct(${product.id})">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getStatusClass(quantity) {
        if (quantity === 0) return 'status-sold-out';
        if (quantity < 10) return 'status-limited';
        return 'status-available';
    }

    getStatusText(quantity) {
        if (quantity === 0) return 'Sold Out';
        if (quantity < 10) return `Only ${quantity} left`;
        return 'Available Now';
    }

    createRatingStars(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
        
        let stars = '';
        for (let i = 0; i < fullStars; i++) {
            stars += '<span class="star filled">★</span>';
        }
        if (halfStar) {
            stars += '<span class="star half">☆</span>';
        }
        for (let i = 0; i < emptyStars; i++) {
            stars += '<span class="star">☆</span>';
        }
        
        return `<div class="rating">${stars}</div>`;
    }

    // Location Services
    initLocationDetection() {
        if (navigator.geolocation) {
            const locationBtn = document.querySelector('.detect-location-btn');
            if (locationBtn) {
                locationBtn.addEventListener('click', () => this.detectUserLocation());
            }
        }
    }

    detectUserLocation() {
        if (!navigator.geolocation) {
            this.showAlert('Geolocation is not supported by this browser.', 'warning');
            return;
        }

        const locationBtn = document.querySelector('.detect-location-btn');
        if (locationBtn) {
            locationBtn.innerHTML = '<span class="loading"></span> Detecting...';
            locationBtn.disabled = true;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                this.updateLocationFilters(latitude, longitude);
                
                if (locationBtn) {
                    locationBtn.innerHTML = '<span class="icon icon-location"></span> Location Detected';
                    locationBtn.disabled = false;
                }
            },
            (error) => {
                this.showAlert('Unable to detect your location. Please enter manually.', 'warning');
                if (locationBtn) {
                    locationBtn.innerHTML = '<span class="icon icon-location"></span> Detect Location';
                    locationBtn.disabled = false;
                }
            }
        );
    }

    updateLocationFilters(latitude, longitude) {
        // Update distance-based search filters
        const locationInputs = document.querySelectorAll('input[name="latitude"], input[name="longitude"]');
        locationInputs[0].value = latitude;
        locationInputs[1].value = longitude;
        
        // Trigger product search with new location
        this.searchProducts({
            latitude,
            longitude,
            radius: document.querySelector('select[name="distance"]')?.value || 25
        });
    }

    // Messaging System
    async sendMessage(recipientId, subject, message, orderId = null) {
        try {
            const response = await this.apiCall('messages/send.php', 'POST', {
                recipient_id: recipientId,
                subject,
                message,
                order_id: orderId
            });

            if (response.success) {
                this.showAlert('Message sent successfully!', 'success');
                return true;
            } else {
                this.showAlert(response.message || 'Failed to send message', 'danger');
                return false;
            }
        } catch (error) {
            this.showAlert('Failed to send message. Please try again.', 'danger');
            return false;
        }
    }

    // Utility Functions
    async apiCall(endpoint, method = 'GET', data = null) {
        const url = this.apiBase + endpoint;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    }

    showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.querySelector('.alert-container') || this.createAlertContainer();
        
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type}`;
        alertElement.innerHTML = `
            <div class="alert-content">
                ${message}
                <button class="alert-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
            </div>
        `;
        
        alertContainer.appendChild(alertElement);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, duration);
    }

    createAlertContainer() {
        const container = document.createElement('div');
        container.className = 'alert-container';
        container.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }

    initEventListeners() {
        // Form submissions
        document.addEventListener('submit', async (e) => {
            if (e.target.matches('.login-form')) {
                e.preventDefault();
                await this.handleLogin(e.target);
            } else if (e.target.matches('.register-form')) {
                e.preventDefault();
                await this.handleRegister(e.target);
            } else if (e.target.matches('.search-form')) {
                e.preventDefault();
                await this.handleSearch(e.target);
            }
        });

        // Product interactions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart-btn')) {
                const productId = e.target.closest('.product-card').dataset.productId;
                this.addToCart(parseInt(productId));
            }
        });

        // Filter changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('.filter-control')) {
                this.handleFilterChange();
            }
        });
    }

    async handleLogin(form) {
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');
        const userType = formData.get('user_type');

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.innerHTML = '<span class="loading"></span> Logging in...';
        submitBtn.disabled = true;

        const result = await this.login(email, password, userType);
        
        if (!result.success) {
            this.showAlert(result.message, 'danger');
        }

        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }

    async handleRegister(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        if (data.password !== data.confirm_password) {
            this.showAlert('Passwords do not match!', 'danger');
            return;
        }

        try {
            const response = await this.apiCall('auth/register.php', 'POST', data);
            
            if (response.success) {
                this.showAlert('Registration successful! Please login.', 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                this.showAlert(response.message || 'Registration failed', 'danger');
            }
        } catch (error) {
            this.showAlert('Registration failed. Please try again.', 'danger');
        }
    }

    async handleSearch(form) {
        const formData = new FormData(form);
        const filters = Object.fromEntries(formData.entries());
        await this.searchProducts(filters);
    }

    handleFilterChange() {
        const filterForm = document.querySelector('.filter-sidebar form');
        if (filterForm) {
            this.handleSearch(filterForm);
        }
    }
}

// Initialize the app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new FarmerLookupApp();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FarmerLookupApp;
}