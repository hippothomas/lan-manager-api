<?php

namespace App\EventSubscriber;

use App\Entity\LANParty;
use App\Entity\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LANPartySubscriber implements EventSubscriberInterface
{
    private $tokenStorage;
    private $em;

	public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $em) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => [
                ['createLANParty', EventPriorities::POST_WRITE],
            ],
        ];
    }

    public function createLANParty(ViewEvent $event)
    {
		$lan_party = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$lan_party instanceof LANParty || Request::METHOD_POST !== $method) {
            return;
        }
		if (!$token = $this->tokenStorage->getToken()) {
            return;
        }
        if (!$user = $token->getUser()) {
            return;
        }

		// Create registration as staff for user
		$registration = new Registration();
		$registration->setAccount($user);
		$registration->setLanParty($lan_party);
		$registration->setRoles(["STAFF"]);
		$registration->getStatus("registered");

		$this->em->persist($registration);
		$this->em->flush();
    }
}
