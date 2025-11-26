<?php

namespace App\Repository;

use App\DTO\Content\ContentSearchRequestDTO;
use App\DTO\Elasticsearch\ContentDocumentDTO;
use App\Service\Elasticsearch\ElasticsearchService;
use Elastica\Query;
use Elastica\Query\BoolQuery;
use Elastica\Query\MatchAll;
use Elastica\Query\Term;
use Elastica\Query\MatchQuery;

class ElasticsearchContentRepository
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearchService
    ) {
    }

    public function search(ContentSearchRequestDTO $request): array
    {
        $boolQuery = new BoolQuery();

        if ($request->getKeyword()) {
            $matchQuery = new MatchQuery();
            $matchQuery->setField('title', $request->getKeyword());
            $boolQuery->addMust($matchQuery);
        } else {
            $boolQuery->addMust(new MatchAll());
        }

        if ($request->getContentType()) {
            $termQuery = new Term();
            $termQuery->setTerm('contentType', $request->getContentType()->value);
            $boolQuery->addFilter($termQuery);
        }

        $query = new Query($boolQuery);

        if ($request->getSortByScore()) {
            $query->setSort([
                'score' => ['order' => $request->getSortByScore()]
            ]);
        }

        $query->setFrom($request->getOffset());
        $query->setSize($request->getLimit());

        $index = $this->elasticsearchService->getIndex();
        $resultSet = $index->search($query);

        $total = $resultSet->getTotalHits();

        $results = [];
        foreach ($resultSet->getResults() as $result) {
            $results[] = ContentDocumentDTO::fromArray($result->getData());
        }

        return [
            'results' => $results,
            'total' => $total
        ];
    }
}

