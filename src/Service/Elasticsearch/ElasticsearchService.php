<?php

namespace App\Service\Elasticsearch;

use Elastica\Client;
use Elastica\Document;
use Elastica\Index;
use Psr\Log\LoggerInterface;

class ElasticsearchService
{
    private Client $client;
    private Index $index;
    private string $indexName;

    public function __construct(
        string $elasticsearchHost,
        int $elasticsearchPort,
        string $indexName,
        private readonly LoggerInterface $logger
    ) {
        $this->client = new Client([
            'hosts' => [
                sprintf('%s:%d', $elasticsearchHost, $elasticsearchPort)
            ]
        ]);
        
        $this->indexName = $indexName;
        $this->index = $this->client->getIndex($this->indexName);
    }

    public function createIndex(): void
    {
        if ($this->index->exists()) {
            $this->logger->info('Index already exists', ['index' => $this->indexName]);
            return;
        }

        $config = [
            'mappings' => [
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'contentType' => [
                        'type' => 'keyword',
                        'doc_values' => true
                    ],
                    'score' => [
                        'type' => 'float',
                        'doc_values' => true
                    ],
                    'createdAt' => [
                        'type' => 'date',
                        'format' => 'strict_date_optional_time||epoch_millis'
                    ],
                ]
            ]
        ];

        $this->index->create($config, ['recreate' => false]);
        $this->logger->info('Index created successfully', ['index' => $this->indexName]);
    }

    public function deleteIndex(): void
    {
        if ($this->index->exists()) {
            $this->index->delete();
            $this->logger->info('Index deleted', ['index' => $this->indexName]);
        }
    }

    public function indexDocument(Document $document): void
    {
        $this->index->addDocument($document);
        $this->index->refresh();
    }

    public function bulkIndexDocuments(array $documents): void
    {
        if (empty($documents)) {
            return;
        }

        $this->index->addDocuments($documents);
        $this->index->refresh();
        
        $this->logger->info('Bulk indexed documents', ['count' => count($documents)]);
    }

    public function getIndex(): Index
    {
        return $this->index;
    }

    public function isHealthy(): bool
    {
        try {
            $health = $this->client->getCluster()->getHealth();
            return in_array($health->getStatus(), ['green', 'yellow']);
        } catch (\Exception $e) {
            $this->logger->error('Elasticsearch health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

