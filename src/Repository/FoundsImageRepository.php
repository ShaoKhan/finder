<?php

namespace App\Repository;

use App\Entity\FoundsImage;
use App\Form\FoundsImageUploadType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 * @method FoundsImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoundsImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoundsImage[]    findAll()
 * @method FoundsImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoundsImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoundsImage::class);
    }

    /**
     * Speichert ein Photo-Objekt in der Datenbank.
     */
    public function save(FoundsImage $photo, bool $flush = true): void
    {
        $this->_em->persist($photo);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Entfernt ein Photo-Objekt aus der Datenbank.
     */
    public function remove(Photo $photo, bool $flush = true): void
    {
        $this->_em->remove($photo);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAllSortedByField(string $sortField = 'name', string $sortOrder = 'ASC')
    {
        $allowedFields = ['name', 'nearestTown', 'createdAt'];

        if (!in_array($sortField, $allowedFields)) {
            throw new \InvalidArgumentException(sprintf('Invalid sort field: %s', $sortField));
        }

        return $this->createQueryBuilder('i')
                    ->orderBy("i.$sortField", $sortOrder)
                    ->getQuery();
    }

    public function findAllFiltered(string $sortField, string $sortOrder, string $search): Query
    {
        $qb = $this->createQueryBuilder('f');

        // Suchfilter
        if (!empty($search)) {
            $qb->andWhere('f.name LIKE :search OR f.nearestTown LIKE :search OR f.createdAt LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Sortierung
        $qb->orderBy('f.' . $sortField, $sortOrder);

        return $qb->getQuery();
    }
}