<?php

namespace App\Service\Utility;

class DurationParser
{
    /**
     * Duration formatını saniye cinsinden süre değerine çevirir
     * 
     * Desteklenen formatlar:
     * - "MM:SS" → Dakika:Saniye (örn: "15:30" → 930 saniye)
     * - "HH:MM:SS" → Saat:Dakika:Saniye (örn: "1:22:45" → 4965 saniye)
     * 
     * @param string|int|null $duration Süre değeri
     * @return int Saniye cinsinden süre
     */
    public function parseDuration(?string $duration): int
    {
        if (empty($duration) || $duration === null) {
            return 0;
        }

        return $this->parseTimeFormat($duration);
    }

    /**
     * @param string $duration Time formatında süre (HH:MM:SS veya MM:SS)
     * @return int Saniye cinsinden süre
     */
    private function parseTimeFormat(string $duration): int
    {
        $parts = explode(':', $duration);
        $totalSeconds = 0;

        if (count($parts) === 3) {
            // HH:MM:SS formatı
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
            $seconds = (int)$parts[2];
            
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
        } elseif (count($parts) === 2) {
            // MM:SS formatı
            $minutes = (int)$parts[0];
            $seconds = (int)$parts[1];
            
            $totalSeconds = ($minutes * 60) + $seconds;
        }

        return $totalSeconds;
    }
}

