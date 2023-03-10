<?php

namespace App\Security\Voter;

use App\Entity\LANParty;
use App\Repository\RegistrationRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LANPartyVoter extends Voter
{
    private RegistrationRepository $repository;

	public function __construct(RegistrationRepository $repository)
	{
        $this->repository = $repository;
	}

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportsAttribute = in_array($attribute, ['LANPARTY_STAFF']);
        $supportsSubject = $subject instanceof LANParty;

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
            case 'LANPARTY_STAFF':
				$lan_party_id = $subject->getId();
				return $this->repository->isStaffInLAN($user->getId(), $lan_party_id);
                break;
        }

        return false;
    }
}
