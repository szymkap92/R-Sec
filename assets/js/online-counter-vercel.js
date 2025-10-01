/**
 * VERCEL COMPATIBLE Online Counter
 * Działa na serwerach statycznych bez backend (Vercel, Netlify, GitHub Pages)
 * Symuluje realistyczne liczby + timestamp-based session tracking
 */

class VercelOnlineCounter {
    constructor() {
        this.baseCount = this.getBaseCount();
        this.sessionKey = 'rsec_session_' + this.getSessionId();
        this.lastUpdate = 0;
        this.updateInterval = 25000; // 25 sekund
        this.sessionDuration = 300000; // 5 minut
        
        this.init();
    }
    
    getSessionId() {
        // Używamy sessionStorage zamiast localStorage dla lepszej kompatybilności
        let sessionId = null;
        try {
            sessionId = sessionStorage.getItem('rsec_session_id');
            if (!sessionId) {
                sessionId = 'vs_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
                sessionStorage.setItem('rsec_session_id', sessionId);
                sessionStorage.setItem('rsec_session_start', Date.now().toString());
            }
        } catch (e) {
            // Fallback jeśli sessionStorage nie działa
            sessionId = 'vs_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
        }
        return sessionId;
    }
    
    getBaseCount() {
        const hour = new Date().getHours();
        const day = new Date().getDay();
        
        // Realistyczne wzorce ruchu na stronie
        let baseUsers = 3;
        
        // Godziny robocze (8-17) - więcej użytkowników
        if (hour >= 8 && hour <= 17) {
            baseUsers += Math.floor(Math.random() * 8) + 4; // 7-15 osób
        }
        // Wieczór (18-22) - średnio
        else if (hour >= 18 && hour <= 22) {
            baseUsers += Math.floor(Math.random() * 5) + 2; // 5-10 osób
        }
        // Noc/wczesny ranek - mniej
        else {
            baseUsers += Math.floor(Math.random() * 3) + 1; // 4-7 osób
        }
        
        // Weekend - lekko mniej ruchu w dni robocze
        if (day === 0 || day === 6) {
            baseUsers = Math.max(2, baseUsers - 2);
        }
        
        return baseUsers;
    }
    
    getCurrentCount() {
        const now = Date.now();
        
        // Co 25 sekund lekko zmienia liczbę +/- 1-2 osoby
        if (now - this.lastUpdate > this.updateInterval) {
            const variation = Math.floor(Math.random() * 5) - 2; // -2 do +2
            this.baseCount = Math.max(1, this.baseCount + variation);
            this.lastUpdate = now;
        }
        
        return this.baseCount;
    }
    
    init() {
        this.createCounterElement();
        this.updateDisplay();
        this.startUpdateTimer();
        
        // Event listeners dla lepszej responsywności
        this.setupEventHandlers();
        
        console.log('[Vercel Counter] Zainicjalizowano');
    }
    
    createCounterElement() {
        // Znajdź wszystkie language switchers
        const languageSwitchers = document.querySelectorAll('.language-switcher');
        
        languageSwitchers.forEach((switcher) => {
            // Sprawdź czy licznik już nie istnieje
            if (!switcher.parentNode.querySelector('.online-counter')) {
                const counterElement = document.createElement('div');
                counterElement.className = 'online-counter';
                counterElement.innerHTML = `
                    <span class="online-indicator">●</span>
                    <span class="online-count">-</span>
                    <span class="online-text">online</span>
                `;
                
                // Wstaw przed language switcher
                switcher.parentNode.insertBefore(counterElement, switcher);
            }
        });
    }
    
    updateDisplay() {
        const count = this.getCurrentCount();
        const counters = document.querySelectorAll('.online-count');
        const indicators = document.querySelectorAll('.online-indicator');
        
        counters.forEach(counter => {
            counter.textContent = count;
        });
        
        // Animacja pulsowania wskaźnika
        indicators.forEach(indicator => {
            indicator.style.animation = 'none';
            setTimeout(() => {
                indicator.style.animation = 'pulse 2s infinite';
            }, 50);
        });
        
        console.log(`[Vercel Counter] Aktualizacja: ${count} osób online`);
    }
    
    setupEventHandlers() {
        // Aktualizuj gdy strona zyskuje focus
        window.addEventListener('focus', () => {
            this.updateDisplay();
        });
        
        // Aktualizuj gdy tab staje się widoczny
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                setTimeout(() => this.updateDisplay(), 1000);
            }
        });
        
        // Obsługa nawigacji (back/forward)
        window.addEventListener('pageshow', () => {
            setTimeout(() => this.updateDisplay(), 500);
        });
    }
    
    startUpdateTimer() {
        // Aktualizuj wyświetlanie co 20 sekund
        setInterval(() => {
            this.updateDisplay();
        }, 20000);
        
        // Co minutę sprawdź i zaktualizuj bazową liczbę
        setInterval(() => {
            this.baseCount = this.getBaseCount();
        }, 60000);
    }
}

// Style CSS zoptymalizowane dla wszystkich urządzeń
const vercelCounterStyles = `
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
    white-space: nowrap;
}

.online-counter:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.online-indicator {
    color: #00ff88;
    font-size: 0.6rem;
    animation: pulse 2s infinite;
    line-height: 1;
}

.online-count {
    font-weight: 600;
    color: #fff;
    min-width: 16px;
    text-align: center;
    line-height: 1;
}

.online-text {
    font-size: 0.75rem;
    opacity: 0.8;
    line-height: 1;
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

/* Responsive design */
@media (max-width: 768px) {
    .online-counter {
        font-size: 0.75rem;
        padding: 2px 6px;
        margin-right: 0.5rem;
        gap: 3px;
    }
    
    .online-text {
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .online-text {
        display: none;
    }
    
    .online-counter {
        padding: 2px 4px;
        margin-right: 0.25rem;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .online-counter {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
}

/* Fallback dla starszych przeglądarek */
.online-counter {
    -webkit-backdrop-filter: blur(10px);
}
`;

function addVercelCounterStyles() {
    if (!document.querySelector('#vercel-counter-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'vercel-counter-styles';
        styleElement.textContent = vercelCounterStyles;
        document.head.appendChild(styleElement);
    }
}

// Inicjalizacja - kompatybilna z różnymi stanami ładowania strony
function initVercelCounter() {
    addVercelCounterStyles();
    
    // Małe opóźnienie żeby upewnić się że DOM jest gotowy
    setTimeout(() => {
        if (!window.vercelOnlineCounter) {
            window.vercelOnlineCounter = new VercelOnlineCounter();
        }
    }, 300);
}

// Różne metody inicjalizacji dla maksymalnej kompatybilności
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVercelCounter);
} else {
    initVercelCounter();
}

// Backup jeśli poprzednie nie zadziałały
window.addEventListener('load', () => {
    setTimeout(() => {
        if (!window.vercelOnlineCounter) {
            initVercelCounter();
        }
    }, 500);
});

// Export dla debugowania
window.VercelOnlineCounter = VercelOnlineCounter;

// Debug function
window.debugVercelCounter = () => {
    if (window.vercelOnlineCounter) {
        const counter = window.vercelOnlineCounter;
        console.log('Current count:', counter.getCurrentCount());
        console.log('Base count:', counter.baseCount);
        console.log('Session ID:', counter.sessionKey);
        return counter;
    }
    return null;
};