<?php

namespace App\Tests\Api;

use App\Entity\LANParty;
use App\Repository\UserRepository;
use App\Repository\LANPartyRepository;
use App\Repository\RegistrationRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class LANPartyTest extends ApiTestCase
{
	protected $user;

	protected function setUp(): void
    {
		$userRepository = static::getContainer()->get(UserRepository::class);
		$this->user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
	}

    public function testGetCollection(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);
        $response = $client->request('GET', '/api/lan_parties');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/lan_parties",
			"@id" => "/api/lan_parties",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);
        $this->assertMatchesResourceCollectionJsonSchema(LANParty::class);

		// If one of the LAN in the collection is private check that the user is registered to it
		$collection = json_decode($response->getContent())->{"hydra:member"};
		$registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		foreach ($collection as $lan) {
			if ($lan->private == true) {
				$isUserRegistered = $registrationRepository->isUserRegistered($this->user->getId(), $lan->id);
				$this->assertTrue($isUserRegistered);
			}
		}
    }

    public function testGetCollectionNotConnected(): void
    {
        $response = static::createClient()->request('GET', '/api/registrations');

        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testCreateLANParty(): void
    {
		$dateStart = date("c", strtotime("+ 10 days"));
		$dateEnd = date("c", strtotime("+ 13 days"));

		$client = static::createClient();
		$client->loginUser($this->user);
        $response = $client->request('POST', '/api/lan_parties', ['json' => [
			"name" => "Amazing LAN",
			"maxPlayers" => 25,
			"private" => true,
			"registrationOpen" => true,
			"location" => "Paris, France",
			"coverImage" => "https://picsum.photos/id/237/600/400",
			"website" => "https://amazinglan.com",
			"cost" => "23.99",
			"description" => "You should really come to my amazing LAN Party !",
			"dateStart" => $dateStart,
			"dateEnd" => $dateEnd
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
            '@type' => 'lan_parties',
			"name" => "Amazing LAN",
			"maxPlayers" => 25,
			"private" => true,
			"registrationOpen" => true,
			"location" => "Paris, France",
			"coverImage" => "https://picsum.photos/id/237/600/400",
			"website" => "https://amazinglan.com",
			"cost" => "23.99",
			"description" => "You should really come to my amazing LAN Party !",
			"dateStart" => $dateStart,
			"dateEnd" => $dateEnd,
			'registrations' => []
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
    }

    public function testCreateInvalidLANParty(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);
        $response = $client->request('POST', '/api/lan_parties', ['json' => [
			"maxPlayers" => '25'
		]]);
        $this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testCreateLANPartyNotConnected(): void
    {
		$dateStart = date("c", strtotime("+ 10 days"));
		$dateEnd = date("c", strtotime("+ 13 days"));

		$response = static::createClient()->request('POST', '/api/lan_parties', ['json' => [
			"name" => "Amazing LAN",
			"maxPlayers" => 25,
			"private" => true,
			"registrationOpen" => true,
			"location" => "Paris, France",
			"coverImage" => "https://picsum.photos/id/237/600/400",
			"website" => "https://amazinglan.com",
			"cost" => "23.99",
			"description" => "You should really come to my amazing LAN Party !",
			"dateStart" => $dateStart,
			"dateEnd" => $dateEnd
		]]);
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testGetSingleLANPartyRegistrationOpenPublic(): void
	{
		$client = static::createClient();
		$client->loginUser($this->user);
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(["registrationOpen" => true, "private" => false], ['id' => 'DESC'], 1, 0);

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
            '@type' => 'lan_parties',
			"name" => $lanparty->getName(),
			"maxPlayers" => $lanparty->getMaxPlayers(),
			"private" => $lanparty->isPrivate(),
			"registrationOpen" => $lanparty->isRegistrationOpen(),
			"location" => $lanparty->getLocation(),
			"coverImage" => $lanparty->getCoverImage(),
			"website" => $lanparty->getWebsite(),
			"cost" => $lanparty->getCost(),
			"description" => $lanparty->getDescription(),
			"dateStart" => $lanparty->getDateStart()->format("c"),
			"dateEnd" => $lanparty->getDateEnd()->format("c")
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
	}

    public function testGetSingleLANPartyRegistrationOpenPrivate(): void
	{
		$client = static::createClient();
		$client->loginUser($this->user);
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(["registrationOpen" => true, "private" => true], ['id' => 'DESC'], 1, 0);

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
            '@type' => 'lan_parties',
			"name" => $lanparty->getName(),
			"maxPlayers" => $lanparty->getMaxPlayers(),
			"private" => $lanparty->isPrivate(),
			"registrationOpen" => $lanparty->isRegistrationOpen(),
			"location" => $lanparty->getLocation(),
			"coverImage" => $lanparty->getCoverImage(),
			"website" => $lanparty->getWebsite(),
			"cost" => $lanparty->getCost(),
			"description" => $lanparty->getDescription(),
			"dateStart" => $lanparty->getDateStart()->format("c"),
			"dateEnd" => $lanparty->getDateEnd()->format("c")
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
	}

    public function testGetSingleLANPartyRegistrationClosedUserRegistered(): void
	{
		$client = static::createClient();

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(["registrationOpen" => false, "private" => true], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registration = $registrationRepository->findByRoleAndLAN(["PLAYER"], $lanparty->getId())[0];
		$client->loginUser($registration->getAccount());

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
            '@type' => 'lan_parties',
			"name" => $lanparty->getName(),
			"maxPlayers" => $lanparty->getMaxPlayers(),
			"private" => $lanparty->isPrivate(),
			"registrationOpen" => $lanparty->isRegistrationOpen(),
			"location" => $lanparty->getLocation(),
			"coverImage" => $lanparty->getCoverImage(),
			"website" => $lanparty->getWebsite(),
			"cost" => $lanparty->getCost(),
			"description" => $lanparty->getDescription(),
			"dateStart" => $lanparty->getDateStart()->format("c"),
			"dateEnd" => $lanparty->getDateEnd()->format("c")
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
	}

    public function testGetSingleLANPartyRegistrationClosedUserNotRegistered(): void
	{
		$client = static::createClient();
		$client->loginUser($this->user);
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(["registrationOpen" => false, "private" => true], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registrationRepository->removeRegistrationIfExist($this->user->getId(), $lanparty->getId());

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseStatusCodeSame(404);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testGetSingleLANPartyNotConnected(): void
	{
		$client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(["registrationOpen" => true, "private" => false], ['id' => 'DESC'], 1, 0);

        $client->request('GET', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testUpdateLANPartyAsStaff(): void
    {
        $client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());

		$client->request('PUT', '/api/lan_parties/'.$registration->getLanParty()->getId(), ['json' => [
            'name' => 'updated name',
        ]]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
			"@id" => "/api/lan_parties/".$registration->getLanParty()->getId(),
            '@type' => 'lan_parties',
            'name' => 'updated name',
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
    }

	public function testUpdateLANPartyAsUser(): void
    {
        $client = static::createClient();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$client->loginUser($registration->getAccount());

		$client->request('PUT', '/api/lan_parties/'.$registration->getLanParty()->getId(), ['json' => [
            'name' => 'updated name',
        ]]);
        $this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

	public function testUpdateLANPartyNotConnected(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/lan_parties/'.$lanparty->getId(), ['json' => [
            'name' => 'updated name',
        ]]);
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testDeleteLANPartyAsStaff(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());

        $client->request('DELETE', '/api/lan_parties/'.$registration->getLanParty()->getId());
        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $LANPartyRepository->findOneBy(['id' => $registration->getLanParty()->getId()])
        );
    }

    public function testDeleteLANPartyAsUser(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["PLAYER"])[0];
		$client->loginUser($registration->getAccount());

        $client->request('DELETE', '/api/lan_parties/'.$registration->getLanParty()->getId());
        $this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testDeleteLANPartyNotConnected(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/lan_parties/'.$lanparty->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }
}
