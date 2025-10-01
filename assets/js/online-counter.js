/**
 * Online Counter System
 * Pokazuje liczbę osób obecnie przeglądających stronę
 */

class OnlineCounter {
    constructor() {
        this.sessionId = this.getOrCreateSessionId();
        this.apiUrl = 'api/online-counter.php';
        this.heartbeatInterval = 15000; // 15 sekund
        this.updateInterval = 20000; // 20 sekund
        this.heartbeatTimer = null;
        this.updateTimer = null;
        
        this.init();
    }
    
    getOrCreateSessionId() {
        let sessionId = localStorage.getItem('rsec_session_id');
        if (!sessionId) {
            sessionId = 'rsec_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('rsec_session_id', sessionId);
        }
        return sessionId;
    }
    
    init() {
        this.createCounterElement();
        this.sendHeartbeat(); // Pierwsze wysłanie
        this.startHeartbeat();
        this.startUpdates();
        
        // Cleanup przy opuszczeniu strony
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
        
        // Cleanup przy ukryciu strony (tab switching)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopTimers();
            } else {
                this.startHeartbeat();
                this.startUpdates();
                this.sendHeartbeat();
            }
        });
    }
    
    createCounterElement() {
        // Znajdź element language-switcher we wszystkich możliwych lokalizacjach
        const languageSwitchers = document.querySelectorAll('.language-switcher');
        
        languageSwitchers.forEach((switcher, index) => {
            // Utwórz element licznika tylko jeśli jeszcze nie istnieje
            if (!switcher.querySelector('.online-counter')) {
                const counterElement = document.createElement('div');
                counterElement.className = 'online-counter';
                counterElement.innerHTML = `
                    <span class="online-indicator">●</span>
                    <span class="online-count">-</span>
                    <span class="online-text">online</span>
                `;
                
                // Dodaj przed language-switcher
                switcher.parentNode.insertBefore(counterElement, switcher);
            }
        });
    }
    
    async sendHeartbeat() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sessionId: this.sessionId
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.updateDisplay(data.onlineCount);
            }
        } catch (error) {
            console.warn('Online counter heartbeat failed:', error);
            // W przypadku błędu pokazuj placeholder
            this.updateDisplay('-');
        }
    }
    
    async getOnlineCount() {
        try {
            const response = await fetch(this.apiUrl + '?t=' + Date.now());
            const data = await response.json();
            if (data.success) {
                this.updateDisplay(data.onlineCount);
            }
        } catch (error) {
            console.warn('Online counter update failed:', error);
        }
    }
    
    updateDisplay(count) {
        const counters = document.querySelectorAll('.online-count');
        const indicators = document.querySelectorAll('.online-indicator');
        
        counters.forEach(counter => {
            counter.textContent = count;
        });
        
        // Animacja wskaźnika (pulsowanie)
        indicators.forEach(indicator => {
            indicator.style.animation = 'none';
            setTimeout(() => {
                indicator.style.animation = 'pulse 2s infinite';
            }, 100);
        });
    }
    
    startHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
        }
        this.heartbeatTimer = setInterval(() => {
            this.sendHeartbeat();
        }, this.heartbeatInterval);
    }
    
    startUpdates() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        this.updateTimer = setInterval(() => {
            this.getOnlineCount();
        }, this.updateInterval);
    }
    
    stopTimers() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }
    
    cleanup() {
        this.stopTimers();
    }
}

// Style CSS dla licznika
const onlineCounterStyles = `
.online-counter {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-light, #888);
    margin-right: 1rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 8px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.online-counter:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.online-indicator {
    color: #00ff88;
    font-size: 0.6rem;
    animation: pulse 2s infinite;
}

.online-count {
    font-weight: 600;
    color: #fff;
    min-width: 16px;
    text-align: center;
}

.online-text {
    font-size: 0.75rem;
    opacity: 0.8;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.1);
    }
}

/* Responsywność */
@media (max-width: 768px) {
    .online-counter {
        font-size: 0.75rem;
        padding: 2px 6px;
        margin-right: 0.5rem;
    }
    
    .online-text {
        display: none;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .online-counter {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
}
`;

// Dodaj style do strony
function addOnlineCounterStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = onlineCounterStyles;
    document.head.appendChild(styleElement);
}

// Inicjalizacja po załadowaniu DOM
document.addEventListener('DOMContentLoaded', () => {
    addOnlineCounterStyles();
    
    // Małe opóźnienie żeby navbar się załadował
    setTimeout(() => {
        new OnlineCounter();
    }, 500);
});

// Export dla użycia w innych miejscach
window.OnlineCounter = OnlineCounter;