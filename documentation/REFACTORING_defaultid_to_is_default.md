# Plan refaktoringu: zmiana `defaultid` na `is_default` w tabeli `vtiger_currency_info`

## Cel
Zmiana mylącego pola `defaultid` (magiczna liczba `-11`) na czytelne `is_default` (boolean) w tabeli walut.

## Uwaga
**NIE zmieniać** `defaultid` w tabeli `vtiger_calendar_user_activitytypes` - to jest inne pole (foreign key do `vtiger_calendar_default_activitytypes.id`).

---

## KROK 1: Migracja bazy danych

### 1.1 Stworzyć plik migracji
**Plik**: `src/Modules/Install/migrate_schema/YYYYMMDDHHMMSS_change_defaultid_to_is_default.php`

```php
<?php
// Migracja: zmiana defaultid na is_default w vtiger_currency_info

class Migration_ChangeDefaultIdToIsDefault extends \App\Modules\Install\Models\Migration
{
    public function up()
    {
        $db = \App\Db::getInstance();
        
        // 1. Dodać nową kolumnę is_default
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER currency_status
        ")->execute();
        
        // 2. Przenieść dane: defaultid = -11 -> is_default = 1
        $db->createCommand("
            UPDATE vtiger_currency_info 
            SET is_default = 1 
            WHERE defaultid = -11
        ")->execute();
        
        // 3. Upewnić się że wszystkie pozostałe mają is_default = 0
        $db->createCommand("
            UPDATE vtiger_currency_info 
            SET is_default = 0 
            WHERE defaultid != -11 OR defaultid IS NULL
        ")->execute();
        
        // 4. Dodać indeks dla szybkiego wyszukiwania
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            ADD INDEX idx_is_default (is_default)
        ")->execute();
        
        // 5. Usunąć starą kolumnę defaultid
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            DROP COLUMN defaultid
        ")->execute();
    }
    
    public function down()
    {
        // Rollback: przywrócić defaultid
        $db = \App\Db::getInstance();
        
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            ADD COLUMN defaultid TINYINT(6) NOT NULL DEFAULT 0 AFTER currency_status
        ")->execute();
        
        $db->createCommand("
            UPDATE vtiger_currency_info 
            SET defaultid = -11 
            WHERE is_default = 1
        ")->execute();
        
        $db->createCommand("
            UPDATE vtiger_currency_info 
            SET defaultid = 0 
            WHERE is_default = 0
        ")->execute();
        
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            DROP COLUMN is_default
        ")->execute();
    }
}
```

---

## KROK 2: Zmiany w kodzie PHP

### 2.1 Pliki do zmiany (tylko dla `vtiger_currency_info`):

#### A. `src/ModuleManagement/Adapters/Functions.php`
- **Linia 935**: `if ($currency['defaultid'] === '-11'` → `if ($currency['is_default'] == 1`
- **Funkcja**: `getDefaultCurrencyInfo()`

#### B. `src/Modules/Settings/CurrencyUpdate/Models/Module.php`
- **Linia 63**: `->andWhere(['!=', 'defaultid', -11])` → `->andWhere(['=', 'is_default', 0])`
- **Funkcja**: `getSupportedCurrencies()`

#### C. `src/Modules/Base/Helpers/Util.php`
- **Linia 234**: `->where(['<', 'defaultid', '0'])` → `->where(['=', 'is_default', 1])`
- **Funkcja**: `getBaseCurrency()`

#### D. `src/Modules/Settings/Currency/Models/Record.php`
- **Linia 33**: `return ($this->get('defaultid') != '-11')` → `return ($this->get('is_default') == 1)`
- **Linia 91**: `'defaultid' => 0` → `'is_default' => 0`
- **Funkcje**: `isBaseCurrency()`, `save()`

#### E. `src/Fields/CurrencyField.php`
- **Linia 443**: `->where(['<', 'defaultid', 0])` → `->where(['=', 'is_default', 1])`
- **Funkcja**: `getDBCurrencyId()`

#### F. `src/Utils/InventoryUtils.php`
- **Linia 38**: `->orderBy(['defaultid' => SORT_ASC])` → `->orderBy(['is_default' => SORT_DESC])`
- **Funkcja**: `getUserCurrencyId()`

---

## KROK 3: Zmiany w szablonach Smarty

