<?php

namespace App\Controller;

use App\Entity\Information;
use App\Repository\LANPartyRepository;
use App\Repository\InformationRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class UpsertInformationController extends AbstractController
{
	protected $repository;
	protected $reg_repository;
	protected $lan_repository;

	public function __construct(InformationRepository $repository, RegistrationRepository $reg_repository, LANPartyRepository $lan_repository)
	{
		$this->repository 	  = $repository;
		$this->reg_repository = $reg_repository;
		$this->lan_repository = $lan_repository;
	}

    public function __invoke(Information $information, Request $request): Information
    {
		$user = $this->getUser();
		if (!$user instanceof UserInterface) { throw new HttpException(401, 'Unauthorized'); }

		$method = $request->getMethod();
		$lan_party_id = $request->attributes->get("lanId");

		// check if the user is in LAN's Staff
		if (!$this->reg_repository->isStaffInLAN($user->getId(), $lan_party_id)) {
			throw new HttpException(403, 'Forbidden');
		}

		// in every case we reset the LAN to be sure there is no issue
		$lan_party = $this->lan_repository->findOneById($lan_party_id);
		$information->setLanParty($lan_party);

		// check if every author is in Staff, to prevent users to add random user
		$authors = $information->getAuthor();
		foreach ($authors as $a) {
			if (!$this->reg_repository->isStaffInLAN($a->getId(), $lan_party_id)) {
				$information->removeAuthor($a);
			}
		}
		if (!$information->getAuthor()->contains($user)) {
			$information->addAuthor($user);
		}

		return $information;
    }
}
