<?php
declare(strict_types = 1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

class FinderAbstractController extends SymfonyAbstractController
{
    public function __construct() { }

    public function getUserFullName():string
    {
        $user = $this->getUser();
        if ($user && method_exists($user, 'getVorname') && method_exists($user, 'getNachname')) {
            $vorname = $user->getVorname();
            $nachname = $user->getNachname();
            if ($vorname && $nachname) {
                return $vorname . ' ' . $nachname;
            }
        }
        return 'anonymous';
    }

}

