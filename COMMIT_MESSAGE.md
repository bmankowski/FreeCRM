refactor: Replace vglobal() with AppConfig::main() for configuration values

Replace all occurrences of vglobal() calls for configuration values with
AppConfig::main() to modernize configuration access and improve code consistency.

## Phase 4: upload_maxsize (5 files)
- Replace vglobal('upload_maxsize') with AppConfig::main('upload_maxsize')
- Updated base classes: Edit.php, QuickCreateAjax.php
- Updated helper: Util::getMaxUploadSize()
- Updated views: Faq/Edit.php
- Updated action: Settings/PDF/Actions/Watermark.php

## Phase 5: Configuration values (12 files, 17 occurrences)
- default_timezone (6 files): Util.php, ScheduleReports.php, WSAPP/Utils.php,
  WorkFlowScheduler.php, Workflow.php, EditRecordStructure.php
- site_URL (3 files, 4 occurrences): ScheduleReports.php, ForgotPassword.php,
  Users_ForgotPassword_Handler.php
- cache_dir (4 files): PaymentsIn/Views/step1.php, PaymentsIn/Models/Record.php,
  PaymentsOut/Views/step1.php, PaymentsOut/Models/Record.php
- tmp_dir (2 files, 3 occurrences): Reports/Models/Record.php, QuickExport.php

## Phase 6: Remaining configuration values (9 files, 9 occurrences)
- default_charset (3 files): CustomView/Models/Record.php, OSSMail/Models/Module.php,
  Documents/Models/Record.php
- php_max_execution_time (4 files): Settings/Profiles/Models/Record.php,
  Settings/GlobalPermission/Models/Record.php, Settings/SharingAccess/Models/Module.php,
  Settings/Groups/Models/Record.php
- davStorageDir (1 file, 2 occurrences): Settings/Dav/Models/Module.php

## Benefits
- Consistent configuration access pattern across the codebase
- Better maintainability and testability
- No functional changes, only refactoring of configuration access
- All configuration values now use AppConfig::main() instead of vglobal()
- All changes verified with grep and linter checks

## Files modified (26 total)
- src/Modules/Base/Helpers/Util.php
- src/Modules/Base/Views/Edit.php
- src/Modules/Base/Views/QuickCreateAjax.php
- src/Modules/Faq/Views/Edit.php
- src/Modules/Settings/PDF/Actions/Watermark.php
- src/Modules/Reports/Models/ScheduleReports.php
- src/Modules/WSAPP/Utils.php
- src/Modules/Workflow/WorkFlowScheduler.php
- src/Modules/Workflow/Workflow.php
- src/Modules/Users/Models/EditRecordStructure.php
- src/Modules/Users/Actions/ForgotPassword.php
- src/Modules/Users/Handlers/Users_ForgotPassword_Handler.php
- src/Modules/PaymentsIn/Views/step1.php
- src/Modules/PaymentsIn/Models/Record.php
- src/Modules/PaymentsOut/Views/step1.php
- src/Modules/PaymentsOut/Models/Record.php
- src/Modules/Reports/Models/Record.php
- src/Modules/Base/Actions/QuickExport.php
- src/Modules/CustomView/Models/Record.php
- src/Modules/OSSMail/Models/Module.php
- src/Modules/Documents/Models/Record.php
- src/Modules/Settings/Profiles/Models/Record.php
- src/Modules/Settings/GlobalPermission/Models/Record.php
- src/Modules/Settings/SharingAccess/Models/Module.php
- src/Modules/Settings/Groups/Models/Record.php
- src/Modules/Settings/Dav/Models/Module.php

Refs: documentation/vglobal-refactoring-plan-detailed.md (Phase 4, 5 & 6)

