// ===== Filter Products on Products Page =====
document.addEventListener('DOMContentLoaded', function() {
    // Product filtering functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productItems = document.querySelectorAll('.product-item');
    const searchInput = document.getElementById('productSearch');

    // Combined Filter Function (Category + Search)
    function filterProducts() {
        const activeBtn = document.querySelector('.filter-btn.active');
        const category = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        let hasVisibleItems = false;

        productItems.forEach(item => {
            const itemCategory = item.getAttribute('data-category');
            // Search in title, description, and specs
            const itemText = item.textContent.toLowerCase();
            
            const matchesCategory = (category === 'all' || itemCategory === category);
            const matchesSearch = (searchTerm === '' || itemText.includes(searchTerm));

            if (matchesCategory && matchesSearch) {
                item.style.display = 'block';
                hasVisibleItems = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Handle "No results" message
        const productsGrid = document.getElementById('productsGrid');
        if (productsGrid) {
            let noResultsMsg = document.getElementById('no-results-msg');
            
            if (!hasVisibleItems) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'no-results-msg';
                    noResultsMsg.style.gridColumn = '1 / -1';
                    noResultsMsg.style.textAlign = 'center';
                    noResultsMsg.style.padding = '40px';
                    noResultsMsg.style.color = '#6b7280';
                    noResultsMsg.innerHTML = '<h3>No products found</h3><p>Try adjusting your search or category filter.</p>';
                    productsGrid.appendChild(noResultsMsg);
                }
                noResultsMsg.style.display = 'block';
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }
    }

    // Search Event Listener
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Auto-switch to 'All' category when searching to avoid confusion
            if (this.value.trim().length > 0) {
                const allBtn = document.querySelector('.filter-btn[data-filter="all"]');
                if (allBtn && !allBtn.classList.contains('active')) {
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    allBtn.classList.add('active');
                }
            }
            filterProducts();
        });
    }

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            filterProducts();
        });
    });

    // Handle "Other" subject in contact form
    const subjectSelect = document.getElementById('subject');
    const otherSubjectGroup = document.getElementById('otherSubjectGroup');
    const otherSubjectInput = document.getElementById('otherSubject');

    if (subjectSelect && otherSubjectGroup && otherSubjectInput) {
        subjectSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                otherSubjectGroup.style.display = 'block';
                otherSubjectInput.required = true;
            } else {
                otherSubjectGroup.style.display = 'none';
                otherSubjectInput.required = false;
                otherSubjectInput.value = ''; // Clear the value if user changes their mind
            }
        });
    }

    // Handle contact form submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const messageDiv = document.getElementById('formMessage');
            const submitBtn = this.querySelector('button[type="submit"]');

            // 1. Basic Client-side Validation
            const formData = new FormData(this);
            const name = formData.get('name');
            const email = formData.get('email');
            const phone = formData.get('phone');
            const company = formData.get('company');
            const message = formData.get('message');

            if (!name || !email || !phone || !company || !message) {
                showFormMessage('Please fill in all required fields.', 'error');
                return;
            }

            // 2. UI Feedback (Loading)
            if (messageDiv) {
                messageDiv.textContent = 'Sending message...';
                messageDiv.className = 'form-message';
                messageDiv.style.display = 'block';
            }
            if (submitBtn) submitBtn.disabled = true;

            // 3. Send Request
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showFormMessage(data.message, 'success');
                    contactForm.reset();
                } else {
                    showFormMessage('Error: ' + (data.message || 'Unknown error occurred'), 'error');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                showFormMessage('Connection error. Please try again.', 'error');
            })
            .finally(() => {
                if (submitBtn) submitBtn.disabled = false;
            });
        });
    }

    // Handle "Add to Inquiry" button on products — redirect to contact page with product info
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productName = this.parentElement.querySelector('h3').textContent;
            const params = new URLSearchParams();
            params.set('product', productName);
            params.set('subject', 'product-inquiry');
            params.set('message', `Hello, I am interested in ${productName}. Please send a quote and availability.`);
            // Navigate to contact page with query params and anchor to form
            window.location.href = `contact.html?${params.toString()}#contactForm`;
        });
    });

    // Handle "Request Service" button on services — redirect to contact page with service info
    const requestServiceButtons = document.querySelectorAll('.request-service-btn');
    requestServiceButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const serviceName = this.parentElement.querySelector('h3').textContent;
            const params = new URLSearchParams();
            params.set('product', serviceName + ' Service');
            params.set('subject', 'service-request');
            params.set('message', `Hello, I am interested in your ${serviceName} service. Please provide more details and availability.`);
            window.location.href = `contact.html?${params.toString()}#contactForm`;
        });
    });

    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Newsletter checkbox feedback
    const newsletterCheckbox = document.getElementById('newsletter');
    if (newsletterCheckbox) {
        newsletterCheckbox.addEventListener('change', function() {
            // Newsletter subscription logic
        });
    }

    // Mobile Menu Toggle
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileBtn && navMenu) {
        mobileBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            document.body.classList.toggle('mobile-menu-open');
            this.textContent = navMenu.classList.contains('active') ? '✕' : '☰';
        });
    }
});

// Show form message
function showFormMessage(message, type) {
    const messageElement = document.getElementById('formMessage');
    if (messageElement) {
        messageElement.textContent = message;
        messageElement.className = `form-message ${type}`;

        // Auto-hide error messages after 5 seconds
        if (type === 'error') {
            setTimeout(() => {
                messageElement.className = '';
            }, 5000);
        }
    }
}

// Add click animation to buttons
document.querySelectorAll('.btn, .add-to-cart, .filter-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        // Create ripple effect
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');

        // Only add ripple effect to buttons that support it
        if (this.style.position === '' && this.tagName !== 'A') {
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        }
    });
});

