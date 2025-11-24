<?php

namespace App\Service\Utility;

class DurationParser
{
    /**
     * Duration formatını saniye cinsinden süre değerine çevirir
     * 
     * Desteklenen formatlar:
     * - "M" → Sadece Dakika (örn: "8" → 480 saniye)
     * - "MM:SS" → Dakika:Saniye (örn: "15:30" → 930 saniye)
     * - "HH:MM:SS" → Saat:Dakika:Saniye (örn: "1:22:45" → 4965 saniye)
     * 
     * @param string|null $duration Süre değeri
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
     * @param string $duration Time formatında süre (HH:MM:SS, MM:SS veya M)
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
        } elseif (count($parts) === 1) {
            // Sadece dakika formatı (örn: "8")
            $minutes = (int)$parts[0];
            
            $totalSeconds = $minutes * 60;
        }

        return $totalSeconds;
    }
}

