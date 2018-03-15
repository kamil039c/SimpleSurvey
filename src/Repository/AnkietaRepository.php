<?php

namespace App\Repository;

use App\Entity\Ankieta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Ankieta|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ankieta|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ankieta[]    findAll()
 * @method Ankieta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnkietaRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Ankieta::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('a')
            ->where('a.something = :value')->setParameter('value', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
