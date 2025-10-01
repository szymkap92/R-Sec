# 🚨 Vercel Troubleshooting Guide

## Diagnoza problemu z licznikiem na Vercel

### 🔍 **Kroki diagnostyczne:**

#### 1. **Sprawdź stronę debug**
```
https://your-vercel-domain.vercel.app/debug-vercel.html
```
- Ta strona automatycznie zdiagnozuje najczęstsze problemy
- Sprawdzi czy skrypty się ładują
- Zweryfikuje kompatybilność przeglądarki
- Pokaże wszystkie błędy w konsoli

#### 2. **Sprawdź Developer Tools**
1. Otwórz F12 w przeglądarce
2. Zakładka **Console** - szukaj błędów czerwonych
3. Zakładka **Network** - sprawdź czy `online-counter-vercel.js` się ładuje
4. Zakładka **Elements** - sprawdź czy istnieje element `.online-counter`

#### 3. **Najczęstsze problemy i rozwiązania:**

### ❌ **Problem: Skrypt się nie ładuje (404)**

**Oznaki:**
- Console pokazuje błąd 404 dla `online-counter-vercel.js`
- Network tab pokazuje failed request

**Rozwiązanie:**
```bash
# Sprawdź czy plik istnieje w odpowiednim katalogu
ls public/assets/js/online-counter-vercel.js

# Sprawdź ścieżki w HTML - powinny być relative
grep -r "online-counter-vercel.js" public/
```

### ❌ **Problem: JavaScript error**

**Oznaki:**
- Console pokazuje błędy JavaScript
- Licznik się nie inicjalizuje

**Rozwiązanie:**
1. Sprawdź kompatybilność przeglądarki
2. Upewnij się że nie ma błędów składni
3. Sprawdź czy nie koliduje z innymi skryptami

### ❌ **Problem: Element DOM nie jest tworzony**

**Oznaki:**
- Skrypt się ładuje, ale licznik nie pojawia się
- Console mówi że `.language-switcher` nie istnieje

**Rozwiązanie:**
```javascript
// W konsoli przeglądarki:
document.querySelectorAll('.language-switcher')
// Powinno zwrócić NodeList z elementami

// Jeśli puste, sprawdź CSS i HTML
```

### ❌ **Problem: Licznik pokazuje tylko "-"**

**Oznaki:**
- Element licznika istnieje
- Ale pokazuje "-" zamiast liczby

**Rozwiązanie:**
```javascript
// W konsoli:
debugVercelCounter()
// Sprawdzi stan licznika

// Lub ręcznie:
window.vercelOnlineCounter.updateDisplay()
```

### ❌ **Problem: Storage errors**

**Oznaki:**
- Console pokazuje błędy sessionStorage
- Licznik nie zachowuje stanu

**Rozwiązanie:**
1. Sprawdź privacy settings przeglądarki
2. Sprawdź czy nie ma incognito mode
3. Sprawdź czy domena nie blokuje storage

### 🔧 **Szczegółowa diagnostyka:**

#### **Test 1: Sprawdź ścieżki**
```bash
# Struktura powinna być:
public/
  assets/
    js/
      online-counter-vercel.js
  pages/
    en/
      index-en.html (uses ../../assets/js/...)
    pl/
      index.html (uses ../../assets/js/...)
  index.html (uses assets/js/...)
```

#### **Test 2: Sprawdź network requests**
1. F12 → Network tab
2. Refresh stronę
3. Filtruj JS files
4. Sprawdź czy `online-counter-vercel.js` ma status 200

#### **Test 3: Sprawdź inicjalizację**
```javascript
// W konsoli przeglądarki:
console.log('Counter object:', window.vercelOnlineCounter);
console.log('Counter class:', window.VercelOnlineCounter);

// Test manualnej inicjalizacji:
if (!window.vercelOnlineCounter) {
    window.vercelOnlineCounter = new VercelOnlineCounter();
}
```

#### **Test 4: Sprawdź CSS**
```javascript
// Sprawdź czy styles są załadowane:
const styles = document.querySelector('#vercel-counter-styles');
console.log('Counter styles loaded:', !!styles);

// Sprawdź computed styles:
const counter = document.querySelector('.online-counter');
if (counter) {
    console.log('Counter display:', getComputedStyle(counter).display);
}
```

### 🚀 **Quick fixes:**

#### **Fix 1: Force re-initialization**
```javascript
// Usuń istniejący licznik i utwórz nowy
if (window.vercelOnlineCounter) {
    window.vercelOnlineCounter.cleanup();
}
document.querySelectorAll('.online-counter').forEach(el => el.remove());
window.vercelOnlineCounter = new VercelOnlineCounter();
```

#### **Fix 2: Manual DOM injection**
```javascript
// Jeśli automatic injection nie działa:
const switcher = document.querySelector('.language-switcher');
if (switcher && !switcher.parentNode.querySelector('.online-counter')) {
    const counter = document.createElement('div');
    counter.className = 'online-counter';
    counter.innerHTML = '<span class="online-indicator">●</span><span class="online-count">5</span><span class="online-text">online</span>';
    switcher.parentNode.insertBefore(counter, switcher);
}
```

#### **Fix 3: Fallback styles**
```javascript
// Jeśli CSS nie ładuje się:
const style = document.createElement('style');
style.textContent = `
.online-counter { 
    display: flex; 
    align-items: center; 
    gap: 4px; 
    margin-right: 1rem; 
    color: #ccc; 
    background: rgba(255,255,255,0.1); 
    padding: 4px 8px; 
    border-radius: 12px; 
}
.online-indicator { color: #00ff88; }
.online-count { font-weight: bold; color: white; }
`;
document.head.appendChild(style);
```

### 📞 **Contact dla dalszej pomocy:**

Jeśli problemy persistują, zbierz następujące informacje:
1. URL strony na Vercel
2. Screenshot console errors
3. Browser i wersja
4. Export logów z debug-vercel.html

I wyślij na biuro@r-sec.pl z tematem "Vercel Counter Issue".

---

### ✅ **Oczekiwany rezultat:**

Po naprawie powinieneś zobaczyć:
```
● 8 online    [PL | EN]
```

- Zielony pulsujący wskaźnik (●)
- Liczba 4-15 osób (zależnie od pory dnia)
- Text "online"
- Licznik aktualizuje się co 20 sekund
- Na mobile text "online" znika, zostaje tylko "● 8"