# FreeCRM Universal PSR-4 Migration Script v2.0

## Przegląd

Uniwersalny skrypt migracji PSR-4, który łączy funkcjonalności podstawowego i zaawansowanego skryptu w jednym narzędziu. Obsługuje zarówno proste migracje klas, jak i zaawansowane operacje z analizą zależności, aliasami klas i aktualizacją composer.json.

## Funkcje

### Tryb Podstawowy (Basic Mode) - domyślny
- Skanowanie legacy klas
- Migracja pojedynczych plików/katalogów
- Migracja modułów
- Migracja klas core
- Backup i przywracanie
- Dry-run mode
- Status migracji

### Tryb Zaawansowany (Advanced Mode)
- Wszystkie funkcje trybu podstawowego
- Analiza zależności klas
- Tworzenie aliasów dla kompatybilności wstecznej
- Aktualizacja composer.json
- Walidacja migracji
- Pełna migracja wszystkich klas
- Ładowanie konfiguracji z pliku

## Instalacja

```bash
# Skopiuj skrypt do katalogu projektu
cp psr4-migrator-universal.sh /path/to/FreeCRM/

# Nadaj uprawnienia wykonywania
chmod +x psr4-migrator-universal.sh
```

## Użycie

### Podstawowa składnia
```bash
./psr4-migrator-universal.sh [OPTIONS] COMMAND [ARGS]
```

### Tryby pracy
```bash
# Tryb podstawowy (domyślny)
./psr4-migrator-universal.sh COMMAND

# Tryb zaawansowany
./psr4-migrator-universal.sh --advanced COMMAND
```

## Komendy

### Podstawowe komendy (wszystkie tryby)
- `scan` - Skanowanie legacy klas
- `migrate [PATH]` - Migracja pliku/katalogu
- `migrate-module [MODULE]` - Migracja modułu
- `migrate-core` - Migracja klas core
- `dry-run [PATH]` - Podgląd migracji
- `backup` - Tworzenie backup
- `restore [BACKUP_ID]` - Przywracanie z backup
- `status` - Status migracji
- `help` - Pomoc

### Zaawansowane komendy (tylko --advanced)
- `migrate-all` - Migracja wszystkich klas
- `analyze-dependencies` - Analiza zależności
- `create-aliases` - Tworzenie aliasów klas
- `update-composer` - Aktualizacja composer.json
- `validate` - Walidacja migracji

## Opcje

- `--basic` - Tryb podstawowy (domyślny)
- `--advanced` - Tryb zaawansowany
- `-v, --verbose` - Szczegółowe wyjście
- `-d, --dry-run` - Podgląd zmian bez aplikowania
- `-h, --help` - Pomoc

## Przykłady użycia

### Tryb podstawowy
```bash
# Skanowanie legacy klas
./psr4-migrator-universal.sh scan

# Migracja konkretnego pliku
./psr4-migrator-universal.sh migrate modules/Users/Users.php

# Migracja całego modułu
./psr4-migrator-universal.sh migrate-module Users

# Migracja klas core
./psr4-migrator-universal.sh migrate-core

# Podgląd migracji
./psr4-migrator-universal.sh dry-run include/main/WebUI.php

# Tworzenie backup
./psr4-migrator-universal.sh backup

# Sprawdzenie statusu
./psr4-migrator-universal.sh status
```

### Tryb zaawansowany
```bash
# Skanowanie z analizą zależności
./psr4-migrator-universal.sh --advanced scan

# Tworzenie aliasów klas
./psr4-migrator-universal.sh --advanced create-aliases

# Aktualizacja composer.json
./psr4-migrator-universal.sh --advanced update-composer

# Walidacja migracji
./psr4-migrator-universal.sh --advanced validate

# Pełna migracja (OSTROŻNIE!)
./psr4-migrator-universal.sh --advanced migrate-all

# Szczegółowy podgląd
./psr4-migrator-universal.sh --advanced --verbose dry-run file.php
```

## Mapowanie namespace

### Tryb podstawowy
Skrypt używa wbudowanego mapowania namespace dla typowych klas Vtiger.

