<?php

class MemoryHelper
{
    public static function formatBytes($size, $precision = 2): string
    {
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');
        return round(1024 ** ($base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    public function getMemory($inBytes = false): array
    {
        $mem_usage = memory_get_usage();
        $mem_peak = memory_get_peak_usage();

        if ($inBytes) {
            return [$mem_usage, $mem_peak];
        }

        return [$this->formatBytes($mem_usage), $this->formatBytes($mem_peak)];
    }

}