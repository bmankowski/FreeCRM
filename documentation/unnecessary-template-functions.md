# Niepotrzebne użycia funkcji w szablonach Smarty - FreeCRM

## Wprowadzenie

Ten dokument identyfikuje funkcje używane w szablonach Smarty, które powinny zostać zastąpione natywnymi mechanizmami Smarty lub przeniesione do kontrolera zgodnie z zasadami MVC.

## Kategorie problemów

### 1. `count()` - Zastąpienie natywnym mechanizmem Smarty

**Problem**: Użycie funkcji `{count($ARRAY)}` zamiast natywnego modyfikatora Smarty.

**Przykłady nieprawidłowego użycia:**

```4:4:layouts/basic/modules/Settings/Users/SwitchUsersContent.tpl
<input type="hidden" id="suCount" value="{count($SWITCH_USERS)}" />
```

```12:12:layouts/basic/modules/Settings/Users/LocksContent.tpl
<input type="hidden" id="lcount" value="{count($LOCKS)}" />
```

```20:20:layouts/basic/modules/Base/RelatedActivities.tpl
<input type="hidden" class="countActivities" value="{count($ACTIVITIES)}">
```

**Rozwiązanie**: Użyj natywnego modyfikatora Smarty `@count`:

```smarty
{* Zamiast: {count($SWITCH_USERS)} *}
{$SWITCH_USERS|@count}

{* Lub jeszcze lepiej - przekaż wartość z kontrolera: *}
<input type="hidden" id="suCount" value="{$SWITCH_USERS_COUNT}" />
```

**Dlaczego**: 
- Smarty ma natywny modyfikator `@count` dla tablic
- Liczba elementów powinna być obliczona w kontrolerze i przekazana jako gotowa wartość
- Zwiększa to wydajność (obliczenie raz zamiast przy każdym renderowaniu)

**Lokalizacje do poprawy:**
- `layouts/basic/modules/Settings/Users/SwitchUsersContent.tpl` (linia 4)
- `layouts/basic/modules/Settings/Users/LocksContent.tpl` (linia 12)
- `layouts/basic/modules/Base/RelatedActivities.tpl` (linia 20)
- `layouts/basic/modules/Settings/Picklist/DeleteView.tpl` (linia 37)

---

### 2. `vglobal()` - Przeniesienie do kontrolera

**Problem**: Pobieranie wartości konfiguracyjnych bezpośrednio w szablonie zamiast przekazania z kontrolera.

**Przykłady nieprawidłowego użycia:**

```20:20:layouts/basic/modules/Base/ListViewContents.tpl
<input type="hidden" id="listMaxEntriesMassEdit" value="{vglobal('listMaxEntriesMassEdit')}" />
```

```68:68:layouts/basic/modules/Base/PopupLayout.tpl
<input type="hidden" id="backgroundClosingModal" value="{vglobal('backgroundClosingModal')}" />
```

```26:26:layouts/basic/modules/Users/ListViewContents.tpl
<input type="hidden" id="listMaxEntriesMassEdit" value="{vglobal('listMaxEntriesMassEdit')}" />
```

**Rozwiązanie**: Przekaż wartości z kontrolera:

```php
// W kontrolerze (np. ListView.php)
$viewer->assign('LIST_MAX_ENTRIES_MASS_EDIT', \App\AppConfig::main('listMaxEntriesMassEdit'));
$viewer->assign('BACKGROUND_CLOSING_MODAL', \App\AppConfig::main('backgroundClosingModal'));
```

```smarty
{* W szablonie *}
<input type="hidden" id="listMaxEntriesMassEdit" value="{$LIST_MAX_ENTRIES_MASS_EDIT}" />
<input type="hidden" id="backgroundClosingModal" value="{$BACKGROUND_CLOSING_MODAL}" />
```

**Dlaczego**:
- Zgodność z MVC - logika biznesowa (pobieranie konfiguracji) powinna być w kontrolerze
- Łatwiejsze testowanie - wartości można mockować w kontrolerze
- Lepsza separacja odpowiedzialności
- Wartości są przygotowane raz, nie przy każdym renderowaniu

