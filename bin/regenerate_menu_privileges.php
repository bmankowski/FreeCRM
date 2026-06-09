#!/usr/bin/env php
<?php
/**
 * FreeCRM - Legacy entry point; runs full user_privileges regeneration.
 *
 * Run: docker compose exec -T app php bin/regenerate_menu_privileges.php
 *
 * @deprecated Use bin/regenerate_user_privileges.php
 */

declare(strict_types=1);

fwrite(STDERR, "Note: prefer bin/regenerate_user_privileges.php for full rebuild.\n");

require __DIR__ . '/regenerate_user_privileges.php';
