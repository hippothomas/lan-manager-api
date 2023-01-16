<?php

namespace App\Security\Voter;

use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RegistrationVoter extends Voter
{
    private RegistrationRepository $repository;

	public function __construct(RegistrationRepository $repository)
	{
        $this->repository = $repository;
	}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportsAttribute = in_array($attribute, ['REGISTRATION_STAFF']);
        $supportsSubject = $subject instanceof Registration;

        return $supportsAttribute && $supportsSubject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

		switch ($attribute) {
            case 'REGISTRATION_STAFF':
				$lan_party_id = $subject->getLanParty()->getId();
				$staff_registrations = $this->repository->findStaff($lan_party_id);

				foreach ($staff_registrations as $staff) {
					if ($staff->getAccount() == $user) { return true; }
				}
                break;
        }

        return false;
    }
}
