<?php

namespace App\Security\Voter;

use App\Entity\Information;
use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RolesVoter extends Voter
{
    private RegistrationRepository $repository;

	public function __construct(RegistrationRepository $repository)
	{
        $this->repository = $repository;
	}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportsAttribute = in_array($attribute, ['STAFF', 'PLAYER']);
        $supportsSubject = $subject instanceof Registration
							|| $subject instanceof Information;

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
            case 'STAFF':
				$lan_party_id = $subject->getLanParty()->getId();
				return $this->repository->isStaffInLAN($user->getId(), $lan_party_id);
                break;
			case 'PLAYER':
				$lan_party_id = $subject->getLanParty()->getId();
				return $this->repository->isUserRegistered($user->getId(), $lan_party_id);
				break;
        }

        return false;
    }
}
