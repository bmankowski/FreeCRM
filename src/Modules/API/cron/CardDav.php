<?php
/**
 * CardDAV Cron Class
 * @package YetiForce.Cron
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
\App\Log\Log::trace('Start cron CardDAV');

\App\Modules\API\Models\DAV::runCronCardDav();

\App\Log\Log::trace('End cron CardDAV');
