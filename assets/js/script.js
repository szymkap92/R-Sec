document.addEventListener('DOMContentLoaded', function() {
    // Cookie Consent Banner
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptAllBtn = document.getElementById('acceptAll');
    const rejectAllBtn = document.getElementById('rejectAll');
    const acceptNecessaryBtn = document.getElementById('acceptNecessary');

    // Check if user has already made a choice
    const cookieChoice = localStorage.getItem('cookieChoice');
    
    if (!cookieChoice) {
        // Show banner after a short delay
        setTimeout(() => {
            cookieConsent.classList.add('show');
        }, 1000);
    }

    // Accept all cookies
    acceptAllBtn.addEventListener('click', function() {
        localStorage.setItem('cookieChoice', 'accepted');
        cookieConsent.classList.remove('show');
    });

    // Reject all cookies
    rejectAllBtn.addEventListener('click', function() {
        localStorage.setItem('cookieChoice', 'rejected');
        cookieConsent.classList.remove('show');
    });

    // Accept only necessary cookies
    acceptNecessaryBtn.addEventListener('click', function() {
        localStorage.setItem('cookieChoice', 'necessary');
        cookieConsent.classList.remove('show');
    });
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const body = document.body;
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            body.classList.toggle('menu-open');
        });
    }

    // Close mobile menu when clicking on a link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (navMenu) navMenu.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
            body.classList.remove('menu-open');
        });
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && 
            !hamburger.contains(e.target) && 
            !navMenu.contains(e.target) && 
            navMenu.classList.contains('active')) {
            
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });

    // Close mobile menu on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
            body.classList.remove('menu-open');
        }
    });

    // Dropdown menu functionality
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        // Handle mobile dropdown toggle
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                dropdown.classList.toggle('active');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Close dropdown on mobile when clicking dropdown links
        if (menu) {
            menu.querySelectorAll('.dropdown-link').forEach(link => {
                link.addEventListener('click', () => {
                    dropdown.classList.remove('active');
                    navMenu.classList.remove('active');
                });
            });
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 70;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Navbar background change on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.backdropFilter = 'blur(10px)';
        } else {
            navbar.style.background = 'var(--white)';
            navbar.style.backdropFilter = 'none';
        }
    });

    // Contact form handling
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(contactForm);
            const name = formData.get('name');
            const email = formData.get('email');
            const company = formData.get('company');
            const message = formData.get('message');

            // Basic validation
            if (!name || !email || !message) {
                showNotification('Proszę wypełnić wszystkie wymagane pola.', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showNotification('Proszę podać poprawny adres email.', 'error');
                return;
            }

            // Show success message (in real implementation, you would send to server)
            showNotification('Dziękujemy za wiadomość! Skontaktujemy się wkrótce.', 'success');
            contactForm.reset();
        });
    }

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.feature, .service-card, .audit-type, .training-item, .shop-item').forEach(el => {
        observer.observe(el);
    });

    // Statistics Counter Animation
    const statItems = document.querySelectorAll('.stat-item');
    const statNumbers = document.querySelectorAll('.stat-number');
    let hasAnimated = false;

    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const suffix = element.getAttribute('data-suffix') || '';
        const duration = 2000; // 2 seconds
        const frameDuration = 1000 / 60; // 60 FPS
        const totalFrames = Math.round(duration / frameDuration);
        let frame = 0;
        
        const counter = setInterval(() => {
            frame++;
            const progress = frame / totalFrames;
            const current = Math.round(target * progress);
            
            element.textContent = current + suffix;
            
            if (frame === totalFrames) {
                clearInterval(counter);
                element.textContent = target + suffix;
            }
        }, frameDuration);
    }

    // Intersection Observer for statistics
    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !hasAnimated) {
                hasAnimated = true;
                
                // Add animation class to all stat items
                statItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('animate');
                        const numberElement = item.querySelector('.stat-number');
                        animateCounter(numberElement);
                    }, index * 100); // Stagger the animations
                });
            }
        });
    }, {
        threshold: 0.3
    });

    // Observe the stats section
    const statsSection = document.querySelector('.stats');
    if (statsSection) {
        statsObserver.observe(statsSection);
    }

    // Side Navigation Section Tracking
    const sections = document.querySelectorAll('section[id]');
    const sideNavDots = document.querySelectorAll('.side-nav-dot');
    let currentActiveSection = '';

    // Improved section detection using scroll position
    function updateActiveSection() {
        let currentSection = '';
        const scrollPosition = window.scrollY + 150; // Offset for navbar

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                currentSection = section.getAttribute('id');
            }
        });

        // If we're at the very bottom of the page, ensure the last section is active
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 10) {
            currentSection = sections[sections.length - 1].getAttribute('id');
        }

        // Update active dot if section changed
        if (currentSection && currentSection !== currentActiveSection) {
            currentActiveSection = currentSection;
            
            // Remove active class from all dots
            sideNavDots.forEach(dot => dot.classList.remove('active'));
            
            // Add active class to current section dot
            const correspondingDot = document.querySelector(`.side-nav-dot[data-section="${currentSection}"]`);
            if (correspondingDot) {
                correspondingDot.classList.add('active');
                // Debug info - can be removed in production
                console.log(`Active section changed to: ${currentSection}`);
            }
        }
    }

    // Throttle scroll event for better performance
    let scrollTimeout;
    function throttledUpdateActiveSection() {
        if (scrollTimeout) {
            clearTimeout(scrollTimeout);
        }
        scrollTimeout = setTimeout(updateActiveSection, 10); // Update every 10ms for smooth experience
    }

    // Use scroll event for more accurate detection
    window.addEventListener('scroll', throttledUpdateActiveSection);
    
    // Initial call to set the active section on page load
    window.addEventListener('load', updateActiveSection);
    
    // Set first section as active initially (home section)
    if (sideNavDots.length > 0) {
        sideNavDots[0].classList.add('active');
        currentActiveSection = 'home';
    }
    
    updateActiveSection();

    // Initialize particles.js - add timeout to ensure library is loaded
    setTimeout(() => {
        if (window.particlesJS) {
            console.log('Initializing particles.js');
            particlesJS("particles-js", {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: "#ffffff"
                },
                shape: {
                    type: "circle",
                    stroke: {
                        width: 0,
                        color: "#000000"
                    },
                    polygon: {
                        nb_sides: 5
                    }
                },
                opacity: {
                    value: 0.5,
                    random: false,
                    anim: {
                        enable: false,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: false,
                        speed: 40,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: "#ffffff",
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 6,
                    direction: "none",
                    random: false,
                    straight: false,
                    out_mode: "out",
                    bounce: false,
                    attract: {
                        enable: false,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: "canvas",
                events: {
                    onhover: {
                        enable: true,
                        mode: "repulse"
                    },
                    onclick: {
                        enable: true,
                        mode: "push"
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 400,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    bubble: {
                        distance: 400,
                        size: 40,
                        duration: 2,
                        opacity: 8,
                        speed: 3
                    },
                    repulse: {
                        distance: 200,
                        duration: 0.4
                    },
                    push: {
                        particles_nb: 4
                    },
                    remove: {
                        particles_nb: 2
                    }
                }
            },
            retina_detect: true
            });
        } else {
            console.error('particles.js library not loaded');
        }
    }, 100);

    // Keep the intersection observer as backup for edge cases
    const sectionObserver = new IntersectionObserver(function(entries) {
        // Only use this if scroll-based detection isn't working
        if (!currentActiveSection) {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio > 0.1) {
                    const sectionId = entry.target.getAttribute('id');
                    const correspondingDot = document.querySelector(`.side-nav-dot[data-section="${sectionId}"]`);
                    
                    if (correspondingDot) {
                        sideNavDots.forEach(dot => dot.classList.remove('active'));
                        correspondingDot.classList.add('active');
                        currentActiveSection = sectionId;
                    }
                }
            });
        }
    }, {
        threshold: [0.1, 0.3, 0.5], // Multiple thresholds
        rootMargin: '-80px 0px -80px 0px'
    });

    // Observe all sections
    sections.forEach(section => {
        sectionObserver.observe(section);
    });

    // Add click functionality to side nav dots
    sideNavDots.forEach(dot => {
        dot.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            const targetSection = document.getElementById(sectionId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 100;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Cookie consent (simple implementation)
    if (!localStorage.getItem('cookiesAccepted')) {
        showCookieBanner();
    }
});

// Helper functions
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showNotification(message, type) {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;

    // Add notification styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;

    document.body.appendChild(notification);

    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function showCookieBanner() {
    const banner = document.createElement('div');
    banner.className = 'cookie-banner';
    banner.innerHTML = `
        <div class="cookie-content">
            <p>Ta strona używa plików cookie w celu poprawienia jakości usług. Kontynuując przeglądanie wyrażasz zgodę na ich używanie.</p>
            <div class="cookie-buttons">
                <button class="btn btn-primary accept-cookies">Akceptuję</button>
                <button class="btn btn-secondary decline-cookies">Odrzuć</button>
            </div>
        </div>
    `;

    banner.style.cssText = `
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--primary-color);
        color: var(--white);
        padding: 1.5rem;
        z-index: 10000;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    `;

    document.body.appendChild(banner);

    // Accept cookies
    banner.querySelector('.accept-cookies').addEventListener('click', () => {
        localStorage.setItem('cookiesAccepted', 'true');
        banner.remove();
    });

    // Decline cookies
    banner.querySelector('.decline-cookies').addEventListener('click', () => {
        banner.remove();
    });
}

// Add CSS for animations
const animationStyles = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .animate-in {
        animation: fadeInUp 0.6s ease-out;
    }

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

    .cookie-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .cookie-buttons {
        display: flex;
        gap: 1rem;
    }

    .cookie-buttons .btn {
        padding: 8px 20px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .cookie-content {
            flex-direction: column;
            text-align: center;
        }
        
        .cookie-buttons {
            width: 100%;
            justify-content: center;
        }
    }
`;

// Inject animation styles
const styleSheet = document.createElement('style');
styleSheet.textContent = animationStyles;
document.head.appendChild(styleSheet);

