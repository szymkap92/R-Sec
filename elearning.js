// E-Learning Platform JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Course filtering functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const courseCards = document.querySelectorAll('.course-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const filterCategory = this.getAttribute('data-category');
            
            courseCards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                
                if (filterCategory === 'all' || cardCategory === filterCategory) {
                    card.classList.remove('hidden');
                    card.classList.add('show');
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('show');
                }
            });
        });
    });
    
    // Course enrollment functionality
    const enrollButtons = document.querySelectorAll('.course-btn');
    
    enrollButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseCard = this.closest('.course-card');
            const courseTitle = courseCard.querySelector('h3').textContent;
            const isEnrolled = this.textContent.includes('Kontynuuj');
            
            if (isEnrolled) {
                // Navigate to course content
                showNotification(`Przechodzisz do kursu: ${courseTitle}`, 'success');
                // Here you would navigate to the actual course content
                setTimeout(() => {
                    window.location.href = 'course-player.html?course=' + encodeURIComponent(courseTitle);
                }, 1000);
            } else {
                // Enroll in course
                showEnrollmentModal(courseTitle, courseCard);
            }
        });
    });
    
    // Progress bar animations
    const progressBars = document.querySelectorAll('.progress-fill');
    
    const progressObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const progressBar = entry.target;
                const width = progressBar.style.width;
                progressBar.style.width = '0%';
                
                setTimeout(() => {
                    progressBar.style.width = width;
                }, 200);
            }
        });
    }, { threshold: 0.5 });
    
    progressBars.forEach(bar => {
        progressObserver.observe(bar);
    });
    
    // Search functionality
    function createSearchBar() {
        const searchHTML = `
            <div class="search-container">
                <input type="text" id="courseSearch" placeholder="🔍 Szukaj kursów..." class="search-input">
            </div>
        `;
        
        const categoriesSection = document.querySelector('.course-categories .container');
        categoriesSection.insertAdjacentHTML('beforeend', searchHTML);
        
        const searchInput = document.getElementById('courseSearch');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            courseCards.forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const description = card.querySelector('p').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    createSearchBar();
    
    // Course statistics animation
    function animateHeroStats() {
        const heroStats = document.querySelectorAll('.hero-stat .stat-number');
        
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumber = entry.target;
                    const finalValue = statNumber.textContent;
                    const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                    const suffix = finalValue.replace(/\d/g, '');
                    
                    let currentValue = 0;
                    const increment = numericValue / 60; // 1 second animation at 60fps
                    
                    const counter = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= numericValue) {
                            statNumber.textContent = finalValue;
                            clearInterval(counter);
                        } else {
                            statNumber.textContent = Math.floor(currentValue) + suffix;
                        }
                    }, 16);
                    
                    statsObserver.unobserve(statNumber);
                }
            });
        }, { threshold: 0.8 });
        
        heroStats.forEach(stat => {
            statsObserver.observe(stat);
        });
    }
    
    animateHeroStats();
});

