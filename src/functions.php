<?php

if (!function_exists('memory_limit_bytes')) {
    function memory_limit_bytes(): int
    {
        $memoryLimit = trim(ini_get('memory_limit'));

        if ('-1' === $memoryLimit) {
            return -1;
        }
        $memoryLimit = strtolower($memoryLimit);
        $max         = strtolower(ltrim($memoryLimit, '+'));
        if (0 === strpos($max, '0x')) {
            $max = intval($max, 16);
        } elseif (0 === strpos($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = (int)$max;
        }
        switch (substr($memoryLimit, -1)) {
            case 't':
                $max *= pow(1024, 4);
                break;
            case 'g':
                $max *= pow(1024, 3);
                break;
            case 'm':
                $max *= pow(1024, 2);
                break;
            case 'k':
                $max *= 1024;
        }
        return $max;
    }
}