<?php
/**
 * CalDAV Cron Class
 * @package YetiForce.Cron
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
\App\Log::trace('Start cron CalDAV');
\App\Modules\API\Models\DAV::runCronCalDav();
\App\Log::trace('End cron CalDAV');
