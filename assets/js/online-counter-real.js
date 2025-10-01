/**
 * REAL Online Counter - Prawdziwy licznik osób online
 * Zlicza rzeczywiste sesje używając localStorage + BroadcastChannel
 */

class RealOnlineCounter {
    constructor() {
        this.sessionId = this.generateSessionId();
        this.storageKey = 'rsec_real_sessions';
        this.broadcastChannel = null;
        this.cleanupInterval = null;
        this.updateInterval = null;
        this.sessionTimeout = 30000; // 30 sekund
        
        this.init();
    }
    
    generateSessionId() {
        return 'rsec_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    init() {
        this.createCounterElement();
        this.setupBroadcastChannel();
        this.registerSession();
        this.startCleanupTimer();
        this.startUpdateTimer();
        this.updateDisplay();
        
        // Event handlers
        this.setupEventHandlers();
        
        console.log(`[Online Counter] Sesja zarejestrowana: ${this.sessionId}`);
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
    
    setupBroadcastChannel() {
        if ('BroadcastChannel' in window) {
            this.broadcastChannel = new BroadcastChannel('rsec_online_counter');
            this.broadcastChannel.addEventListener('message', (event) => {
                if (event.data.type === 'session_update') {
                    this.updateDisplay();
                }
            });
        }
    }
    
    broadcastUpdate() {
        if (this.broadcastChannel) {
            this.broadcastChannel.postMessage({
                type: 'session_update',
                sessionId: this.sessionId,
                timestamp: Date.now()
            });
        }
    }
    
    registerSession() {
        const sessions = this.getSessions();
        sessions[this.sessionId] = {
            lastHeartbeat: Date.now(),
            userAgent: navigator.userAgent,
            url: window.location.href,
            startTime: Date.now()
        };
        
        this.saveSessions(sessions);
        this.broadcastUpdate();
        
        console.log(`[Online Counter] Aktywne sesje: ${Object.keys(sessions).length}`);
    }
    
    updateHeartbeat() {
        const sessions = this.getSessions();
        if (sessions[this.sessionId]) {
            sessions[this.sessionId].lastHeartbeat = Date.now();
            this.saveSessions(sessions);
            this.broadcastUpdate();
        }
    }
    
    getSessions() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : {};
        } catch (error) {
            console.warn('[Online Counter] Error loading sessions:', error);
            return {};
        }
    }
    
    saveSessions(sessions) {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(sessions));
        } catch (error) {
            console.warn('[Online Counter] Error saving sessions:', error);
        }
    }
    
    cleanExpiredSessions() {
        const sessions = this.getSessions();
        const currentTime = Date.now();
        let removedCount = 0;
        
        for (const [sessionId, session] of Object.entries(sessions)) {
            if ((currentTime - session.lastHeartbeat) > this.sessionTimeout) {
                delete sessions[sessionId];
                removedCount++;
            }
        }
        
        if (removedCount > 0) {
            this.saveSessions(sessions);
            this.broadcastUpdate();
            console.log(`[Online Counter] Usunięto ${removedCount} wygasłych sesji`);
        }
        
        return sessions;
    }
    
    getOnlineCount() {
        const sessions = this.cleanExpiredSessions();
        return Object.keys(sessions).length;
    }
    
    updateDisplay() {
        const count = this.getOnlineCount();
        const counters = document.querySelectorAll('.online-count');
        const indicators = document.querySelectorAll('.online-indicator');
        
        counters.forEach(counter => {
            counter.textContent = count;
        });
        
        // Animacja wskaźnika
        indicators.forEach(indicator => {
            indicator.style.animation = 'none';
            setTimeout(() => {
                indicator.style.animation = 'pulse 2s infinite';
            }, 100);
        });
        
        console.log(`[Online Counter] Aktualizacja wyświetlania: ${count} osób online`);
    }
    
    setupEventHandlers() {
        // Heartbeat co 10 sekund
        setInterval(() => {
            this.updateHeartbeat();
        }, 10000);
        
        // Cleanup przy zamykaniu strony
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
        
        // Obsługa visibility change (przełączanie tabów)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                // Tab stał się aktywny - odśwież
                this.updateHeartbeat();
                this.updateDisplay();
            }
        });
        
        // Focus/blur events
        window.addEventListener('focus', () => {
            this.updateHeartbeat();
            this.updateDisplay();
        });
        
        // Page show/hide events (back/forward navigation)
        window.addEventListener('pageshow', () => {
            this.updateHeartbeat();
            this.updateDisplay();
        });
    }
    
    startCleanupTimer() {
        // Czyść stare sesje co 15 sekund
        this.cleanupInterval = setInterval(() => {
            this.cleanExpiredSessions();
            this.updateDisplay();
        }, 15000);
    }
    
    startUpdateTimer() {
        // Aktualizuj wyświetlanie co 5 sekund
        this.updateInterval = setInterval(() => {
            this.updateDisplay();
        }, 5000);
    }
    
    cleanup() {
        console.log(`[Online Counter] Cleanup sesji: ${this.sessionId}`);
        
        // Usuń własną sesję
        const sessions = this.getSessions();
        delete sessions[this.sessionId];
        this.saveSessions(sessions);
        
        // Wyczyść timery
        if (this.cleanupInterval) {
            clearInterval(this.cleanupInterval);
        }
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        
        // Zamknij broadcast channel
        if (this.broadcastChannel) {
            this.broadcastChannel.close();
        }
        
        this.broadcastUpdate();
    }
    
    // Debug methods
    debugGetAllSessions() {
        return this.getSessions();
    }
    
    debugForceCleanup() {
        const sessions = this.cleanExpiredSessions();
        this.updateDisplay();
        return sessions;
    }
}

// Style CSS (takie same jak wcześniej)
const realCounterStyles = `
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

function addRealCounterStyles() {
    if (!document.querySelector('#real-counter-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'real-counter-styles';
        styleElement.textContent = realCounterStyles;
        document.head.appendChild(styleElement);
    }
}

// Inicjalizacja
document.addEventListener('DOMContentLoaded', () => {
    addRealCounterStyles();
    
    setTimeout(() => {
        window.realOnlineCounter = new RealOnlineCounter();
    }, 500);
});

// Debug w konsoli
window.debugOnlineCounter = () => {
    if (window.realOnlineCounter) {
        const sessions = window.realOnlineCounter.debugGetAllSessions();
        console.log('Wszystkie sesje:', sessions);
        console.log('Liczba aktywnych sesji:', Object.keys(sessions).length);
        return sessions;
    }
    return null;
};

window.RealOnlineCounter = RealOnlineCounter;