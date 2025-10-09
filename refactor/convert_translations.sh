#!/bin/bash

# Skrypt do konwersji konstrukcji {\App\Language::translate('KEY', $MODULE)} na {'KEY'|t:$MODULE}
# Autor: Assistant
# Data: $(date)

# Kolorowe wyjście
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funkcja pomocnicza do wyświetlania komunikatów
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Sprawdź czy podano katalog
if [ $# -eq 0 ]; then
    echo "Użycie: $0 <katalog> [--dry-run]"
    echo ""
    echo "Przykłady:"
    echo "  $0 layouts/                    # Konwertuj wszystkie pliki .tpl w katalogu layouts/"
    echo "  $0 layouts/ --dry-run          # Pokaż co zostanie zmienione bez wprowadzania zmian"
    echo "  $0 .                           # Konwertuj wszystkie pliki .tpl w bieżącym katalogu i podkatalogach"
    exit 1
fi

TARGET_DIR="$1"
DRY_RUN=false

# Parsuj argumenty
for arg in "$@"; do
    case $arg in
        --dry-run)
            DRY_RUN=true
            ;;
    esac
done

# Sprawdź czy katalog istnieje
if [ ! -d "$TARGET_DIR" ]; then
    log_error "Katalog '$TARGET_DIR' nie istnieje!"
    exit 1
fi

log_info "Rozpoczynam konwersję w katalogu: $TARGET_DIR"
if [ "$DRY_RUN" = true ]; then
    log_warning "TRYB DRY-RUN: Żadne zmiany nie zostaną wprowadzone"
fi

# Znajdź wszystkie pliki .tpl
TPL_FILES=$(find "$TARGET_DIR" -name "*.tpl" -type f)

if [ -z "$TPL_FILES" ]; then
    log_warning "Nie znaleziono plików .tpl w katalogu '$TARGET_DIR'"
    exit 0
fi

TOTAL_FILES=$(echo "$TPL_FILES" | wc -l)
log_info "Znaleziono $TOTAL_FILES plików .tpl do przetworzenia"

CONVERTED_FILES=0
TOTAL_CHANGES=0

