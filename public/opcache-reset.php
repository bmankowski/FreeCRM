<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "Opcache reset successfully\n";
} else {
    echo "Opcache not available\n";
}