// Enrollment modal functionality
function showEnrollmentModal(courseTitle, courseCard) {
    const price = courseCard.querySelector('.price').textContent;
    const duration = courseCard.querySelector('.course-duration').textContent;
    const lessons = courseCard.querySelector('.course-lessons').textContent;
    
    const modalHTML = `
        <div class="modal-overlay" id="enrollmentModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>🎓 Zapisz się na kurs</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <h4>${courseTitle}</h4>
                    <div class="course-details">
                        <p><strong>Cena:</strong> ${price}</p>
                        <p><strong>Czas trwania:</strong> ${duration}</p>
                        <p><strong>Liczba lekcji:</strong> ${lessons}</p>
                    </div>
                    <form class="enrollment-form">
                        <div class="form-group">
                            <input type="text" placeholder="Imię i nazwisko" required>
                        </div>
                        <div class="form-group">
                            <input type="email" placeholder="Adres email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" placeholder="Telefon">
                        </div>
                        <div class="form-group">
                            <input type="text" placeholder="Firma (opcjonalnie)">
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" required>
                                Akceptuję regulamin i politykę prywatności
                            </label>
                        </div>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox">
                                Chcę otrzymywać informacje o nowych kursach
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-large">
                            Zapisz się teraz
                        </button>
                    </form>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('enrollmentModal');
    const closeBtn = modal.querySelector('.modal-close');
    const form = modal.querySelector('.enrollment-form');
    
    // Close modal functionality
    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Simulate enrollment process
        showNotification('Przetwarzamy Twoje zgłoszenie...', 'info');
        
        setTimeout(() => {
            closeModal();
            showNotification(`Gratulacje! Zostałeś zapisany na kurs: ${courseTitle}`, 'success');
            
            // Update button to show enrollment
            const courseBtn = courseCard.querySelector('.course-btn');
            courseBtn.textContent = 'Kontynuuj';
            courseBtn.classList.remove('btn-primary');
            courseBtn.classList.add('btn-secondary');
            
            // Update progress
            const progressFill = courseCard.querySelector('.progress-fill');
            const progressText = courseCard.querySelector('.progress-text');
            progressFill.style.width = '5%';
            progressText.textContent = '5% ukończone';
            
        }, 2000);
    });
    
    function closeModal() {
        modal.remove();
    }
}

// Notification system (reuse from main script if available)
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
        background: ${type === 'success' ? '#27ae60' : type === 'info' ? '#3498db' : '#e74c3c'};
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

    // Auto remove after 4 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 4000);
}

// Add modal styles dynamically
const modalStyles = `
    <style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        backdrop-filter: blur(5px);
    }
    
    .modal-content {
        background: var(--bg-darker);
        border-radius: 15px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        border: 2px solid var(--secondary-color);
        box-shadow: 0 20px 40px rgba(255, 0, 0, 0.3);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2rem;
        border-bottom: 1px solid var(--secondary-color);
    }
    
    .modal-header h3 {
        color: var(--white);
        margin: 0;
        font-size: 1.5rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        color: var(--white);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }
    
    .modal-close:hover {
        background: var(--secondary-color);
        color: var(--white);
    }
    
    .modal-body {
        padding: 2rem;
    }
    
    .modal-body h4 {
        color: var(--secondary-color);
        margin-bottom: 1rem;
        font-size: 1.3rem;
    }
    
    .course-details {
        background: rgba(255, 255, 255, 0.05);
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        border-left: 4px solid var(--secondary-color);
    }
    
    .course-details p {
        color: var(--text-light);
        margin: 0.5rem 0;
    }
    
    .enrollment-form .form-group {
        margin-bottom: 1.5rem;
    }
    
    .enrollment-form input {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #333;
        border-radius: 8px;
        background: var(--bg-darker);
        color: var(--white);
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .enrollment-form input:focus {
        outline: none;
        border-color: var(--secondary-color);
        box-shadow: 0 0 10px var(--red-glow);
    }
    
    .checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 0.8rem;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: auto;
        margin: 0;
        transform: scale(1.2);
    }
    
    .checkbox-group label {
        color: var(--text-light);
        font-size: 0.9rem;
        line-height: 1.4;
        cursor: pointer;
    }
    
    .search-container {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
    }
    
    .search-input {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid var(--white);
        color: var(--white);
        padding: 0.8rem 1.5rem;
        border-radius: 25px;
        font-size: 1rem;
        width: 100%;
        max-width: 400px;
        transition: var(--transition);
    }
    
    .search-input:focus {
        outline: none;
        border-color: var(--secondary-color);
        box-shadow: 0 0 20px var(--red-glow);
        background: rgba(255, 255, 255, 0.15);
    }
    
    .search-input::placeholder {
        color: var(--text-light);
    }
    </style>
`;

document.head.insertAdjacentHTML('beforeend', modalStyles);