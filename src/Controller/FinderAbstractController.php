<?php
declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

class FinderAbstractController extends SymfonyAbstractController
{
    public function __construct() { }

    public function getUserFullName():string
    {
        return $this->getUser() && $this->getUser()->vorname && $this->getUser()->nachname
            ? $this->getUser()->vorname . ' ' . $this->getUser()->nachname
            : 'anonymous';
    }

}