# Przetwórz każdy plik
while IFS= read -r file; do
    if [ ! -f "$file" ]; then
        continue
    fi
    
    log_info "Przetwarzam: $file"
    
    # Sprawdź czy plik zawiera konstrukcję do konwersji
    if ! grep -q "{\\\\App\\\\Language::translate(" "$file" && ! grep -q "{App\\\\Language::translate(" "$file" && ! grep -q "\\\\App\\\\Language::translate(" "$file"; then
        continue
    fi
    
    
    # Wykonaj konwersję używając sed
    # Wzorzec: {\App\Language::translate('KEY', $MODULE)} -> {'KEY'|t:$MODULE}
    if [ "$DRY_RUN" = true ]; then
        # Pokaż co zostanie zmienione
        echo "Zmiany w pliku $file:"
        # Obsługa różnych wariantów:
        # 1. {\App\Language::translate('KEY', $MODULE)}
        # 2. {\App\Language::translate('KEY',$MODULE)} (bez spacji)
        # 3. {\App\Language::translate($var, $MODULE)} (ze zmienną)
        # 4. {\App\Language::translate('KEY')} (bez modułu - domyślnie Vtiger)
        # 5. {\App\Language::translate($var)} (zmienna bez modułu)
        # 6. {App\Language::translate('KEY', 'MODULE')} (bez backslash, moduł w cudzysłowach)
        sed -n "s/{\\\\App\\\\Language::translate('\([^']*\)',\s*\$\([^}]*\))}/{'\1'|t:\$\2}/gp" "$file"
        sed -n "s/{\\\\App\\\\Language::translate(\$\([^,]*\),\s*\$\([^}]*\))}/{\$\1|t:\$\2}/gp" "$file"
        sed -n "s/{\\\\App\\\\Language::translate('\([^']*\)')}/{\'\1\'|t}/gp" "$file"
        sed -n "s/{\\\\App\\\\Language::translate(\$\([^)]*\))}/{\$\1|t}/gp" "$file"
        sed -n "s/{App\\\\Language::translate('\([^']*\)',\s*'\([^']*\)')}/{\'\1\'|t:\'\2\'}/gp" "$file"
        sed -n "s/{App\\\\Language::translate(\$\([^)]*\))}/{\$\1|t}/gp" "$file"
        sed -n "s/{App\\\\Language::translate('\([^']*\)',\s*\$\([^)]*\))}/{\'\1\'|t:\$\2}/gp" "$file"
        sed -n "s/{App\\\\Language::translate('\([^']*\)')}/{\'\1\'|t}/gp" "$file"
        sed -n "s/{App\\\\Language::translate(\$\([^,]*\),\s*\$\([^)]*\))}/{\$\1|t:\$\2}/gp" "$file"
        sed -n "s/{App\\\\Language::translate(\$\([^,]*\),\s*'\([^']*\)')}/{\$\1|t:\'\2\'}/gp" "$file"
        sed -n "s/\\\\App\\\\Language::translate(\$\([^,]*\),\s*\$\([^)]*\))/{\$\1|t:\$\2}/gp" "$file"
        echo ""
    else
        # Wykonaj rzeczywistą konwersję
        # Używamy tymczasowego pliku dla bezpieczeństwa
        temp_file=$(mktemp)
        
        # Konwersja główna - różne warianty
        # 1. {\App\Language::translate('KEY', $MODULE)}
        sed "s/{\\\\App\\\\Language::translate('\([^']*\)',\s*\$\([^}]*\))}/{'\1'|t:\$\2}/g" "$file" > "$temp_file"
        # 2. {\App\Language::translate($var, $MODULE)} (ze zmienną)
        sed -i "s/{\\\\App\\\\Language::translate(\$\([^,]*\),\s*\$\([^}]*\))}/{\$\1|t:\$\2}/g" "$temp_file"
        # 3. {\App\Language::translate('KEY')} (bez modułu - domyślnie Vtiger)
        sed -i "s/{\\\\App\\\\Language::translate('\([^']*\)')}/{\'\1\'|t}/g" "$temp_file"
        # 4. {\App\Language::translate($var)} (zmienna bez modułu)
        sed -i "s/{\\\\App\\\\Language::translate(\$\([^)]*\))}/{\$\1|t}/g" "$temp_file"
        # 5. {App\Language::translate('KEY', 'MODULE')} (bez backslash, moduł w cudzysłowach)
        # 6. {App\Language::translate($var)} (bez backslash, zmienna bez modułu)
        # 7. {App\Language::translate('KEY', $MODULE)} (bez backslash, ze zmienną modułu)
        # 8. {App\Language::translate('KEY')} (bez backslash, bez modułu)
        # 9. {App\Language::translate($var, $MODULE)} (bez backslash, ze zmienną jako pierwszym parametrem)
        # 10. \App\Language::translate($var, $MODULE) (w assign, bez nawiasów klamrowych)
        sed -i "s/{App\\\\Language::translate('\([^']*\)',\s*'\([^']*\)')}/{\'\1\'|t:\'\2\'}/g" "$temp_file"
        sed -i "s/{App\\\\Language::translate(\$\([^)]*\))}/{\$\1|t}/g" "$temp_file"
        sed -i "s/{App\\\\Language::translate('\([^']*\)',\s*\$\([^)]*\))}/{\'\1\'|t:\$\2}/g" "$temp_file"
        sed -i "s/{App\\\\Language::translate('\([^']*\)')}/{\'\1\'|t}/g" "$temp_file"
        sed -i "s/{App\\\\Language::translate(\$\([^,]*\),\s*\$\([^)]*\))}/{\$\1|t:\$\2}/g" "$temp_file"
        sed -i "s/{App\\\\Language::translate(\$\([^,]*\),\s*'\([^']*\)')}/{\$\1|t:\'\2\'}/g" "$temp_file"
        sed -i "s/\\\\App\\\\Language::translate(\$\([^,]*\),\s*\$\([^)]*\))/{\$\1|t:\$\2}/g" "$temp_file"
        
        # Sprawdź czy były jakieś zmiany
        if ! cmp -s "$file" "$temp_file"; then
            mv "$temp_file" "$file"
            changes=$(grep -c "{\\\\App\\\\Language::translate(\|{App\\\\Language::translate(\|\\\\App\\\\Language::translate(" "$file" 2>/dev/null | head -1)
            if [ "$changes" -eq 0 ]; then
                log_success "Skonwertowano plik: $file"
                ((CONVERTED_FILES++))
                # Policz ile zmian zostało wprowadzonych
                file_changes=$(grep -o "{'.*'|t:\$.*}" "$file" | wc -l)
                ((TOTAL_CHANGES += file_changes))
            else
                log_warning "Nie wszystkie konstrukcje zostały skonwertowane w: $file"
            fi
        else
            rm "$temp_file"
        fi
    fi
    
done <<< "$TPL_FILES"

# Podsumowanie
echo ""
log_info "=== PODSUMOWANIE ==="
if [ "$DRY_RUN" = true ]; then
    log_info "Tryb dry-run zakończony. Żadne zmiany nie zostały wprowadzone."
else
    log_success "Skonwertowano $CONVERTED_FILES plików"
    log_success "Wprowadzono $TOTAL_CHANGES zmian"
fi

log_info "Konwersja zakończona!"
