<?php

namespace App\Repository;

use App\Entity\Begehung;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Begehung>
 */
class BegehungRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Begehung::class);
    }

    /**
     * Findet aktive Begehungen für einen Benutzer
     */
    public function findActiveByUser(User $user): ?Begehung
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('b.startTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Findet alle Begehungen für einen Benutzer
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Findet Begehungen in einem bestimmten Zeitraum
     */
    public function findByUserAndDateRange(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->andWhere('b.startTime >= :startDate')
            ->andWhere('b.startTime <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Findet Begehungen mit GPS-Daten in einem bestimmten Bereich
     */
    public function findByBoundingBox(float $minLat, float $maxLat, float $minLon, float $maxLon): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.startLatitude >= :minLat')
            ->andWhere('b.startLatitude <= :maxLat')
            ->andWhere('b.startLongitude >= :minLon')
            ->andWhere('b.startLongitude <= :maxLon')
            ->setParameter('minLat', $minLat)
            ->setParameter('maxLat', $maxLat)
            ->setParameter('minLon', $minLon)
            ->setParameter('maxLon', $maxLon)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Zählt die Anzahl der Begehungen für einen Benutzer
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Findet die neueste Begehung für einen Benutzer
     */
    public function findLatestByUser(User $user): ?Begehung
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.startTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Findet Begehungen mit Funden
     */
    public function findWithFounds(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.foundsImages', 'f')
            ->andWhere('b.user = :user')
            ->andWhere('f.id IS NOT NULL')
            ->setParameter('user', $user)
            ->orderBy('b.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Berechnet die Gesamtdauer aller Begehungen für einen Benutzer
     */
    public function getTotalDurationByUser(User $user): int
    {
        $result = $this->createQueryBuilder('b')
            ->select('SUM(b.duration)')
            ->andWhere('b.user = :user')
            ->andWhere('b.duration IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
