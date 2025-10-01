/**
 * Simple Online Counter - Stateless Version
 * Pokazuje symulowaną liczbę osób online
 * Dla zastosowania gdy API nie jest dostępne
 */

class SimpleOnlineCounter {
    constructor() {
        this.init();
    }
    
    init() {
        this.createCounterElement();
        this.startSimulation();
        
        // Update co 30 sekund
        setInterval(() => {
            this.updateDisplay();
        }, 30000);
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
    
    generateRealisticCount() {
        const now = new Date();
        const hour = now.getHours();
        
        // Symuluj ruch w ciągu dnia (strefa czasowa europejska)
        let baseCount;
        if (hour >= 9 && hour <= 17) {
            // Godziny pracy - więcej ruchu
            baseCount = Math.floor(Math.random() * 12) + 8; // 8-19
        } else if (hour >= 18 && hour <= 22) {
            // Wieczór - średni ruch
            baseCount = Math.floor(Math.random() * 8) + 5; // 5-12
        } else {
            // Noc - mniej ruchu
            baseCount = Math.floor(Math.random() * 5) + 2; // 2-6
        }
        
        // Dodaj małą losowość
        const variation = Math.floor(Math.random() * 3) - 1; // -1, 0, lub 1
        return Math.max(1, baseCount + variation);
    }
    
    updateDisplay() {
        const count = this.generateRealisticCount();
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
    }
    
    startSimulation() {
        // Pierwsza aktualizacja po krótkim opóźnieniu
        setTimeout(() => {
            this.updateDisplay();
        }, 1000);
    }
}

// Style CSS
const simpleCounterStyles = `
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
`;

function addSimpleCounterStyles() {
    if (!document.querySelector('#simple-counter-styles')) {
        const styleElement = document.createElement('style');
        styleElement.id = 'simple-counter-styles';
        styleElement.textContent = simpleCounterStyles;
        document.head.appendChild(styleElement);
    }
}

// Auto-inicjalizacja
document.addEventListener('DOMContentLoaded', () => {
    addSimpleCounterStyles();
    
    setTimeout(() => {
        new SimpleOnlineCounter();
    }, 500);
});

window.SimpleOnlineCounter = SimpleOnlineCounter;