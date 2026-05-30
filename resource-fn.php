<?php
declare(strict_types=1);
function open(): resource|false {
    return fopen("/tmp/x", "w+");
}
final class LockFn {
    public function acquire(): bool {
        $h = fopen("/tmp/x", "c+");
        if ($h === false) return false;
        flock($h, LOCK_EX);
        return true;
    }
}
