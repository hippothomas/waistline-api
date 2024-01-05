<?php

namespace App\Repository;

use App\Entity\ServiceUsage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceUsage>
 *
 * @method ServiceUsage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiceUsage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiceUsage[]    findAll()
 * @method ServiceUsage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceUsage::class);
    }
}
