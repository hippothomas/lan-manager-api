<?php

namespace App\Controller;

use App\Entity\Registration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsController]
class CreateRegistrationController extends AbstractController
{
    public function __invoke(Registration $registration): Registration
    {
		// if the user has not been set or has been removed by securityPostDenormalize
		if ($registration->getAccount() == null) {
			$user = $this->getUser();
			if (!$user instanceof UserInterface) { throw new HttpException(500, 'Internal error'); }

			$registration->setAccount($user);
		}

        return $registration;
    }
}
