<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
}
echo "Cache cleared\n";

