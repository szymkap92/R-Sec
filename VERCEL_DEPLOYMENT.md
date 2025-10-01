# Wdrożenie Licznika Online na Vercel

## 🚀 Instrukcje wdrożenia:

### 1. **Przygotowanie projektu**
```bash
# Upewnij się że masz wszystkie pliki:
- vercel.json (konfiguracja Vercel)
- api/online-counter.js (Vercel Function)
- assets/js/online-counter-simple.js (frontend)
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

## 📋 **Różne wersje licznika:**

### **Wersja 1: Simple Counter (Aktualnie aktywna)**
- ✅ **Działa na Vercel** - bez backend
- ✅ **Symuluje realistyczne liczby** (2-19 osób)
- ✅ **Dynamiczne na podstawie pory dnia**
- ✅ **Zerowe zależności**

### **Wersja 2: Vercel Functions**
- 🔄 **Wymaga Vercel Functions**
- 📁 Plik: `api/online-counter.js`
- 🔧 Konfiguracja: `vercel.json`

### **Wersja 3: Local Storage (Backup)**
- 📁 Plik: `assets/js/online-counter-local.js`
- 💾 Używa localStorage dla sesji
- 🔄 Synchronizacja między tabami

## 🛠 **Zmiana wersji licznika:**

### **Aby przełączyć na Vercel Functions:**
```bash
# Zamień w wszystkich HTML:
sed -i 's/online-counter-simple.js/online-counter.js/g' *.html
sed -i 's/online-counter-simple.js/online-counter.js/g' pages/*/*.html
```

### **Aby przełączyć na Local Storage:**
```bash
# Zamień w wszystkich HTML:
sed -i 's/online-counter-simple.js/online-counter-local.js/g' *.html
sed -i 's/online-counter-simple.js/online-counter-local.js/g' pages/*/*.html
```

## 🧪 **Testowanie:**

### **Lokalne testowanie:**
- Otwórz `test-counter.html` w przeglądarce
- Sprawdź czy licznik się pojawia
- Powinna być widoczna liczba 2-19

### **Testowanie na Vercel:**
- Po deploy sprawdź dowolną stronę
- Licznik powinien pojawić się obok przełącznika języka
- Liczba powinna aktualizować się co 30 sekund

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