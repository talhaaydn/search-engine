<?php

namespace App\EventListener;

use App\Entity\Content;
use App\Service\Elasticsearch\ContentIndexer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
class ContentElasticsearchSyncListener
{
    public function __construct(
        private readonly ContentIndexer $contentIndexer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Content) {
            return;
        }

        try {
            $this->contentIndexer->indexContent($entity);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index content to Elasticsearch', [
                'content_id' => $entity->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Content) {
            return;
        }

        try {
            $this->contentIndexer->updateContent($entity);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update content in Elasticsearch', [
                'content_id' => $entity->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}

