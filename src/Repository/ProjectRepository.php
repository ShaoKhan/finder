<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.users', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Project
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findByUuid(string $uuid): ?Project
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }
}
