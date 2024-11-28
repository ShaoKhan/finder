<?php

namespace App\Repository;

use App\Entity\FoundsImage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 * @method FoundsImage|null find($id, $lockMode = NULL, $lockVersion = NULL)
 * @method FoundsImage|null findOneBy(array $criteria, array $orderBy = NULL)
 * @method FoundsImage[]    findAll()
 * @method FoundsImage[]    findBy(array $criteria, array $orderBy = NULL, $limit = NULL, $offset = NULL)
 */
class FoundsImageRepository extends ServiceEntityRepository
{
    private mixed $_em;

    public function __construct(ManagerRegistry $registry
    )
    {
        parent::__construct($registry, FoundsImage::class);
    }

    /**
     * Speichert ein Photo-Objekt in der Datenbank.
     */
    public function save(FoundsImage $photo, bool $flush = TRUE): void
    {
        $this->getEntityManager()->persist($photo);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Entfernt ein Photo-Objekt aus der Datenbank.
     */
    public function remove(FoundsImage $photo, bool $flush = TRUE): void
    {
        $this->getEntityManager()->remove($photo);

        if($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllSortedByField(string $sortField = 'name', string $sortOrder = 'ASC'): Query
    {
        $allowedFields = ['name', 'nearestTown', 'createdAt'];

        if(!in_array($sortField, $allowedFields)) {
            throw new \InvalidArgumentException(sprintf('Invalid sort field: %s', $sortField));
        }

        return $this->createQueryBuilder('i')
                    ->orderBy("i.$sortField", $sortOrder)
                    ->getQuery();
    }

    public function findAllFiltered(string $sortField, string $sortOrder, string $search, ?User $user): Query
    {
        $qb = $this->createQueryBuilder('f');

        // Suchfilter
        if(!empty($search)) {
            $qb->andWhere('f.name LIKE :search OR f.nearestTown LIKE :search OR f.createdAt LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($user !== null) {
            $qb->andWhere('f.user = :user')
               ->setParameter('user', $user);
        }

        // Sortierung
        $qb->orderBy('f.' . $sortField, $sortOrder);

        return $qb->getQuery();
    }
}
