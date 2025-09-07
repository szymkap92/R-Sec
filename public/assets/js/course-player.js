// Course Player JavaScript

document.addEventListener('DOMContentLoaded', function() {
    
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
    
    // Lesson navigation
    const lessonItems = document.querySelectorAll('.lesson-item');
    
    lessonItems.forEach(item => {
        item.addEventListener('click', function() {
            if (this.classList.contains('locked')) {
                showNotification('Ta lekcja jest zablokowana. Ukończ poprzednie lekcje aby ją odblokować.', 'warning');
                return;
            }
            
            // Remove current class from all lessons
            lessonItems.forEach(lesson => lesson.classList.remove('current'));
            
            // Add current class to clicked lesson
            this.classList.add('current');
            
            // Update video content
            const lessonTitle = this.querySelector('h6').textContent;
            const lessonNumber = this.querySelector('.lesson-number').textContent;
            
            document.querySelector('.video-info h3').textContent = `Lekcja ${lessonNumber}: ${lessonTitle}`;
            document.getElementById('courseTitle').textContent = `Podstawy Cyberbezpieczeństwa - Lekcja ${lessonNumber}`;
            
            // Update progress
            updateProgress(parseInt(lessonNumber));
            
            showNotification(`Przełączono na: ${lessonTitle}`, 'success');
        });
    });
    
    // Video controls
    const playButton = document.querySelector('.play-btn');
    const videoPlayButton = document.querySelector('.video-play-button');
    let isPlaying = false;
    let currentTime = 332; // 5:32 in seconds
    const totalTime = 1500; // 25:00 in seconds
    
    function togglePlay() {
        isPlaying = !isPlaying;
        playButton.textContent = isPlaying ? '⏸️' : '⏯️';
        videoPlayButton.style.display = isPlaying ? 'none' : 'flex';
        
        if (isPlaying) {
            startVideoTimer();
            showNotification('Odtwarzanie rozpoczęte', 'info');
        } else {
            clearInterval(videoTimer);
            showNotification('Odtwarzanie wstrzymane', 'info');
        }
    }
    
    let videoTimer;
    function startVideoTimer() {
        videoTimer = setInterval(() => {
            if (isPlaying) {
                currentTime += 1;
                updateVideoTime();
                
                if (currentTime >= totalTime) {
                    currentTime = totalTime;
                    isPlaying = false;
                    playButton.textContent = '⏯️';
                    videoPlayButton.style.display = 'flex';
                    clearInterval(videoTimer);
                    showNotification('Lekcja ukończona! 🎉', 'success');
                    markLessonCompleted();
                }
            }
        }, 1000);
    }
    
    function updateVideoTime() {
        const progress = (currentTime / totalTime) * 100;
        document.querySelector('.video-progress-fill').style.width = progress + '%';
        
        const currentMinutes = Math.floor(currentTime / 60);
        const currentSeconds = currentTime % 60;
        const totalMinutes = Math.floor(totalTime / 60);
        const totalSeconds = totalTime % 60;
        
        document.querySelector('.time-display').textContent = 
            `${currentMinutes.toString().padStart(2, '0')}:${currentSeconds.toString().padStart(2, '0')} / ${totalMinutes.toString().padStart(2, '0')}:${totalSeconds.toString().padStart(2, '0')}`;
    }
    
    playButton.addEventListener('click', togglePlay);
    videoPlayButton.addEventListener('click', togglePlay);
    
    // Video progress bar click
    const videoProgressBar = document.querySelector('.video-progress-bar');
    videoProgressBar.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const barWidth = rect.width;
        const clickProgress = clickX / barWidth;
        
        currentTime = Math.floor(totalTime * clickProgress);
        updateVideoTime();
        
        showNotification(`Przeskoczono do ${Math.floor(currentTime / 60)}:${(currentTime % 60).toString().padStart(2, '0')}`, 'info');
    });
    
    // Speed control
    const speedButton = document.querySelector('.speed-btn');
    const speeds = ['0.5x', '0.75x', '1x', '1.25x', '1.5x', '2x'];
    let currentSpeedIndex = 2; // 1x
    
    speedButton.addEventListener('click', function() {
        currentSpeedIndex = (currentSpeedIndex + 1) % speeds.length;
        this.textContent = speeds[currentSpeedIndex];
        showNotification(`Prędkość zmieniona na ${speeds[currentSpeedIndex]}`, 'info');
    });
    
    // Quality control
    const qualityButton = document.querySelector('.quality-btn');
    const qualities = ['360p', '480p', '720p', '1080p'];
    let currentQualityIndex = 2; // 720p
    
    qualityButton.addEventListener('click', function() {
        currentQualityIndex = (currentQualityIndex + 1) % qualities.length;
        this.textContent = qualities[currentQualityIndex];
        showNotification(`Jakość zmieniona na ${qualities[currentQualityIndex]}`, 'info');
    });
    
    // Fullscreen
    const fullscreenButton = document.querySelector('.fullscreen-btn');
    fullscreenButton.addEventListener('click', function() {
        const videoContainer = document.querySelector('.video-container');
        
        if (!document.fullscreenElement) {
            videoContainer.requestFullscreen().then(() => {
                showNotification('Tryb pełnoekranowy włączony', 'info');
            });
        } else {
            document.exitFullscreen().then(() => {
                showNotification('Tryb pełnoekranowy wyłączony', 'info');
            });
        }
    });
    
    // Quiz functionality
    const quizOptions = document.querySelectorAll('.quiz-option input');
    const checkAnswerButton = document.querySelector('.quiz-actions .btn');
    
    checkAnswerButton.addEventListener('click', function() {
        const selectedAnswer = document.querySelector('input[name="q1"]:checked');
        
        if (!selectedAnswer) {
            showNotification('Wybierz odpowiedź przed sprawdzeniem', 'warning');
            return;
        }
        
        // Correct answer is 'b'
        if (selectedAnswer.value === 'b') {
            showNotification('Prawidłowa odpowiedź! 🎉', 'success');
            this.textContent = 'Następne pytanie';
            this.classList.add('btn-success');
        } else {
            showNotification('Nieprawidłowa odpowiedź. Spróbuj ponownie.', 'error');
            selectedAnswer.checked = false;
        }
    });
    
    // Notes functionality
    const notesTextarea = document.querySelector('.notes-textarea');
    const saveNotesButton = document.querySelector('.notes-actions .btn-secondary');
    const exportNotesButton = document.querySelector('.notes-actions .btn-primary');
    
    // Auto-save notes
    let saveTimeout;
    notesTextarea.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            localStorage.setItem('course-notes-lesson3', this.value);
            showNotification('Notatki zapisane automatycznie', 'info');
        }, 2000);
    });
    
    // Load saved notes
    const savedNotes = localStorage.getItem('course-notes-lesson3');
    if (savedNotes) {
        notesTextarea.value = savedNotes;
    }
    
    saveNotesButton.addEventListener('click', function() {
        localStorage.setItem('course-notes-lesson3', notesTextarea.value);
        showNotification('Notatki zostały zapisane!', 'success');
    });
    
    exportNotesButton.addEventListener('click', function() {
        const notes = notesTextarea.value;
        const blob = new Blob([notes], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'notatki-lekcja-3.txt';
        a.click();
        URL.revokeObjectURL(url);
        
        showNotification('Notatki wyeksportowane do pliku!', 'success');
    });
    
    // Material downloads
    const materialButtons = document.querySelectorAll('.material-item .btn');
    materialButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const materialName = this.parentElement.querySelector('h5').textContent;
            showNotification(`Pobieranie: ${materialName}`, 'info');
            
            // Simulate download
            setTimeout(() => {
                showNotification(`${materialName} został pobrany!`, 'success');
            }, 1500);
        });
    });
    
    // Lesson navigation buttons
    const prevButton = document.querySelector('.lesson-navigation .btn-secondary');
    const nextButton = document.querySelector('.lesson-navigation .btn-primary');
    
    prevButton.addEventListener('click', function() {
        const currentLesson = document.querySelector('.lesson-item.current');
        const prevLesson = currentLesson.previousElementSibling;
        
        if (prevLesson && prevLesson.classList.contains('lesson-item')) {
            prevLesson.click();
        } else {
            showNotification('To jest pierwsza lekcja w tym module', 'info');
        }
    });
    
    nextButton.addEventListener('click', function() {
        const currentLesson = document.querySelector('.lesson-item.current');
        const nextLesson = currentLesson.nextElementSibling;
        
        if (nextLesson && nextLesson.classList.contains('lesson-item')) {
            if (nextLesson.classList.contains('locked')) {
                showNotification('Ukończ bieżącą lekcję aby odblokować następną', 'warning');
            } else {
                nextLesson.click();
            }
        } else {
            showNotification('To jest ostatnia lekcja w tym module', 'info');
        }
    });
    
    // Sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.course-sidebar');
    const lessonsList = document.querySelector('.lessons-list');
    
    sidebarToggle.addEventListener('click', function() {
        if (lessonsList.style.display === 'none') {
            lessonsList.style.display = 'block';
            this.textContent = '▼';
        } else {
            lessonsList.style.display = 'none';
            this.textContent = '▶';
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.code) {
            case 'Space':
                e.preventDefault();
                togglePlay();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                currentTime = Math.max(0, currentTime - 10);
                updateVideoTime();
                break;
            case 'ArrowRight':
                e.preventDefault();
                currentTime = Math.min(totalTime, currentTime + 10);
                updateVideoTime();
                break;
            case 'KeyF':
                e.preventDefault();
                fullscreenButton.click();
                break;
        }
    });
    
    // Helper functions
    function updateProgress(lessonNumber) {
        const totalLessons = 12;
        const progress = (lessonNumber / totalLessons) * 100;
        
        document.querySelector('.course-progress-nav .progress-fill').style.width = progress + '%';
        document.querySelector('.course-progress-nav .progress-text').textContent = 
            `Lekcja ${lessonNumber} z ${totalLessons} (${Math.round(progress)}%)`;
    }
    
    function markLessonCompleted() {
        const currentLesson = document.querySelector('.lesson-item.current');
        currentLesson.classList.add('completed');
        currentLesson.querySelector('.lesson-status').textContent = '✓';
        currentLesson.querySelector('.lesson-number').style.background = 'var(--secondary-color)';
        
        // Unlock next lesson if it exists
        const nextLesson = currentLesson.nextElementSibling;
        if (nextLesson && nextLesson.classList.contains('lesson-item') && nextLesson.classList.contains('locked')) {
            nextLesson.classList.remove('locked');
            nextLesson.querySelector('.lesson-status').textContent = '';
            showNotification('Następna lekcja została odblokowana!', 'success');
        }
    }
    
    // Initialize
    updateVideoTime();
});

// Notification system
function showNotification(message, type = 'info') {
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

    const colors = {
        success: '#27ae60',
        error: '#e74c3c', 
        warning: '#f39c12',
        info: '#3498db'
    };

    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
        font-size: 0.9rem;
    `;

    document.body.appendChild(notification);

    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.remove();
    });

    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 4000);
}

// Add slide in animation
const slideInStyles = `
<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification-close {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0;
    margin-left: 0.5rem;
}

.notification-close:hover {
    opacity: 0.7;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', slideInStyles);