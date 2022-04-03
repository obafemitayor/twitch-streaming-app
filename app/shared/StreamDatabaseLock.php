<?php

namespace App\Shared;

class StreamDatabaseLock
{
    private static $lock = null;

    public static function aquireLock() {
        static::$lock = true;
    }

    public static function releaseLock() {
        static::$lock = false;
    }

    public static function checkIfLockExist() {
        return  static::$lock;
    }
}
