<?php

namespace App\Acme\AdminBundle\Repository;

use App\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Ankieta|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ankieta|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ankieta[]    findAll()
 * @method Ankieta[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Survey::class);
    }
}