**Lokalizacje do poprawy:**
- `layouts/basic/modules/Base/ListViewContents.tpl` (linia 20)
- `layouts/basic/modules/Base/PopupLayout.tpl` (linia 68)
- `layouts/basic/modules/Base/MainLayout.tpl` (linia 79)
- `layouts/basic/modules/Base/Header.tpl` (linia 69)
- `layouts/basic/modules/Users/ListViewContents.tpl` (linia 26)
- `layouts/basic/modules/RecycleBin/ListView.tpl` (linia 70)
- `layouts/basic/modules/RecycleBin/ListViewContents.tpl` (linia 25)
- `layouts/basic/modules/Reports/ListView.tpl` (linia 72)
- `layouts/basic/modules/Reports/ListViewContents.tpl` (linia 23)
- `layouts/basic/modules/OSSPasswords/ListViewContents.tpl` (linia 9)

---

### 3. `implode()` - Przygotowanie wartości w kontrolerze

**Problem**: Łączenie tablic w stringi bezpośrednio w szablonie zamiast przygotowania w kontrolerze.

**Przykłady nieprawidłowego użycia:**

```30:30:layouts/basic/modules/Accounts/dashboards/NewAccounts.tpl
<input type="text" name="time" title="{"LBL_CHOOSE_DATE"|t}" class="dateRange widgetFilter width90 form-control" value="{implode(',',$DTIME)}"/>
```

```29:29:layouts/basic/modules/Base/DatabaseException.tpl
<pre>{implode(',', $MESSAGE['params'])}</pre>
```

```56:56:layouts/basic/modules/Base/dashboards/Calendar.tpl
<input type="hidden" value="current" data-current="{implode(',',$CURRENT_STATUS)}" data-history="{implode(',',$HISTORY_STATUS)}" class="widgetFilterSwitch">
```

**Rozwiązanie**: Przygotuj wartości w kontrolerze:

```php
// W kontrolerze
$viewer->assign('DTIME_STRING', implode(',', $dtime));
$viewer->assign('CURRENT_STATUS_STRING', implode(',', $currentStatus));
$viewer->assign('HISTORY_STATUS_STRING', implode(',', $historyStatus));
```

```smarty
{* W szablonie *}
<input type="text" name="time" value="{$DTIME_STRING}"/>
<input type="hidden" value="current" data-current="{$CURRENT_STATUS_STRING}" data-history="{$HISTORY_STATUS_STRING}" />
```

**Dlaczego**:
- Logika formatowania danych powinna być w kontrolerze
- Szablon powinien tylko wyświetlać gotowe wartości
- Łatwiejsze testowanie i debugowanie
- Możliwość ponownego użycia wartości w innych miejscach

**Lokalizacje do poprawy:**
- Wszystkie pliki dashboardów używające `{implode(',',$DTIME)}` (~14 plików)
- `layouts/basic/modules/Base/DatabaseException.tpl` (linia 29)
- `layouts/basic/modules/Base/dashboards/Calendar.tpl` (linia 56)

---

### 4. `intval()` - Konwersja w kontrolerze

**Problem**: Konwersja wartości na integer bezpośrednio w szablonie.

**Przykłady nieprawidłowego użycia:**

```45:45:layouts/basic/modules/Settings/PDF/Step8.tpl
<input type="number" name="watermark_size" class="form-control" value="{intval($PDF_MODEL->get('watermark_size'))}" id="watermark_size" min="0" max="99" />
```

```53:53:layouts/basic/modules/Settings/PDF/Step8.tpl
<input type="number" name="watermark_angle" class="form-control" value="{intval($PDF_MODEL->get('watermark_angle'))}" id="watermark_angle" min="0" max="360" />
```

**Rozwiązanie**: Konwertuj wartości w kontrolerze lub modelu:

```php
// W kontrolerze lub modelu
$watermarkSize = (int) $pdfModel->get('watermark_size');
$watermarkAngle = (int) $pdfModel->get('watermark_angle');
$viewer->assign('WATERMARK_SIZE', $watermarkSize);
$viewer->assign('WATERMARK_ANGLE', $watermarkAngle);
```

```smarty
{* W szablonie *}
<input type="number" name="watermark_size" value="{$WATERMARK_SIZE}" />
<input type="number" name="watermark_angle" value="{$WATERMARK_ANGLE}" />
```

**Dlaczego**:
- Walidacja i konwersja typów powinna być w warstwie logiki biznesowej
- Szablon powinien otrzymywać już przygotowane wartości
- Łatwiejsze testowanie poprawności danych

**Lokalizacje do poprawy:**
- `layouts/basic/modules/Settings/PDF/Step8.tpl` (linie 45, 53)

