<?php

namespace App\Controller\Api;

use App\DTO\Content\ContentSearchRequestDTO;
use App\DTO\Content\ContentSearchResponseItemDTO;
use App\DTO\Pagination\PaginationMetaDTO;
use App\Service\Content\ContentService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    public function __construct(
        private readonly ContentService $contentService
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('content/search.html.twig');
    }

    #[Route('/api/contents', name: 'content_index', methods: ['GET'])]
    #[OA\Get(
        path: '/api/contents',
        summary: 'Content search',
        description: 'Search content by keyword, content type and sort by score',
        tags: ['Content']
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful search result',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: ContentSearchResponseItemDTO::class)),
                    description: 'Array of content items'
                ),
                new OA\Property(
                    property: 'meta',
                    ref: new Model(type: PaginationMetaDTO::class),
                    description: 'Pagination metadata'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid parameters'
    )]
    public function index(
        #[MapQueryString] ContentSearchRequestDTO $searchRequest
    ): JsonResponse {
        return $this->json(
            $this->contentService->search($searchRequest)
        );
    }
}

