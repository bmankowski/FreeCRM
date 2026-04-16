
## Architektura docelowa (target)
### Kontrakt
- Każdy cron task to klasa PSR‑4:
  - np. `App\Modules\Kandydaci\Cron\ScheduledImportTask`
- Klasa implementuje minimalny interfejs:
  - `CronTaskInterface` z metodą `execute(): void`
### Źródło prawdy
- DB przechowuje:
  - `handler_class` (FQCN klasy)
  - `handler_params` (opcjonalne parametry)
  - nadal też `handler_file` jako fallback/legacy (na czas migracji)
### Wykonanie
- Runner preferuje `handler_class`, a gdy brak → używa `handler_file`.
---
## Zasady projektowe (żeby nie over-engineerować)
- Minimalny interfejs: tylko `execute(): void`.
- Brak wymaganego DI‑kontenera na start.
- Parametry taska są opcjonalne i proste (JSON/TEXT).
- Kompatybilność wsteczna jest priorytetem: żadnych “big‑bang” zmian.
- Runner ma walidować klasę i interfejs, ale ma **nie ubijać** całego CRON przy błędzie taska.
---
## Faza 0 — Inwentaryzacja
### Co robimy
- Eksportujemy listę tasków z `vtiger_cron_task`.
- Grupujemy taski:
  - **A**: handler_file to “1-liner” odpalający metodę klasy
  - **B**: proceduralne skrypty (duża logika w pliku)
  - **C**: legacy `modules/<Module>/cron/*.php`
### Kryteria sukcesu
- Mamy listę tasków + priorytety migracji (najprostsze → najtrudniejsze).
---
## Faza 1 — Kontrakt klas (PSR)
### Co dodajemy
- `App\Modules\Cron\Contract\CronTaskInterface`:
  - `public function execute(): void;`
### Opcjonalne konwencje (nie wymagane)
- `public static function getDefaultFrequency(): int`
- `public static function getModule(): string`
- `public static function getDescription(): string`
### Kryteria sukcesu
- Da się stworzyć nowy task jako klasa i uruchomić go lokalnie (bez DB).
---
## Faza 2 — Rozszerzenie schematu DB (backward-compatible)
### Zmiany w tabeli `vtiger_cron_task`
Dodajemy (minimalnie):
- `handler_class VARCHAR(255) NULL`
- `handler_params JSON NULL` *(lub `TEXT NULL` jeśli JSON nie jest opcją)*
Opcjonalnie:
- `lock_ttl INT NULL` (czas blokady per task)
- `timeout INT NULL` (limit czasu per task) — jeśli w ogóle chcesz to wspierać
### Zasada uruchamiania
- Jeśli `handler_class` jest ustawione → uruchom klasę
- W przeciwnym razie → uruchom `handler_file` jak dotychczas
### Kryteria sukcesu
- Stare taski działają bez zmian.
- Nowe taski mogą być rejestrowane przez `handler_class`.
---
## Faza 3 — Zmiana Runnera (uruchamianie klas)
### Zachowanie Runnera
Dla każdego taska:
1. jeśli `handler_class`:
   - `class_exists($fqcn)`; jeśli nie → log error + mark finished (lub mark error, jeśli dodasz status)
   - sprawdź `instanceof CronTaskInterface`; jeśli nie → log error + skip
   - utwórz obiekt:
     - na start: `new $fqcn()` (bez DI)
   - jeśli `handler_params` → przekaż:
     - wariant A: konstruktor `__construct(array $params = [])`
     - wariant B: `setParams(array $params): void` (opcjonalny interface)
   - `$task->execute()`
2. else:
   - `require_once handler_file` (legacy)
### Obsługa błędów
- Exception w tasku:
  - log + nie ubijaj pozostałych tasków
- Komunikaty stdout:
  - ograniczamy; preferujemy `App\Log\Log`
### Kryteria sukcesu
- Jeden task na `handler_class` odpala się przez runner.
- Stare taski na `handler_file` nadal działają.
---
## Faza 4 — Rejestracja tasków (proste API)
### Minimum
- Dodaj helper `CronRegistry` lub prostą metodę w istniejącej warstwie:
  - `registerClassTask(name, fqcn, frequency, module, status, sequence, description, params?)`
### Dlaczego
- Żeby nie “klikać” inserta ręcznie w SQL przy nowych taskach.
- Bez UI (UI może być później).
### Kryteria sukcesu
- Da się dodać nowy task klasowy jednym wywołaniem w migracji/instalatorze.
---
## Faza 5 — Migracja tasków (iteracyjnie)
### Strategia
- Migrujemy w małych PR‑ach:
  - 1–3 taski na PR
- Kolejność:
  1. najprostsze (A)
  2. proceduralne (B)
  3. najbardziej ryzykowne (C / skanery / workflow)
### Kroki dla pojedynczego taska
1. Tworzysz klasę `*Task` implementującą `CronTaskInterface`
2. Przenosisz logikę do klasy (bez zmiany zachowania)
3. Ustawiasz w DB `handler_class = FQCN` (opcjonalnie params)
4. Zostawiasz `handler_file` jako fallback przez 1–2 wydania
5. Potem (opcjonalnie) czyścisz `handler_file` albo zostawiasz legacy na zawsze
### Kryteria sukcesu
- Task działa w cron runnerze na produkcyjnym flow.
- Logi są czytelne, brak nowych krytycznych błędów.
---
## Faza 6 — Deprecacja `handler_file` (ostatnia)
### Opcja A (bezpieczna)
- Zostawiamy `handler_file` jako legacy “na zawsze”.
- Dodajemy tylko warning w logu, jeśli task wciąż go używa (opcjonalnie).
### Opcja B (twardsza)
- Po pełnej migracji:
  - dokumentujemy usunięcie wsparcia `handler_file`
  - usuwamy gałąź `require_once` z runnera
### Kryteria sukcesu
- 100% tasków działa na `handler_class` (jeśli wybierasz opcję B).
---
## Test plan (praktyczny)
### Smoke
- Uruchom:
  - `cron/vtigercron.php service=<task_legacy_file>`
  - `cron/vtigercron.php service=<task_class_based>`
### Negatywne
- `handler_class` wskazuje nieistniejącą klasę → runner loguje i idzie dalej
- `handler_class` nie implementuje interfejsu → runner loguje i idzie dalej
### Produkcyjne kryterium
- Po wdrożeniu sprawdzamy `cache/logs/system.log` pod kątem nowych błędów runtime.
---
## Kryteria zakończenia migracji
- Nowe taski są tworzone jako klasy PSR‑4 i uruchamiane przez interfejs.
- Runner wspiera `handler_class` i (opcjonalnie) legacy `handler_file`.
- Wszystkie stare taski są przeniesione lub mają stabilny fallback.
- Brak “magii” i ciężkich zależności: proste klasy, prosty interfejs, prosta migracja.