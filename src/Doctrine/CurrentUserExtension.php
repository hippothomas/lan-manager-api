<?php

namespace App\Doctrine;

use App\Entity\LANParty;
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
		$this->addWhere($queryBuilder, $resourceClass, "collection");
	}

	public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
	{
		$this->addWhere($queryBuilder, $resourceClass, "item");
	}

	private function addWhere(QueryBuilder $queryBuilder, string $resourceClass, string $type): void
	{
		if ($this->security->isGranted('ROLE_ADMIN') || null === $user = $this->security->getUser()) {
			return;
		}

		if (Registration::class == $resourceClass) {
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

		if (LANParty::class == $resourceClass) {
			// Filter to return only Lan Party that is public and open to registration or thoses where the user is already registered to
			$rootAlias = $queryBuilder->getRootAliases()[0];
			$cond = "";
			// Don't show the privates Lan in the collection
			if ($type == "collection") { $cond = sprintf('%s.private', $rootAlias).' = false AND '; }
			// Retrieve all Lan to which the user has registered
			$registrations = $this->entityManager->createQueryBuilder()
									->select('IDENTITY(r.lanParty)')
									->from('App\Entity\Registration', 'r')
									->where('r.account = :current_user')
									->getDQL();
			$queryBuilder->andWhere($cond.sprintf('%s.registrationOpen', $rootAlias).' = true OR '.sprintf('%s.id', $rootAlias).' IN ('.$registrations.')');
			$queryBuilder->setParameter('current_user', $user->getId());
		}
	}
}
