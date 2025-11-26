<?php

namespace App\Service\Provider;

use App\DTO\ContentDTO;
use App\Enum\ContentType;

class ContentScorer
{
    /**
     * İçerik için skor hesaplar
     * 
     * Final Skor = (Temel Puan * İçerik Türü Katsayısı) + Güncellik Puanı + Etkileşim Puanı
     */
    public function calculateScore(ContentDTO $dto): float
    {
        $baseScore = $this->calculateBaseScore($dto);
        $typeMultiplier = $this->getContentTypeMultiplier($dto->type);
        $freshnessScore = $this->calculateFreshnessScore($dto);
        $engagementScore = $this->calculateEngagementScore($dto);

        $finalScore = ($baseScore * $typeMultiplier) + $freshnessScore + $engagementScore;

        return round($finalScore, 2);
    }

    /**
     * Temel puan hesaplama
     * - Video: views / 1000 + (likes / 100)
     * - Metin: reading_time + (reactions / 50)
     */
    private function calculateBaseScore(ContentDTO $dto): float
    {
        if ($dto->type === ContentType::VIDEO) {
            return ($dto->views / 1000) + ($dto->likes / 100);
        }

        if ($dto->type === ContentType::ARTICLE) {
            return $dto->readingTime + ($dto->reactions / 50);
        }

        return 0.0;
    }

    /**
     * İçerik türü katsayısı
     * - Video: 1.5
     * - Metin: 1.0
     */
    private function getContentTypeMultiplier(ContentType $type): float
    {
        return match ($type) {
            ContentType::VIDEO => 1.5,
            ContentType::ARTICLE => 1.0,
        };
    }

    /**
     * Güncellik puanı hesaplama
     * - 1 hafta içinde: +5
     * - 1 ay içinde: +3
     * - 3 ay içinde: +1
     * - Daha eski: +0
     */
    private function calculateFreshnessScore(ContentDTO $dto): float
    {
        $now = new \DateTime();
        $publishedAt = $dto->publishedAt;
        $diff = $now->diff($publishedAt);
        
        $totalDays = $diff->days;

        if ($totalDays <= 7) {
            return 5.0;
        }

        if ($totalDays <= 30) {
            return 3.0;
        }

        if ($totalDays <= 90) {
            return 1.0;
        }

        return 0.0;
    }

    /**
     * Etkileşim puanı hesaplama
     * - Video: (likes / views) * 10
     * - Metin: (reactions / reading_time) * 5
     */
    private function calculateEngagementScore(ContentDTO $dto): float
    {
        if ($dto->type === ContentType::VIDEO) {
            if ($dto->views === 0) {
                return 0.0;
            }
            return ($dto->likes / $dto->views) * 10;
        }

        if ($dto->type === ContentType::ARTICLE) {
            if ($dto->readingTime === 0) {
                return 0.0;
            }
            return ($dto->reactions / $dto->readingTime) * 5;
        }

        return 0.0;
    }
}