#### A. `layouts/basic/modules/Base/inventoryfields/EditViewCurrency.tpl`
- **Linia 30**: `{if $CURRENCY.defaultid < 0}1{else}0{/if}` → `{if $CURRENCY.is_default}1{else}0{/if}`

---

## KROK 4: Zmiany w plikach instalacyjnych

### 4.1 `src/Modules/Install/install_schema/scheme.sql`
- **Linia 4360**: Zmienić definicję kolumny:
  ```sql
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  ```
  Usunąć: `defaultid` tinyint(6) NOT NULL DEFAULT '0'

### 4.2 `src/Modules/Install/install_schema/data.sql`
- **Linia 1843**: Zmienić INSERT:
  ```sql
  insert into `vtiger_currency_info`(`id`,`currency_name`,`currency_code`,`currency_symbol`,`conversion_rate`,`currency_status`,`is_default`,`deleted`) 
  values (1,'Poland, Zlotych','PLN','zł',1.00000,'Active',1,0);
  ```

### 4.3 `src/Modules/Install/install_schema/Base2.php`
- **Linia 1151**: Zmienić definicję:
  ```php
  'is_default' => 'tinyint(1) NOT NULL DEFAULT \'0\'',
  ```
- **Linia 3247**: Zmienić listę kolumn:
  ```php
  'columns' => ['id', 'currency_name', 'currency_code', 'currency_symbol', 'conversion_rate', 'currency_status', 'is_default', 'deleted'],
  ```
- **Linia 3249**: Zmienić wartości:
  ```php
  [1, 'Poland, Zlotych', 'PLN', 'zł', '1.00000', 'Active', 1, 0],
  ```

---

## KROK 5: Aktualizacja metody `updateBaseCurrency()`

### 5.1 `src/Modules/Users/Models/Module.php`
- **Funkcja**: `updateBaseCurrency()`
- **Zmiana**: Dodać logikę ustawiania `is_default`:
  ```php
  // Ustawić wszystkie na 0
  $db->pquery('UPDATE vtiger_currency_info SET is_default = 0', []);
  
  // Ustawić walutę domyślną na 1
  $query = 'UPDATE vtiger_currency_info SET currency_name = ?, currency_code = ?, currency_symbol = ?, is_default = 1 WHERE id = ?';
  ```

---

## KROK 6: Testy

### 6.1 Testy funkcjonalne:
- [ ] `getDefaultCurrencyInfo()` zwraca walutę z `is_default = 1`
- [ ] `isBaseCurrency()` działa poprawnie
- [ ] `getBaseCurrency()` zwraca walutę domyślną
- [ ] Sortowanie po `is_default DESC` działa
- [ ] Filtrowanie `is_default = 0` działa
- [ ] Instalacja z nowymi plikami działa
- [ ] Migracja istniejącej bazy działa

### 6.2 Testy regresji:
- [ ] Konwersja walut działa
- [ ] Wyświetlanie walut w inventory działa
- [ ] Edycja walut działa
- [ ] Waluta domyślna nie może być usunięta

---

---

## Podsumowanie zmian

### Pliki do modyfikacji (łącznie ~15 plików):

**PHP (6 plików):**
1. `src/ModuleManagement/Adapters/Functions.php`
2. `src/Modules/Settings/CurrencyUpdate/Models/Module.php`
3. `src/Modules/Base/Helpers/Util.php`
4. `src/Modules/Settings/Currency/Models/Record.php`
5. `src/Fields/CurrencyField.php`
6. `src/Utils/InventoryUtils.php`
7. `src/Modules/Users/Models/Module.php`

**Smarty (1 plik):**
1. `layouts/basic/modules/Base/inventoryfields/EditViewCurrency.tpl`

**Instalacja (3 pliki):**
1. `src/Modules/Install/install_schema/scheme.sql`
2. `src/Modules/Install/install_schema/data.sql`
3. `src/Modules/Install/install_schema/Base2.php`

**Migracja (1 nowy plik):**
1. `src/Modules/Install/migrate_schema/YYYYMMDDHHMMSS_change_defaultid_to_is_default.php`

---

## Uwagi

1. **NIE ZMIENIAĆ** `defaultid` w `vtiger_calendar_user_activitytypes` - to jest inne pole!
2. Zachować kompatybilność wsteczną przez jakiś czas (można zostawić obie kolumny)
3. Po wdrożeniu sprawdzić logi błędów
4. Zaktualizować cache walut po migracji

