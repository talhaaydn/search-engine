<?php

namespace App\Service\Elasticsearch;

use App\DTO\Elasticsearch\ContentDocumentDTO;
use App\Entity\Content;
use Elastica\Document;
use Psr\Log\LoggerInterface;

class ContentIndexer
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearchService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function indexContent(Content $content): void
    {
        $document = $this->createDocument($content);
        $this->elasticsearchService->indexDocument($document);
        
        $this->logger->info('Content indexed to Elasticsearch', [
            'content_id' => $content->getId(),
            'title' => $content->getTitle()
        ]);
    }

    public function bulkIndexContents(array $contents): void
    {
        $documents = [];
        foreach ($contents as $content) {
            $documents[] = $this->createDocument($content);
        }

        $this->elasticsearchService->bulkIndexDocuments($documents);
    }

    public function updateContent(Content $content): void
    {
        $this->indexContent($content);
    }

    private function createDocument(Content $content): Document
    {
        $contentDto = ContentDocumentDTO::fromEntity($content);
        return new Document((string)$content->getId(), $contentDto->toArray());
    }
}

