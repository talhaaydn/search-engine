<?php

namespace App\Service\Content;

use App\DTO\Content\ContentSearchRequestDTO;
use App\DTO\Content\ContentSearchResponseItemDTO;
use App\DTO\Pagination\PaginationMetaDTO;
use App\DTO\Pagination\PaginationResponseDTO;
use App\Repository\ElasticsearchContentRepository;

class ContentService
{
    public function __construct(
        private readonly ElasticsearchContentRepository $elasticsearchContentRepository
    ) {
    }

    public function search(ContentSearchRequestDTO $request): PaginationResponseDTO
    {
        ['results' => $results, 'total' => $total] = $this->elasticsearchContentRepository->search($request);

        $data = [];
        foreach ($results as $contentDocument) {
            $data[] = new ContentSearchResponseItemDTO(
                $contentDocument->id,
                $contentDocument->title,
                $contentDocument->contentType,
                $contentDocument->score,
                $contentDocument->createdAt,
            );
        }

        $meta = new PaginationMetaDTO(
            $total,
            $request->getPage(),
            $request->getLimit()
        );

        return new PaginationResponseDTO($data, $meta);
    }
}