// Scroll to top button (optional enhancement)
window.addEventListener('scroll', function() {
    const scrollButton = document.getElementById('scrollTopBtn');
    if (scrollButton) {
        if (window.pageYOffset > 300) {
            scrollButton.style.display = 'block';
        } else {
            scrollButton.style.display = 'none';
        }
    }
});

// Scroll top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Active navigation link based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = location.pathname;
    const menuItems = document.querySelectorAll('.nav-menu a');

    menuItems.forEach(item => {
        if (item.getAttribute('href') === currentLocation || 
            (currentLocation.includes('/') && item.getAttribute('href') === 'index.html' && currentLocation === '/')) {
            item.classList.add('active');
        }
    });
});

// Product grid animation on load
window.addEventListener('load', function() {
    const productCards = document.querySelectorAll('.product-card, .product-item, .feature-card, .service-card');
    productCards.forEach((card, index) => {
        // Stagger animations in small batches of 4 to prevent massive delays for elements at the bottom
        const delay = (index % 4) * 0.1;
        card.style.animation = `fadeInUp 0.6s ease ${delay}s both`;
    });
});

// Add CSS animations dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: rippleEffect 0.6s ease-out;
        pointer-events: none;
    }

    @keyframes rippleEffect {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Prefill contact form from URL params when on contact page
function prefillContactFromQuery() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) return;

    const params = new URLSearchParams(window.location.search);
    const product = params.get('product');
    const subjectParam = params.get('subject');
    const messageParam = params.get('message');

    if (product) {
        const subjectSelect = document.getElementById('subject');
        if (subjectSelect && subjectParam) {
            // Try to set the subject if it matches an option
            const option = Array.from(subjectSelect.options).find(o => o.value === subjectParam);
            if (option) subjectSelect.value = subjectParam;
        }

        const messageField = document.getElementById('message');
        if (messageField) {
            // If message param provided use it, otherwise add a default message mentioning product
            messageField.value = messageParam || `Hello, I am interested in ${product}. Please send a quote and availability.`;
        }

        // Scroll to and focus the submit button so user can quickly send the message
        const sendBtn = document.getElementById('sendMessage') || contactForm.querySelector('button[type="submit"]');
        if (sendBtn) {
            // Small delay to ensure layout completed
                setTimeout(() => {
                    // Account for sticky navbar height so the button sits visible below it
                    const navbar = document.querySelector('.navbar');
                    const navHeight = navbar ? navbar.offsetHeight : 0;
                    const rect = sendBtn.getBoundingClientRect();
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const btnHeight = rect.height || sendBtn.offsetHeight;
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                    // Center button vertically in viewport while leaving navbar space above
                    const btnCenterY = rect.top + scrollTop + (btnHeight / 2);
                    const targetY = Math.max(0, btnCenterY - (viewportHeight / 2) - navHeight + 10);
                    window.scrollTo({ top: targetY, behavior: 'smooth' });
                    // Focus after a short delay so browser doesn't jump
                    setTimeout(() => {
                        sendBtn.focus({ preventScroll: true });
                        // Add highlight so it 'stands' out
                        sendBtn.classList.add('focus-highlight');
                        setTimeout(() => sendBtn.classList.remove('focus-highlight'), 3000);
                    }, 500);
                }, 200);
        }
    }
}

// Run prefill on DOMContentLoaded so fields exist
document.addEventListener('DOMContentLoaded', prefillContactFromQuery);

// Intersection Observer for lazy animation (optional enhancement)
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe all cards for lazy animation
document.querySelectorAll('.feature-card, .product-card, .product-item, .contact-card, .service-card').forEach(card => {
    observer.observe(card);
});

// Counter Animation for Stats Section
document.addEventListener('DOMContentLoaded', function() {
    const statsSections = document.querySelectorAll('.stats');
    
    statsSections.forEach(statsSection => {
        const statNumbers = statsSection.querySelectorAll('.stat h3');
        let animated = false;
        
        // Store target values and reset to 0 immediately
        const statsData = Array.from(statNumbers).map(stat => {
            const text = stat.innerText;
            const target = parseInt(text.replace(/[^0-9]/g, ''), 10);
            const suffix = text.replace(/[0-9]/g, '').trim();
            
            // Initialize to 0 immediately so it's 0 before scrolling into view
            stat.innerText = '0' + suffix;
            
            return { element: stat, target, suffix };
        });

        const animateObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !animated) {
                    animated = true;
                    
                    statsData.forEach(data => {
                        const duration = 900; // Animation duration in ms (lower is faster)
                        const step = data.target / (duration / 16); // 60fps frame adjustment
                        let current = 0;
                        
                        const updateCounter = () => {
                            current += step;
                            if (current < data.target) {
                                data.element.innerText = Math.ceil(current) + data.suffix;
                                requestAnimationFrame(updateCounter);
                            } else {
                                data.element.innerText = data.target + data.suffix;
                            }
                        };
                        updateCounter();
                    });
                    
                    animateObserver.unobserve(statsSection);
                }
            });
        }, { threshold: 0.3 }); // Trigger when 30% of the section is visible

        animateObserver.observe(statsSection);
    });
});

// ===== Basic Content Protection =====
// Disable right-click context menu
document.addEventListener('contextmenu', event => event.preventDefault());

// Disable common developer tools keyboard shortcuts (F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U)
document.addEventListener('keydown', function(e) {
    if (e.key === 'F12' || 
       (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'i' || e.key === 'j' || e.key === 'C' || e.key === 'c')) || 
       (e.ctrlKey && (e.key === 'U' || e.key === 'u' || e.key === 'S' || e.key === 's'))) {
        e.preventDefault();
    }
});
