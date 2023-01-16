<?php

namespace App\Doctrine;

use App\Entity\Registration;
use Doctrine\ORM\QueryBuilder;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;

final class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
	private $security;

    private EntityManagerInterface $entityManager;

	public function __construct(Security $security, EntityManagerInterface $entityManager)
	{
		$this->security = $security;
        $this->entityManager = $entityManager;
	}

	public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
	{
		$this->addWhere($queryBuilder, $resourceClass);
	}

	public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
	{
		$this->addWhere($queryBuilder, $resourceClass);
	}

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
	{
		if (Registration::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN') || null === $user = $this->security->getUser()) {
			return;
		}

		// Filter to return only Registrations from LANParty where the user is registered
		$rootAlias = $queryBuilder->getRootAliases()[0];
		$queryBuilder->andWhere(
			$queryBuilder->expr()->in(
				sprintf('%s.lanParty', $rootAlias),
				$this->entityManager->createQueryBuilder()
					->select('IDENTITY(r.lanParty)')
					->from('App\Entity\Registration', 'r')
					->where('r.account = :current_user')
					->getDQL()
			)
		);
		$queryBuilder->setParameter('current_user', $user->getId());
	}
}
