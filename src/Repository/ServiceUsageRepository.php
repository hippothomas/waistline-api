<?php

namespace App\Repository;

use App\Entity\ServiceUsage;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
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

	/**
	 * Get the service usage of a certain user based on today's date
	 * @param User $user User object we want to search
	 * @return ServiceUsage If the service usage for today exist we return it else we return a new service usage object.
	 */
	public function getDailyUsage(User $user): ServiceUsage
	{
		$date = new DateTime();
		$query = $this->createQueryBuilder('u')
					->andWhere('u.account = :user')
					->andWhere('u.date = :date')
					->setParameter('user', $user)
					->setParameter('date', $date->format('Y-m-d'))
					->setMaxResults(1)
					->getQuery();
		try {
			return $query->getSingleResult();
		} catch (ORMException) {
			$serviceUsage = new ServiceUsage();
			$serviceUsage->setAccount($user);
			$serviceUsage->setUsage(0);
			$serviceUsage->setDate($date);
			return $serviceUsage;
		}
	}

	/**
	 * Insert or Update the incremented daily usage
	 * @param ServiceUsage $serviceUsage
	 * @return void
	 */
	public function upsertDailyUsage(ServiceUsage $serviceUsage): void
	{
		$serviceUsage->setUsage($serviceUsage->getUsage()+1);
		$this->getEntityManager()->persist($serviceUsage);
		$this->getEntityManager()->flush();
	}
}
