<?php

namespace App\Command;

use App\Entity\FoundsImage;
use App\Repository\FoundsImageRepository;
use App\Service\GeoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:calculate-bearing',
    description: 'Berechnet die Himmelsrichtung für alle vorhandenen Fundmeldungen',
)]
class CalculateBearingCommand extends Command
{
    public function __construct(
        private FoundsImageRepository $foundsImageRepository,
        private GeoService $geoService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Zeigt nur an, was geändert würde, ohne zu speichern')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Begrenzt die Anzahl der zu verarbeitenden Bilder', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $limit = (int) $input->getOption('limit');

        $io->title('Himmelsrichtung-Berechnung für Fundmeldungen');

        // Finde alle Bilder ohne Himmelsrichtung
        $images = $this->foundsImageRepository->createQueryBuilder('f')
            ->where('f.directionToChurchOrCenter IS NULL')
            ->andWhere('f.latitude IS NOT NULL')
            ->andWhere('f.longitude IS NOT NULL')
            ->andWhere('f.latitude != 0')
            ->andWhere('f.longitude != 0')
            ->andWhere('f.churchOrCenterName IS NOT NULL')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($images)) {
            $io->success('Alle Fundmeldungen haben bereits eine Himmelsrichtung berechnet!');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Gefunden: %d Fundmeldungen ohne Himmelsrichtung', count($images)));

        if ($dryRun) {
            $io->note('DRY-RUN Modus: Es werden keine Änderungen gespeichert');
        }

        $progressBar = $io->createProgressBar(count($images));
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        foreach ($images as $image) {
            try {
                // Extrahiere Koordinaten aus dem churchOrCenterName
                $churchOrCenterName = $image->churchOrCenterName;
                if (!$churchOrCenterName) {
                    $progressBar->advance();
                    continue;
                }

                // Bestimme die Zielkoordinaten basierend auf dem Namen
                $targetLat = null;
                $targetLon = null;

                if (strpos($churchOrCenterName, 'Kirche:') === 0) {
                    // Für Kirchen: Suche die nächste Kirche
                    $nearestChurch = $this->geoService->findNearestChurch($image->latitude, $image->longitude);
                    if ($nearestChurch && isset($nearestChurch['latitude'], $nearestChurch['longitude'])) {
                        $targetLat = $nearestChurch['latitude'];
                        $targetLon = $nearestChurch['longitude'];
                    }
                } elseif (strpos($churchOrCenterName, 'Ort:') === 0) {
                    // Für Orte: Suche den nächsten Ortskern
                    $nearestTown = $this->geoService->getNearestTown($image->latitude, $image->longitude);
                    if ($nearestTown && isset($nearestTown['latitude'], $nearestTown['longitude'])) {
                        $targetLat = $nearestTown['latitude'];
                        $targetLon = $nearestTown['longitude'];
                    }
                }

                if ($targetLat && $targetLon) {
                    // Berechne die Himmelsrichtung
                    $direction = $this->geoService->calculateBearing(
                        $image->latitude,
                        $image->longitude,
                        $targetLat,
                        $targetLon
                    );

                    if (!$dryRun) {
                        $image->directionToChurchOrCenter = $direction;
                        $this->entityManager->persist($image);
                    }

                    $updated++;
                } else {
                    $errors++;
                }

            } catch (\Exception $e) {
                $errors++;
                $io->warning(sprintf('Fehler bei Bild ID %d: %s', $image->getId(), $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if (!$dryRun && $updated > 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf(
            'Abgeschlossen! %d Bilder aktualisiert, %d Fehler',
            $updated,
            $errors
        ));

        if ($dryRun) {
            $io->note('Führen Sie den Befehl ohne --dry-run aus, um die Änderungen zu speichern');
        }

        return Command::SUCCESS;
    }
}