---

### 5. `in_array()` - Sprawdzanie w kontrolerze

**Problem**: Sprawdzanie członkostwa w tablicach bezpośrednio w szablonie.

**Przykłady nieprawidłowego użycia:**

```smarty
{if in_array($OWNER_ID, $USERS)} selected {/if}
{if in_array($ITEM, $DUPLICATE_STATUS)} disabled {/if}
```

**Rozwiązanie**: Przygotuj wartości boolean w kontrolerze:

```php
// W kontrolerze
$isOwnerInUsers = in_array($ownerId, $users);
$isItemDuplicate = in_array($item, $duplicateStatus);
$viewer->assign('IS_OWNER_IN_USERS', $isOwnerInUsers);
$viewer->assign('IS_ITEM_DUPLICATE', $isItemDuplicate);
```

```smarty
{* W szablonie *}
{if $IS_OWNER_IN_USERS} selected {/if}
{if $IS_ITEM_DUPLICATE} disabled {/if}
```

**Dlaczego**:
- Logika warunkowa powinna być w kontrolerze
- Szablon powinien tylko sprawdzać gotowe wartości boolean
- Łatwiejsze testowanie i debugowanie
- Lepsza separacja odpowiedzialności

**Uwaga**: W dokumentacji `template-functions-analysis.md` wspomniano o ~15 wystąpieniach `in_array()`, ale w rzeczywistości nie znaleziono ich w szablonach podczas przeszukiwania. Możliwe, że zostały już usunięte lub są używane w innych kontekstach.

---

### 6. `explode()` - Parsowanie w kontrolerze

**Problem**: Dzielenie stringów na tablice bezpośrednio w szablonie.

**Przykłady nieprawidłowego użycia:**

```smarty
{assign var="DATE_PARTS" value={explode(' ', $DATE_TIME_VALUE_NOW)}}
{assign var="STATUS_ARRAY" value={explode(',', $DUPLICATE_STATUS)}}
```

**Rozwiązanie**: Parsuj wartości w kontrolerze:

```php
// W kontrolerze
$dateParts = explode(' ', $dateTimeValueNow);
$statusArray = explode(',', $duplicateStatus);
$viewer->assign('DATE_PARTS', $dateParts);
$viewer->assign('STATUS_ARRAY', $statusArray);
```

**Dlaczego**:
- Parsowanie danych to logika biznesowa
- Szablon powinien otrzymywać już przygotowane struktury danych
- Łatwiejsze testowanie i walidacja danych

---

### 7. `AppConfig::*()` - Przeniesienie do kontrolera

**Problem**: Pobieranie wartości konfiguracyjnych bezpośrednio w szablonie za pomocą statycznych metod `AppConfig`.

**Przykłady nieprawidłowego użycia:**

```16:17:layouts/basic/modules/Base/uitypes/SharedOwnerFieldSearchView.tpl
data-minimum-input="{AppConfig::performance('OWNER_MINIMUM_INPUT_LENGTH')}" {/if}>
{if AppConfig::performance('SEARCH_OWNERS_BY_AJAX')}
```

```69:70:layouts/basic/modules/Base/PopupLayout.tpl
<input type="hidden" id="backgroundClosingModal" value="{vglobal('backgroundClosingModal')}" />
<input type="hidden" id="gsAutocomplete" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE')}" />
<input type="hidden" id="gsMinLength" value="{AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH')}" />
```

```27:27:layouts/basic/modules/Users/ListViewContents.tpl
<input type="hidden" id="autoRefreshListOnChange" value="{AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE')}" />
```

**Rozwiązanie**: Przekaż wartości konfiguracyjne z kontrolera:

```php
// W kontrolerze (np. w BaseViewController::preProcess() lub konkretnym kontrolerze)
$viewer->assign('SEARCH_OWNERS_BY_AJAX', \App\AppConfig::performance('SEARCH_OWNERS_BY_AJAX'));
$viewer->assign('OWNER_MINIMUM_INPUT_LENGTH', \App\AppConfig::performance('OWNER_MINIMUM_INPUT_LENGTH'));
$viewer->assign('GLOBAL_SEARCH_AUTOCOMPLETE', \App\AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE'));
$viewer->assign('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH', \App\AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH'));
$viewer->assign('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE', \App\AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE'));
```

