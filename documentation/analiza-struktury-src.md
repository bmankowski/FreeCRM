# Analiza struktury katalogu `src/` w FreeCRM

**Data:** 2025-11-16  
**Autor:** Analiza struktury projektu

---

## 1. Obecna sytuacja

### 1.1 Struktura autoloadingu (composer.json)

```json
"autoload": {
    "psr-4": {
        "vtlib\\": "src/ModuleManagement/Adapters/",
        "App\\": "src/",
        "Exception\\": "src/Exceptions/"
    }
}
```

### 1.2 Pliki w głównym katalogu `src/`

Aktualnie w katalogu `src/` znajduje się **32 pojedyncze pliki PHP**, wszystkie z namespace `App`:

1. AppConfig.php
2. Company.php
3. CRMEntity.php
4. Currency.php
5. CustomFieldUtil.php
6. CustomView.php
7. Db.php
8. Debugger.php
9. EmailParser.php
10. Encryption.php
11. EventHandler.php
12. Field.php
13. Json.php
14. Loader.php
15. Log.php
16. Mail.php
17. Mailer.php
18. ModuleHierarchy.php
19. Privilege.php
20. PrivilegeAdvanced.php
21. PrivilegeFile.php
22. PrivilegeQuery.php
23. PrivilegeUpdater.php
24. PrivilegeUtil.php
25. Purifier.php
26. QueryGenerator.php
27. Record.php
28. RecordSearch.php
29. RequestUtil.php
30. SystemWarnings.php
31. TextParser.php
32. Version.php

### 1.3 Podkatalogi w `src/`

Projekt ma już **26 podkatalogów** z odpowiednio zorganizowaną strukturą:

