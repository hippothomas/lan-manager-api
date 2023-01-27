<?php

namespace App\Tests\Api;

use App\Entity\Information;
use App\Repository\UserRepository;
use App\Repository\LANPartyRepository;
use App\Repository\InformationRepository;
use App\Repository\RegistrationRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class InformationTest extends ApiTestCase
{
	protected $user;

	protected function setUp(): void
    {
		$userRepository = static::getContainer()->get(UserRepository::class);
		$this->user 	= $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
	}

    public function testGetCollection(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);

		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findOneByAccount($this->user->getId());
		$lanparty = $registration->getLanParty();

        $response = $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/Information",
			"@id" => "/api/lan_parties/".$lanparty->getId()."/informations",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);
        $this->assertMatchesResourceCollectionJsonSchema(Information::class);
    }

    public function testGetCollectionAsUserNotRegistered(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);

		$lanPartyRepository = static::getContainer()->get(LANPartyRepository::class);
		$lanparty = $lanPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registrationRepository->removeRegistrationIfExist($this->user->getId(), $lanparty->getId());

        $response = $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations');
        $this->assertResponseStatusCodeSame(404);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testGetCollectionNotConnected(): void
    {
		$client = static::createClient();

		$lanPartyRepository = static::getContainer()->get(LANPartyRepository::class);
		$lanparty = $lanPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $response = $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations');
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testGetCollectionOfNonExistingLAN(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);

		$lanPartyRepository = static::getContainer()->get(LANPartyRepository::class);
		$lanparty = $lanPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $response = $client->request('GET', '/api/lan_parties/'.($lanparty->getId()+10).'/informations');
        $this->assertResponseStatusCodeSame(404);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testCreateInformation(): void
    {
		$client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();

        $response = $client->request('POST', '/api/lan_parties/'.$lanparty->getId().'/informations', ['json' => [
			"title" => "Tournaments",
            "content" => "There will be a lot of tournaments !"
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            "@context" => "/api/contexts/Information",
            "@type" => "Information",
			"title" => "Tournaments",
            "content" => "There will be a lot of tournaments !",
			"author" => [
                "/api/users/".$registration->getAccount()->getId()
            ],
            "lanParty" => "/api/lan_parties/".$lanparty->getId(),
        ]);
        $this->assertMatchesResourceItemJsonSchema(Information::class);
    }

    public function testCreateInformationAddingRandomUserAsAuthor(): void
    {
		$client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();

		$user2 = $registrationRepository->findByRoleAndLAN(["PLAYER"], $lanparty->getId())[0];
		$user2_id = $user2->getAccount()->getId();

        $response = $client->request('POST', '/api/lan_parties/'.$lanparty->getId().'/informations', ['json' => [
			"title" => "Tournaments",
            "content" => "There will be a lot of tournaments !",
			"author" => [
                "/api/users/".$registration->getAccount()->getId(),
                "/api/users/".$user2_id
            ],
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            "@context" => "/api/contexts/Information",
            "@type" => "Information",
			"title" => "Tournaments",
            "content" => "There will be a lot of tournaments !",
			"author" => [
                "/api/users/".$registration->getAccount()->getId()
            ],
            "lanParty" => "/api/lan_parties/".$lanparty->getId(),
        ]);
        $this->assertMatchesResourceItemJsonSchema(Information::class);
    }

    public function testCreateInvalidInformation(): void
    {
		$client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();

        $response = $client->request('POST', '/api/lan_parties/'.$lanparty->getId().'/informations', ['json' => [
            "content" => ["There will be a lot of tournaments !"]
		]]);
        $this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testCreateInformationNotConnected(): void
    {
		$client = static::createClient();

		$lanPartyRepository = static::getContainer()->get(LANPartyRepository::class);
		$lanparty = $lanPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $response = $client->request('POST', '/api/lan_parties/'.$lanparty->getId().'/informations', ['json' => [
			"title" => "Tournaments",
            "content" => "There will be a lot of tournaments !"
		]]);
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testGetSingleInformationAsUserRegistered(): void
	{
		$client = static::createClient();

        $informationRepository = static::getContainer()->get(InformationRepository::class);
        $information = $informationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$lanparty = $information->getLanParty();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRoleAndLAN(["PLAYER"], $lanparty->getId())[0];
		$client->loginUser($registration->getAccount());

		$authors = [];
		foreach ($information->getAuthor() as $a) {
			$authors[] = "/api/users/".$a->getId();
		}

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            "@context" => "/api/contexts/Information",
			"@type" => "Information",
			"title" => $information->getTitle(),
			"content" => $information->getContent(),
			"author" => $authors,
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
        ]);
        $this->assertMatchesResourceItemJsonSchema(Information::class);
	}

    public function testGetSingleInformationAsUserNotRegistered(): void
	{
		$client = static::createClient();
		$client->loginUser($this->user);

        $informationRepository = static::getContainer()->get(InformationRepository::class);
        $information = $informationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$lanparty = $information->getLanParty();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registrationRepository->removeRegistrationIfExist($this->user->getId(), $lanparty->getId());

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
        $this->assertResponseStatusCodeSame(404);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testGetSingleInformationNotConnected(): void
	{
		$client = static::createClient();

        $informationRepository = static::getContainer()->get(InformationRepository::class);
        $information = $informationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$lanparty = $information->getLanParty();

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testUpdateInformationAsStaff(): void
	{
		$client = static::createClient();

		// Log as a staff user
		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$response = $client->request('PUT', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId(), ['json' => [
			"title" => "updated title",
		]]);
		$this->assertResponseIsSuccessful();
		$this->assertJsonContains([
			"@context" => "/api/contexts/Information",
			"@type" => "Information",
			"title" => "updated title",
		]);
		$this->assertMatchesResourceItemJsonSchema(Information::class);
	}

	public function testUpdateInformationAsUser(): void
	{
		$client = static::createClient();

		// Log as a user
		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$response = $client->request('PUT', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId(), ['json' => [
			"title" => "updated title",
		]]);
		$this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testUpdateInformationNotConnected(): void
	{
		$client = static::createClient();

		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$response = $client->request('PUT', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId(), ['json' => [
			"title" => "updated title",
		]]);
		$this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
		$this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testDeleteInformationAsStaff(): void
	{
		$client = static::createClient();
        $informationRepository = static::getContainer()->get(InformationRepository::class);

		// Log as a staff user
		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$client->request('DELETE', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
		$this->assertResponseStatusCodeSame(204);
		$this->assertNull(
			$informationRepository->findOneBy(['id' => $information->getId()])
		);
	}

	public function testDeleteInformationAsUser(): void
	{
		$client = static::createClient();

		// Log as a user
		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$client->loginUser($registration->getAccount());
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$client->request('DELETE', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
        $this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testDeleteInformationNotConnected(): void
	{
		$client = static::createClient();

		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$lanparty = $registration->getLanParty();
		$information = $lanparty->getInformations()[0];

		$client->request('DELETE', '/api/lan_parties/'.$lanparty->getId().'/informations/'.$information->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}
}