```smarty
{* W szablonie *}
{if $SEARCH_OWNERS_BY_AJAX}
    data-ajax-search="1" 
    data-minimum-input="{$OWNER_MINIMUM_INPUT_LENGTH}"
{/if}
<input type="hidden" id="gsAutocomplete" value="{$GLOBAL_SEARCH_AUTOCOMPLETE}" />
<input type="hidden" id="gsMinLength" value="{$GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH}" />
```

**Dlaczego**:
- Zgodność z MVC - dostęp do konfiguracji to logika biznesowa
- Wartości konfiguracyjne są przygotowane raz, nie przy każdym renderowaniu
- Łatwiejsze testowanie - wartości można mockować
- Lepsza separacja odpowiedzialności
- Możliwość cachowania wartości konfiguracyjnych w kontrolerze

**Uwaga**: `AppConfig` jest używany w ~35 miejscach w 22 plikach. Warto rozważyć przekazywanie najczęściej używanych wartości konfiguracyjnych w `BaseViewController::preProcess()`.

**Lokalizacje do poprawy:**
- Wszystkie pliki używające `AppConfig::performance()`, `AppConfig::search()`, `AppConfig::module()`, `AppConfig::main()`, `AppConfig::security()` (~22 pliki)

---

### 8. `is_array()` - Sprawdzanie typu w kontrolerze

**Problem**: Sprawdzanie typu zmiennej bezpośrednio w szablonie.

**Przykłady nieprawidłowego użycia:**

```smarty
{if $PARAMS[$ITEM]|is_array && in_array($ITEMS.id,$PARAMS[$ITEM])}
```

**Rozwiązanie**: Przygotuj walidację w kontrolerze:

```php
// W kontrolerze
$isParamsItemArray = is_array($params[$item]);
$isItemInParams = $isParamsItemArray && in_array($items['id'], $params[$item]);
$viewer->assign('IS_ITEM_IN_PARAMS', $isItemInParams);
```

```smarty
{* W szablonie *}
{if $IS_ITEM_IN_PARAMS}
```

**Dlaczego**:
- Walidacja typów to logika biznesowa
- Szablon powinien otrzymywać już zwalidowane dane
- Lepsza separacja odpowiedzialności

---

## Podsumowanie

### Funkcje do usunięcia/przeniesienia:

1. **`count()`** → Użyj `{$array|@count}` lub przekaż wartość z kontrolera
2. **`vglobal()`** → Przekaż wartości konfiguracyjne z kontrolera
3. **`AppConfig::*()`** → Przekaż wartości konfiguracyjne z kontrolera
4. **`implode()`** → Przygotuj stringi w kontrolerze
5. **`intval()`** → Konwertuj wartości w kontrolerze
6. **`in_array()`** → Sprawdzaj członkostwo w kontrolerze, przekaż boolean
7. **`explode()`** → Parsuj wartości w kontrolerze
8. **`is_array()`** → Waliduj typy w kontrolerze

### Zasady ogólne:

1. **Szablon powinien tylko wyświetlać** - nie powinien wykonywać obliczeń, konwersji ani walidacji
2. **Kontroler przygotowuje dane** - wszystkie wartości powinny być gotowe przed przekazaniem do szablonu
3. **Używaj natywnych mechanizmów Smarty** - gdy operacja jest konieczna w szablonie, preferuj natywne modyfikatory Smarty
4. **Separacja odpowiedzialności** - logika biznesowa w kontrolerze, prezentacja w szablonie

### Korzyści z refaktoryzacji:

- ✅ Lepsza zgodność z MVC
- ✅ Łatwiejsze testowanie
- ✅ Lepsza wydajność (obliczenia raz zamiast przy każdym renderowaniu)
- ✅ Czytelniejszy kod
- ✅ Łatwiejsze utrzymanie
- ✅ Lepsza separacja odpowiedzialności

## Plan migracji

Szczegółowy plan refaktoryzacji dla `vglobal()` znajduje się w: [`vglobal-refactoring-plan.md`](vglobal-refactoring-plan.md)

## Uwagi

- Niektóre funkcje mogą być nadal potrzebne w specyficznych przypadkach (np. debugowanie)
- Przed usunięciem funkcji upewnij się, że wszystkie wystąpienia zostały zrefaktoryzowane
- Zachowaj kompatybilność wsteczną podczas migracji (można pozostawić funkcje jako deprecated)