- **Api/** - namespace `App\Api`
- **App/** - namespace `App\App` (zawiera tylko `Db/`)
- **Base/** - legacy base classes
- **Cache/** - namespace `App\Cache` (Cache.php, Apcu.php, Base.php, XCache.php)
- **Custom/** - customizacje
- **Database/** - namespace `App\Database` (PearDatabase.php)
- **Db/** - namespace `App\Db` (Query builders, helpers)
- **Debug/** - narzędzia debugowania
- **EntryPoint/** - punkty wejścia aplikacji ✓
- **Events/** - system eventów
- **Exceptions/** - namespace `Exception\` (własne wyjątki)
- **Fields/** - namespace `App\Fields` (CurrencyField.php, DateTimeField.php, etc.)
- **Http/** - obsługa HTTP
- **Layout/** - zarządzanie layoutami
- **Log/** - system logowania
- **Main/** - główne komponenty
- **ModuleManagement/** - namespace `vtlib` (zarządzanie modułami)
- **Modules/** - namespace `App\Modules` (nowoczesne implementacje modułów) ✓
- **QueryField/** - namespace `App\QueryField`
- **Runtime/** - namespace `App\Runtime` (pliki runtime)
- **SystemWarnings/** - namespace `App\SystemWarnings`
- **TextParser/** - namespace `App\TextParser`
- **User/** - zarządzanie użytkownikami
- **Utils/** - namespace `App\Utils` (różne utility classes)
- **View/** - komponenty widoków
- **Webservices/** - namespace `App\Webservices`

---

## 2. Ocena zgodności z architecture.mdc

### 2.1 Zgodność z zalecaną strukturą

Według dokumentu `.cursor/rules/architecture.mdc`, folder `/src/` powinien zawierać:

✓ **Zgodne:**
- `/src/EntryPoint/` - Application entry points (WebUI, API, etc.) ✓ ISTNIEJE
- `/src/Modules/` - Modern module implementations ✓ ISTNIEJE

⚠️ **Niezgodności:**
- Dokument wspomina o `/include/` jako "Legacy Runtime" - **ten folder NIE ISTNIEJE**
- Dokument nie przewiduje 32 pojedynczych plików w głównym katalogu `src/`

### 2.2 Zgodność z PSR-4

**Technicznie poprawne:**
Zgodnie z PSR-4 autoloading (`"App\\": "src/"`):
- Klasa `App\AppConfig` → `src/AppConfig.php` ✓
- Klasa `App\Db` → `src/Db.php` ✓
- Klasa `App\Record` → `src/Record.php` ✓

Wszystkie 32 pliki są **technicznie poprawnie zlokalizowane** według PSR-4.

**Architekturalnie niepoprawne:**
- Zbyt wiele klas w głównym katalogu namespace
- Brak organizacji tematycznej
- Trudność w nawigacji i zrozumieniu struktury

---

## 3. Analiza problemu

### 3.1 Dlaczego to jest problem?

1. **Trudność w nawigacji**
   - 32 pliki w jednym katalogu utrudnia szybkie znalezienie właściwej klasy
   - IDE pokazuje dużą płaską listę plików

2. **Brak organizacji semantycznej**
   - Klasy związane z bazą danych: `Db.php`, `QueryGenerator.php`
   - Klasy związane z uprawnieniami: `Privilege*.php` (6 plików!)
   - Klasy pomocnicze: `*Util.php`
   - Wszystkie w jednym miejscu

3. **Niespójność z istniejącymi podkatalogami**
   - Mamy już `Cache/`, `Fields/`, `Utils/` z dobrą organizacją
   - Ale część klas o podobnym przeznaczeniu pozostała w głównym katalogu

4. **Trudność w utrzymaniu**
   - Ciężko zrozumieć strukturę projektu dla nowych programistów
   - Ryzyko duplikacji funkcjonalności

### 3.2 Dlaczego klasy są tam gdzie są?

**Historyczne powody:**
- Projekt FreeCRM bazuje na YetiForce, które jest forkiem Vtiger CRM
- Legacy kod był stopniowo migrowany z różnych lokalizacji
- Klasy były przenoszone do `src/` i otrzymywały namespace `App\`
- Nie została przeprowadzona pełna reorganizacja tematyczna

---

## 4. Propozycja reorganizacji

### 4.1 Struktura docelowa

Zamiast płaskiej struktury z 32 plikami, proponuję organizację tematyczną:

```
src/
├── Api/                    (już istnieje)
├── Cache/                  (już istnieje)
├── Config/                 (NOWY - przeniesienie AppConfig.php)
│   └── AppConfig.php
├── Core/                   (NOWY - podstawowe klasy aplikacji)
│   ├── Company.php
│   ├── Currency.php
│   ├── Field.php
│   ├── ModuleHierarchy.php
│   ├── Version.php
│   ├── Loader.php
│   └── EventHandler.php
├── Database/               (rozszerzenie istniejącego)
│   ├── PearDatabase.php   (już istnieje)
│   ├── Db.php             (PRZENIESIENIE)
│   ├── QueryGenerator.php (PRZENIESIENIE)
│   └── CRMEntity.php      (PRZENIESIENIE - legacy model)
├── Db/                     (już istnieje - query builders)
├── Debug/                  (rozszerzenie)
│   └── Debugger.php       (PRZENIESIENIE)
├── Email/                  (NOWY)
│   ├── EmailParser.php    (PRZENIESIENIE)
│   ├── Mail.php           (PRZENIESIENIE)
│   └── Mailer.php         (PRZENIESIENIE)
├── EntryPoint/             (już istnieje)
├── Exceptions/             (już istnieje)
├── Fields/                 (już istnieje)
│   └── CustomFieldUtil.php (PRZENIESIENIE)
├── Http/                   (już istnieje)
├── Layout/                 (już istnieje)
├── Log/                    (rozszerzenie)
│   └── Log.php            (PRZENIESIENIE)
├── ModuleManagement/       (już istnieje)
├── Modules/                (już istnieje)
├── Records/                (NOWY)
│   ├── Record.php         (PRZENIESIENIE)
│   └── RecordSearch.php   (PRZENIESIENIE)
├── Runtime/                (już istnieje)
├── Security/               (NOWY)
│   ├── Encryption.php     (PRZENIESIENIE)
│   ├── Privilege.php      (PRZENIESIENIE)
│   ├── PrivilegeAdvanced.php (PRZENIESIENIE)
│   ├── PrivilegeFile.php  (PRZENIESIENIE)
│   ├── PrivilegeQuery.php (PRZENIESIENIE)
│   ├── PrivilegeUpdater.php (PRZENIESIENIE)
│   ├── PrivilegeUtil.php  (PRZENIESIENIE)
│   └── Purifier.php       (PRZENIESIENIE)
├── SystemWarnings/         (już istnieje)
│   └── SystemWarnings.php (PRZENIESIENIE)
├── TextParser/             (już istnieje)
│   └── TextParser.php     (PRZENIESIENIE)
├── User/                   (już istnieje)
├── Utils/                  (rozszerzenie)
│   └── RequestUtil.php    (PRZENIESIENIE)
├── View/                   (już istnieje)
│   └── CustomView.php     (PRZENIESIENIE)
└── Webservices/            (już istnieje)
```

### 4.2 Mapowanie namespace'ów

Po reorganizacji, namespace'y zmienią się następująco:

| Stara klasa | Nowy namespace | Nowa lokalizacja |
|-------------|----------------|------------------|
| `App\AppConfig` | `App\Config\AppConfig` | `src/Config/AppConfig.php` |
| `App\Db` | `App\Database\Db` | `src/Database/Db.php` |
| `App\QueryGenerator` | `App\Database\QueryGenerator` | `src/Database/QueryGenerator.php` |
| `App\CRMEntity` | `App\Database\CRMEntity` | `src/Database/CRMEntity.php` |
| `App\Privilege` | `App\Security\Privilege` | `src/Security/Privilege.php` |
| `App\Record` | `App\Records\Record` | `src/Records/Record.php` |
| `App\Mail` | `App\Email\Mail` | `src/Email/Mail.php` |
| `App\Log` | `App\Log\Log` | `src/Log/Log.php` |
| ... | ... | ... |

### 4.3 Wpływ na composer.json

Autoloading **NIE MUSI** być zmieniony:

```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

PSR-4 automatycznie obsłuży wszystkie podkatalogi.

---

## 5. Plan migracji

### 5.1 Etap 1: Analiza zależności (1-2 dni)

1. Użyć grep/PHPStan do znalezienia wszystkich miejsc użycia klas
2. Zidentyfikować klasy najbardziej i najmniej używane
3. Sprawdzić czy są klasy z aliasami (`class_alias`)

### 5.2 Etap 2: Utworzenie aliasów (1 dzień)

Aby zachować backward compatibility:

```php
// src/Legacy/Aliases.php
namespace App\Legacy;

// Backwards compatibility aliases
class_alias(\App\Config\AppConfig::class, 'App\AppConfig');
class_alias(\App\Database\Db::class, 'App\Db');
class_alias(\App\Records\Record::class, 'App\Record');
// ... etc
```

**UWAGA:** Zgodnie z regułami FreeCRM: **"Never create aliases"**  
To oznacza, że musimy przeprowadzić pełną migrację bez aliasów!

### 5.3 Etap 3: Migracja stopniowa (2-4 tygodnie)

**Podejście bezpieczne (zalecane):**

1. Migruj po jednej klasie na raz
2. Dla każdej klasy:
   - Przenieś plik do nowego katalogu
   - Zaktualizuj namespace w pliku
   - Użyj sed/grep aby zaktualizować wszystkie użycia w projekcie
   - Przetestuj aplikację (CLI + web)
   - Sprawdź logi `cache/logs/system.log`
3. Commit po każdej klasie lub małej grupie klas

**Kolejność migracji (od najmniej do najbardziej krytycznych):**

1. **Faza 1 - Utility classes (niskie ryzyko):**
   - RequestUtil.php
   - Json.php
   - Version.php

2. **Faza 2 - Email classes:**
   - EmailParser.php
   - Mail.php
   - Mailer.php

3. **Faza 3 - Security classes:**
   - Encryption.php
   - Purifier.php
   - Privilege*.php (6 plików)

4. **Faza 4 - Records:**
   - Record.php
   - RecordSearch.php

5. **Faza 5 - Core classes (wysokie ryzyko):**
   - AppConfig.php
   - Db.php
   - QueryGenerator.php
   - CRMEntity.php

### 5.4 Etap 4: Aktualizacja dokumentacji

1. Zaktualizuj `.cursor/rules/architecture.mdc`
2. Zaktualizuj `.cursor/rules/php-namespaces.mdc`
3. Utwórz migration guide dla developerów

---

## 6. Ryzyko i mitigacja

### 6.1 Ryzyka

| Ryzyko | Prawdopodobieństwo | Wpływ | Mitigacja |
|--------|-------------------|-------|-----------|
| Złamanie istniejącego kodu | Wysokie | Wysokie | Pełne testy po każdej zmianie |
| Problemy z autoloadingiem | Średnie | Wysokie | Sprawdzenie composer dump-autoload |
| Konflikty w git | Średnie | Średnie | Małe, atomowe commity |
| Długi czas migracji | Wysokie | Średnie | Stopniowa migracja, priorytetyzacja |

### 6.2 Rollback plan

Jeśli coś pójdzie nie tak:
1. Git revert do poprzedniego działającego commitu
2. `composer dump-autoload`
3. Wyczyść cache: `rm -rf cache/templates_c/* cache/logs/*`

---

## 7. Rekomendacje

### 7.1 Krótkoterminowe (1-2 tygodnie)

**Nie wykonywać pełnej reorganizacji natychmiast.** 

Zamiast tego:

1. ✅ **Zaakceptować obecną strukturę jako "working"**
   - Obecna struktura jest **technicznie poprawna** (PSR-4)
   - Aplikacja działa
   - Autoloading funkcjonuje

2. ✅ **Nowe klasy tworzyć w odpowiednich podkatalogach**
   - Nie dodawać nowych plików do głównego `src/`
   - Używać struktury tematycznej dla nowych klas

3. ✅ **Zaktualizować architecture.mdc**
   - Udokumentować obecną strukturę jako "legacy layout"
   - Określić zasady dla nowych klas

### 7.2 Średnioterminowe (1-3 miesiące)

**Rozważyć reorganizację jeśli:**
- Zespół ma czas na refactoring
- Jest dobry coverage testami
- Aplikacja jest stabilna
- Mamy automatyczne testy integracyjne

**Rozpocząć od:**
1. Utility classes (najmniejsze ryzyko)
2. Małe, niekrytyczne klasy
3. Stopniowo przechodzić do core classes

### 7.3 Długoterminowe (3-6 miesięcy)

**Cel strategiczny:**
- Pełna reorganizacja do struktury tematycznej
- Usunięcie wszystkich 32 plików z głównego `src/`
- Spójna architektura zgodna z domain-driven design
- Zaktualizowana dokumentacja

---

## 8. Podsumowanie

### Czy klasy powinny być w `src/`?

**Odpowiedź techniczna:** **TAK** ✓
- Zgodnie z PSR-4 autoloading (`"App\\": "src/"`)
- Klasy `App\*` powinny być w `src/*.php`
- Obecna struktura jest poprawna technicznie

**Odpowiedź architekturalna:** **NIE, ale...** ⚠️
- 32 pliki w jednym katalogu to anti-pattern
- Powinny być zorganizowane tematycznie w podkatalogach
- Ale migracja jest ryzykowna i czasochłonna

### Gdzie powinny być umieszczane?

**Idealna struktura:**
```
src/
├── Config/        (AppConfig)
├── Core/          (podstawowe klasy)
├── Database/      (Db, QueryGenerator, CRMEntity)
├── Email/         (Mail, Mailer, EmailParser)
├── Records/       (Record, RecordSearch)
├── Security/      (Privilege*, Encryption, Purifier)
└── ...
```

### Zalecenie końcowe

**Dla nowych feature'ów:**
- ✅ Używaj struktury tematycznej
- ✅ Twórz podkatalogi
- ✅ Nie dodawaj plików do głównego `src/`

**Dla istniejącego kodu:**
- ⚠️ Zachowaj ostrożność
- ⚠️ Migruj stopniowo, jeśli masz czas i testy
- ⚠️ Najpierw zadbaj o testy, potem refactoring

---

**Następne kroki:**
1. Przejrzyj tę analizę z zespołem
2. Zdecyduj czy i kiedy rozpocząć migrację
3. Jeśli tak - zacznij od aktualizacji testów
4. Migruj małymi krokami z pełnym testowaniem


