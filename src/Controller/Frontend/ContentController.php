<?php

namespace App\Controller\Frontend;

use App\Service\Content\ContentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContentController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('content/search.html.twig');
    }
}

