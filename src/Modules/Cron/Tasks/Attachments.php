<?php
/**
 * MultiImages cron
 * @package YetiForce.Cron
 * @license licenses/License.html
 * @author Michał Lorencik <m.lorencik.com>
 */
\App\Modules\Base\Models\Files::getRidOfTrash(false, \App\AppConfig::performance('CRON_MAX_ATACHMENTS_DELETE'));
