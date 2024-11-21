<?php

declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FoundsController extends AbstractController
{

    #[Route('/founds/index', name: 'founds_index')]
    public function index(): Response
    {
        return $this->render('founds/index.html.twig');
    }
}
