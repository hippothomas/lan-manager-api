<?php

namespace App\Repository;

use App\Entity\Registration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 *
 * @method Registration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Registration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Registration[]    findAll()
 * @method Registration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    public function save(Registration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Registration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findStaff(int $id): array
    {
        return $this->createQueryBuilder('r')
			->innerJoin('App\Entity\LANParty', 'lp', 'WITH', 'lp.id = r.lanParty')
            ->andWhere('lp.id = :id')
            ->andWhere("r.roles LIKE '%\"STAFF\"%'")
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult()
        ;
    }

	public function findByRole(array $roles): array
	{
		$qb = $this->createQueryBuilder('r');
		$or = false;
		foreach ($roles as $key => $role) {
			if ($or) {
				$qb->orWhere('r.roles like :role' . $key)
					->setParameter('role' . $key, '%' . $role . '%');
			} else {
				$qb->where('r.roles like :role' . $key)
					->setParameter('role' . $key, '%' . $role . '%');
				$or = true;
			}
		}
		return $qb->getQuery()
				->getResult();
	}

	public function findByRoleAndLAN(array $roles, int $lanParty): array
	{
		$qb = $this->createQueryBuilder('r');
		$or = false;
		foreach ($roles as $key => $role) {
			if ($or) {
				$qb->orWhere('r.roles like :role' . $key)
					->setParameter('role' . $key, '%' . $role . '%');
			} else {
				$qb->where('r.roles like :role' . $key)
					->setParameter('role' . $key, '%' . $role . '%');
				$or = true;
			}
		}
		$qb->andWhere('r.lanParty = :lanparty')
			->setParameter('lanparty', $lanParty);
		return $qb->getQuery()
				->getResult();
	}

//    /**
//     * @return Registration[] Returns an array of Registration objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Registration
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