### Tryb zaawansowany
Skrypt ładuje mapowanie z pliku `psr4-migrator.config`:

```ini
[NAMESPACE_MAPPINGS]
Vtiger_ = "Vtiger\Core"
Users = "Vtiger\Modules\Users"
Settings_ = "Vtiger\Modules\Settings"
# ... więcej mapowań
```

## Bezpieczeństwo

### Backup
- Automatyczne tworzenie backup przed migracją
- Backup z metadanymi (tryb zaawansowany)
- Możliwość przywracania z backup

### Walidacja
- Sprawdzanie składni PHP po migracji
- Walidacja aliasów klas
- Sprawdzanie spójności

### Dry-run
- Podgląd zmian bez aplikowania
- Sprawdzanie namespace i nazw klas
- Weryfikacja mapowań

## Struktura plików

```
FreeCRM/
├── psr4-migrator-universal.sh    # Uniwersalny skrypt
├── psr4-migrator.config          # Konfiguracja (opcjonalna)
├── migration_backups/            # Backupy migracji
├── migration.log                 # Log migracji
├── .migration_status             # Status migracji
└── include/
    └── LegacyAliases.php         # Aliasy klas (tryb zaawansowany)
```

## Logi

Wszystkie operacje są logowane do `migration.log`:
- Timestamp operacji
- Szczegóły migracji
- Błędy i ostrzeżenia
- Status operacji

## Przykład pełnej migracji

```bash
# 1. Backup przed migracją
./psr4-migrator-universal.sh --advanced backup

# 2. Skanowanie i analiza
./psr4-migrator-universal.sh --advanced scan

# 3. Migracja klas core
./psr4-migrator-universal.sh --advanced migrate-core

# 4. Migracja modułów (po jednym)
./psr4-migrator-universal.sh --advanced migrate-module Users

# 5. Tworzenie aliasów
./psr4-migrator-universal.sh --advanced create-aliases

# 6. Aktualizacja composer.json
./psr4-migrator-universal.sh --advanced update-composer

# 7. Walidacja
./psr4-migrator-universal.sh --advanced validate

# 8. Sprawdzenie statusu
./psr4-migrator-universal.sh --advanced status
```

## Rozwiązywanie problemów

### Błąd składni namespace
```bash
# Sprawdź mapowanie namespace w konfiguracji
./psr4-migrator-universal.sh --advanced --verbose dry-run file.php
```

### Problemy z aliasami
```bash
# Sprawdź składnię aliasów
php -l include/LegacyAliases.php
```

### Problemy z composer.json
```bash
# Przywróć backup composer.json
cp composer.json.backup composer.json
```

## Wsparcie

- Sprawdź log migracji: `cat migration.log`
- Użyj trybu verbose: `--verbose`
- Sprawdź status: `status`
- Przywróć z backup: `restore BACKUP_ID`

## Porównanie z poprzednimi skryptami

| Funkcja | Basic Script | Advanced Script | Universal Script |
|---------|--------------|-----------------|------------------|
| Podstawowa migracja | ✅ | ✅ | ✅ |
| Analiza zależności | ❌ | ✅ | ✅ (--advanced) |
| Aliasy klas | ❌ | ✅ | ✅ (--advanced) |
| Aktualizacja composer | ❌ | ✅ | ✅ (--advanced) |
| Konfiguracja z pliku | ❌ | ✅ | ✅ (--advanced) |
| Jeden skrypt | ❌ | ❌ | ✅ |
| Spójny interfejs | ❌ | ❌ | ✅ |

## Migracja z poprzednich skryptów

### Zastąpienie podstawowego skryptu
```bash
# Stara komenda
./psr4-migrator.sh migrate file.php

# Nowa komenda
./psr4-migrator-universal.sh migrate file.php
```

### Zastąpienie zaawansowanego skryptu
```bash
# Stara komenda
./psr4-migrator-advanced.sh create-aliases

# Nowa komenda
./psr4-migrator-universal.sh --advanced create-aliases
```

## Wersja

**v2.0** - Uniwersalny skrypt łączący funkcjonalności obu poprzednich skryptów

## Autor

AI Assistant - Zoptymalizowany dla FreeCRM/YetiForce
