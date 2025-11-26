<?php

namespace App\Service\Content;

use App\DTO\Content\ContentSearchRequestDTO;
use App\DTO\Content\ContentSearchResponseItemDTO;
use App\DTO\Pagination\PaginationMetaDTO;
use App\DTO\Pagination\PaginationResponseDTO;
use App\Repository\ContentRepository;

class ContentService
{
    public function __construct(
        private readonly ContentRepository $contentRepository
    ) {
    }

    public function search(ContentSearchRequestDTO $request): PaginationResponseDTO
    {
        ['contents' => $contents, 'total' => $total] = $this->contentRepository->searchContents($request);

        $data = [];
        foreach ($contents as $content) {
            $data[] = new ContentSearchResponseItemDTO(
                $content->getId(),
                $content->getTitle(),
                $content->getContentType(),
                $content->getScore(),
                $content->getCreatedAt(),
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

