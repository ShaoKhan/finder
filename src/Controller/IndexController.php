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

    #[Route('/changelog', name: 'app_changelog')]
    public function changelog(): Response
    {
        // Hole die Git-Commits
        $commits = [];
        $gitLog = [];
        exec('git log --pretty=format:"%ad|%s" --date=format:"%Y-%m-%d" -n 50', $gitLog);
        
        foreach ($gitLog as $log) {
            $parts = explode('|', $log);
            if (count($parts) === 2) {
                $date = \DateTime::createFromFormat('Y-m-d', $parts[0]);
                $commits[] = [
                    'date' => $date->format('d.m.Y'),
                    'sortDate' => $parts[0], // Original-Datum für Sortierung
                    'message' => $parts[1]
                ];
            }
        }

        // Gruppiere Commits nach Datum
        $groupedCommits = [];
        foreach ($commits as $commit) {
            $date = $commit['date'];
            $sortDate = $commit['sortDate'];
            if (!isset($groupedCommits[$sortDate])) {
                $groupedCommits[$sortDate] = [
                    'displayDate' => $date,
                    'commits' => []
                ];
            }
            $groupedCommits[$sortDate]['commits'][] = [
                'message' => $commit['message']
            ];
        }
        
        // Sortiere nach Datum (neueste zuerst)
        krsort($groupedCommits);

        return $this->render('index/changelog.html.twig', [
            'commits' => $groupedCommits
        ]);
    }

}
