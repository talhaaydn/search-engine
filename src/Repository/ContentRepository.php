<?php

namespace App\Repository;

use App\Entity\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    /**
     * @param Content[] $contents
     */
    public function bulkInsert(array $contents): void
    {
        $em = $this->getEntityManager();
        $batchSize = 50;

        foreach ($contents as $index => $content) {
            $em->persist($content);

            if (($index + 1) % $batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * @param string $providerName
     * @param string $contentId
     * @return Content|null
     */
    public function findByProviderAndContentId(string $providerName, string $contentId): ?Content
    {
        return $this->findOneBy([
            'providerName' => $providerName,
            'providerContentId' => $contentId,
        ]);
    }

    /**
     * @param Content[] $contents
     */
    public function upsertContents(array $contents): void
    {
        $em = $this->getEntityManager();
        $batchSize = 50;

        foreach ($contents as $index => $content) {
            $existing = $this->findByProviderAndContentId(
                $content->getProviderName(),
                $content->getProviderContentId()
            );

            if ($existing) {
                $existing->setTitle($content->getTitle())
                    ->setContentType($content->getContentType())
                    ->setScore($content->getScore());
                
                $em->persist($existing);
            } else {
                $em->persist($content);
            }

            if (($index + 1) % $batchSize === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }
}

