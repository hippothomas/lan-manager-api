<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class CreateRegistrationController extends AbstractController
{
	protected $repository;

	public function __construct(RegistrationRepository $repository)
	{
		$this->repository = $repository;
	}

    public function __invoke(Registration $registration): Registration
    {
		// if the user has not been set or has been removed by securityPostDenormalize
		if ($registration->getAccount() == null) {
			$user = $this->getUser();
			if (!$user instanceof UserInterface) { throw new HttpException(500, 'Internal error'); }

			$registration->setAccount($user);
		}

		// check if LANParty is not closed to registrations
		if ($registration->getLanParty()->isRegistrationOpen() == false) {
			throw new HttpException(422, 'This LAN Party is closed to registrations !');
		}

		// check is user is not already registered to this LAN
        $result = $this->repository->findOneBy(['account' => $registration->getAccount(), 'lanParty' => $registration->getLanParty()], ['id' => 'DESC'], 1, 0);
		if ($result !== null) {
			throw new HttpException(422, 'This user is already registered in this LANParty !');
		}

        return $registration;
    }
}
