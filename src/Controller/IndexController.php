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
        $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('index/hinweise.html.twig');
    }

    #[Route('imprint', name: 'imprint')]
    public function impressumAction(): Response
    {
        return $this->render('index/imprint.html.twig');
    }

    #[Route('privacy-policy', name: 'privacy-policy')]
    public function privacyPolicyAction(): Response
    {
        return $this->render('index/privacyPolicy.html.twig');
    }

    #[Route('contact', name: 'contact')]
    public function contactAction(): Response
    {
        return $this->render('index/contact.html.twig');
    }

}
