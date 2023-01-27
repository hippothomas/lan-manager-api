<?php

namespace App\Controller;

use App\Entity\Information;
use App\Repository\LANPartyRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class LANPartyCollectionController extends AbstractController
{
	protected $lan_repository;
	protected $reg_repository;

	public function __construct(LANPartyRepository $lan_repository, RegistrationRepository $reg_repository)
	{
		$this->lan_repository = $lan_repository;
		$this->reg_repository = $reg_repository;
	}

    public function __invoke(PaginatorInterface $data, Request $request): PaginatorInterface
    {
		// check if user is connected
		$user = $this->getUser();
		if (!$user instanceof UserInterface) { throw new HttpException(401, 'Unauthorized'); }

		// check if the lan party exist
		$lanId  = $request->attributes->get("lanId");
		$lan_party = $this->lan_repository->findOneById($lanId);
		if (empty($lan_party)) { throw new HttpException(404, 'Not Found'); }

		// check if user is registered to the lan
		if (!$this->reg_repository->isUserRegistered($this->getUser()->getId(), $lanId)) {
			throw new HttpException(404, 'Not Found');
		}

		return $data;
    }
}
