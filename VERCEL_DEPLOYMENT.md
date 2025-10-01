# Wdrożenie Licznika Online na Vercel

## 🚀 Instrukcje wdrożenia:

### 1. **Przygotowanie projektu**
```bash
# Upewnij się że masz wszystkie pliki:
- vercel.json (konfiguracja Vercel)
- api/online-counter.js (Vercel Function - backup)
- assets/js/online-counter-vercel.js (frontend - AKTYWNY)
```

### 2. **Wdrożenie na Vercel**
```bash
# Zaloguj się do Vercel
vercel login

# Wdróż projekt
vercel --prod
```

### 3. **Alternatywnie przez GitHub**
1. Push do GitHub repository
2. Połącz repo z Vercel Dashboard
3. Auto-deploy zostanie uruchomione

## 📋 **Obecna wersja licznika:**

### **Vercel Compatible Counter (Aktualnie aktywna)**
- ✅ **Działa na Vercel/Netlify/GitHub Pages** - bez backend
- ✅ **Realistyczne liczby** (4-15 osób w zależności od pory dnia)
- ✅ **Smart timing** - dynamiczne zmiany co 25 sekund
- ✅ **SessionStorage** zamiast localStorage
- ✅ **Responsive design** - adaptuje się do urządzeń mobilnych
- ✅ **Zero dependencies** - nie wymaga zewnętrznych bibliotek
- ✅ **Cross-browser compatibility** - działa na starszych przeglądarkach

### **Backup wersje:**

### **Wersja 2: Vercel Functions (Backup)**
- 🔄 **Wymaga Vercel Functions**
- 📁 Plik: `api/online-counter.js`
- 🔧 Konfiguracja: `vercel.json`

### **Wersja 3: Real Sessions (Backup)**
- 📁 Plik: `assets/js/online-counter-real.js`
- 💾 Używa localStorage dla prawdziwych sesji
- 🔄 Synchronizacja między tabami

## 🛠 **Zmiana wersji licznika:**

### **Aby przełączyć na Vercel Functions:**
```bash
# Zamień w wszystkich HTML:
find . -name "*.html" -exec sed -i 's/online-counter-vercel.js/online-counter.js/g' {} \;
```

### **Aby przełączyć na Real Sessions:**
```bash
# Zamień w wszystkich HTML:
find . -name "*.html" -exec sed -i 's/online-counter-vercel.js/online-counter-real.js/g' {} \;
```

### **Aby wrócić do Vercel Compatible:**
```bash
# Zamień w wszystkich HTML:
find . -name "*.html" -exec sed -i 's/online-counter-real.js/online-counter-vercel.js/g' {} \;
find . -name "*.html" -exec sed -i 's/online-counter.js/online-counter-vercel.js/g' {} \;
```

## 🧪 **Testowanie:**

### **Lokalne testowanie:**
- Otwórz `test-counter.html` w przeglądarce
- Sprawdź czy licznik się pojawia
- Powinna być widoczna liczba 4-15 (zależna od pory dnia)
- Funkcja debug: w konsoli wpisz `debugVercelCounter()`

### **Testowanie na Vercel:**
- Po deploy sprawdź dowolną stronę
- Licznik powinien pojawić się obok przełącznika języka
- Liczba powinna aktualizować się co 20 sekund
- Na urządzeniach mobilnych tekst "online" się chowa

## 🎯 **Oczekiwany rezultat:**

```
[●] 8 online    [PL | EN]
```

- Zielony pulsujący wskaźnik
- Liczba osób online (2-19)
- Text "online"
- Pozycjonowanie przed language switcher

## 🔧 **Troubleshooting:**

### **Nie pokazuje się licznik:**
1. Sprawdź Developer Tools → Console
2. Upewnij się że skrypt się ładuje
3. Sprawdź czy element `.language-switcher` istnieje

### **Pokazuje tylko kreska (-):**
1. To znaczy że JavaScript się nie wykonał
2. Sprawdź ścieżki do plików JS
3. Sprawdź błędy w konsoli

### **API nie działa:**
1. Sprawdź czy `vercel.json` jest w root
2. Sprawdź czy Vercel Functions są aktywne
3. Przełącz na Simple Counter jako backup