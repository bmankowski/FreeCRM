<?php
declare(strict_types=1);
final class LockGlobal {
    private ?\resource $handle = null;
    public function set(mixed $h): void { $this->handle = $h; }
}
