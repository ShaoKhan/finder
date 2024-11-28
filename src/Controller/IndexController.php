<?php

declare(strict_types = 1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends FinderAbstractController
{

    #[Route('/home', name: 'home')]
    public function index(): Response
    {
        return $this->render('index/index.html.twig');
    }

    #[Route('/hinweise', name: 'hinweise')]
    public function hintsAction(): Response
    {
        return $this->render('index/hinweise.html.twig');
    }

}
