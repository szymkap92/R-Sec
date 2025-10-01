/**
 * Online Counter - Local/Static Version
 * Symuluje licznik online dla stron statycznych
 */

class OnlineCounterLocal {
    constructor() {
        this.sessionId = this.getOrCreateSessionId();
        this.activeUsers = new Set();
        this.heartbeatInterval = 15000; // 15 sekund
        this.updateInterval = 20000; // 20 sekund
        this.heartbeatTimer = null;
        this.updateTimer = null;
        this.lastHeartbeat = Date.now();
        
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
        this.loadStoredSessions();
        this.addCurrentSession();
        this.updateDisplay();
        this.startHeartbeat();
        this.startUpdates();
        
        // Cleanup przy opuszczeniu strony
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
        
        // Obsługa visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopTimers();
            } else {
                this.loadStoredSessions();
                this.addCurrentSession();
                this.startHeartbeat();
                this.startUpdates();
                this.updateDisplay();
            }
        });
    }
    
    createCounterElement() {
        const languageSwitchers = document.querySelectorAll('.language-switcher');
        
        languageSwitchers.forEach((switcher) => {
            if (!switcher.querySelector('.online-counter')) {
                const counterElement = document.createElement('div');
                counterElement.className = 'online-counter';
                counterElement.innerHTML = `
                    <span class="online-indicator">●</span>
                    <span class="online-count">-</span>
                    <span class="online-text">online</span>
                `;
                
                switcher.parentNode.insertBefore(counterElement, switcher);
            }
        });
    }
    
    loadStoredSessions() {
        try {
            const stored = localStorage.getItem('rsec_online_sessions');
            if (stored) {
                const sessions = JSON.parse(stored);
                const currentTime = Date.now();
                const timeout = 30000; // 30 sekund
                
                // Wyczyść stare sesje
                const activeSessions = {};
                for (const [sessionId, session] of Object.entries(sessions)) {
                    if ((currentTime - session.lastSeen) < timeout) {
                        activeSessions[sessionId] = session;
                    }
                }
                
                this.activeUsers = new Set(Object.keys(activeSessions));
                localStorage.setItem('rsec_online_sessions', JSON.stringify(activeSessions));
            } else {
                this.activeUsers = new Set();
            }
        } catch (error) {
            console.warn('Error loading sessions:', error);
            this.activeUsers = new Set();
        }
    }
    
    addCurrentSession() {
        this.activeUsers.add(this.sessionId);
        this.saveSession();
    }
    
    saveSession() {
        try {
            const stored = localStorage.getItem('rsec_online_sessions');
            const sessions = stored ? JSON.parse(stored) : {};
            
            sessions[this.sessionId] = {
                lastSeen: Date.now(),
                userAgent: navigator.userAgent
            };
            
            localStorage.setItem('rsec_online_sessions', JSON.stringify(sessions));
        } catch (error) {
            console.warn('Error saving session:', error);
        }
    }
    
    updateDisplay() {
        // Dodaj trochę realistycznej symulacji
        const baseCount = this.activeUsers.size;
        const randomVariation = Math.floor(Math.random() * 5) + 1; // 1-5 dodatkowych użytkowników
        const simulatedCount = Math.max(1, baseCount + randomVariation);
        
        const counters = document.querySelectorAll('.online-count');
        const indicators = document.querySelectorAll('.online-indicator');
        
        counters.forEach(counter => {
            counter.textContent = simulatedCount;
        });
        
        // Animacja wskaźnika
        indicators.forEach(indicator => {
            indicator.style.animation = 'none';
            setTimeout(() => {
                indicator.style.animation = 'pulse 2s infinite';
            }, 100);
        });
    }
    
    sendHeartbeat() {
        this.lastHeartbeat = Date.now();
        this.loadStoredSessions();
        this.addCurrentSession();
        this.updateDisplay();
        
        // Symuluj broadcast do innych okien/tabów
        try {
            const event = new CustomEvent('rsec-heartbeat', {
                detail: { sessionId: this.sessionId, timestamp: this.lastHeartbeat }
            });
            window.dispatchEvent(event);
        } catch (error) {
            // Ignore errors
        }
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
            this.loadStoredSessions();
            this.updateDisplay();
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
        // Usuń własną sesję przy zamykaniu
        try {
            const stored = localStorage.getItem('rsec_online_sessions');
            if (stored) {
                const sessions = JSON.parse(stored);
                delete sessions[this.sessionId];
                localStorage.setItem('rsec_online_sessions', JSON.stringify(sessions));
            }
        } catch (error) {
            // Ignore cleanup errors
        }
    }
}

// Style CSS dla licznika (takie same jak w wersji oryginalnej)
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

@media (prefers-color-scheme: dark) {
    .online-counter {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
}
`;

function addOnlineCounterStyles() {
    const styleElement = document.createElement('style');
    styleElement.textContent = onlineCounterStyles;
    document.head.appendChild(styleElement);
}

// Inicjalizacja
document.addEventListener('DOMContentLoaded', () => {
    addOnlineCounterStyles();
    
    setTimeout(() => {
        new OnlineCounterLocal();
    }, 500);
});

// Export
window.OnlineCounterLocal = OnlineCounterLocal;