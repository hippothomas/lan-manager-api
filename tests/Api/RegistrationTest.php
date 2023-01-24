<?php

namespace App\Tests\Api;

use App\Entity\Registration;
use App\Repository\UserRepository;
use App\Repository\LANPartyRepository;
use App\Repository\RegistrationRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class RegistrationTest extends ApiTestCase
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
        $response = $client->request('GET', '/api/registrations');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/Registration",
			"@id" => "/api/registrations",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);
		$this->assertGreaterThan(0, count($response->toArray()['hydra:member']));
        $this->assertMatchesResourceCollectionJsonSchema(Registration::class);
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

    public function testCreateRegistration(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(['registrationOpen' => true], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registrationRepository->removeRegistrationIfExist($this->user->getId(), $lanparty->getId());

        $response = $client->request('POST', '/api/registrations', ['json' => [
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"roles" => ["PLAYER"],
			"status" => "registered",
			"account" => "/api/users/".$this->user->getId(),
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
		]);
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
    }

    public function testCreateRegistrationByStaffUser(): void
    {
		$client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());

		// Retrieve random user
		$userRepository = static::getContainer()->get(UserRepository::class);
		$user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$registrationRepository->removeRegistrationIfExist($user->getId(), $registration->getLanParty()->getId());

        $response = $client->request('POST', '/api/registrations', ['json' => [
			"account" => "/api/users/".$user->getId(),
			"lanParty" => "/api/lan_parties/".$registration->getLanParty()->getId(),
			"roles" => ["VISITOR"],
			"status" => "waiting"
		]]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"roles" => ["VISITOR"],
			"status" => "waiting",
			"account" => "/api/users/".$user->getId(),
			"lanParty" => "/api/lan_parties/".$registration->getLanParty()->getId()
		]);
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
    }

    public function testCreateRegistrationToLanWithClosedRegistrations(): void
    {
		$client = static::createClient();
		$client->loginUser($this->user);

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(['registrationOpen' => false], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
		$registrationRepository->removeRegistrationIfExist($this->user->getId(), $lanparty->getId());

        $response = $client->request('POST', '/api/registrations', ['json' => [
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
		]]);

        $this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred",
			"hydra:description" => 'Item not found for "/api/lan_parties/'.$lanparty->getId().'".'
		]);
    }

    public function testCreateRegistrationWithUserAlreadyRegistered(): void
    {
		$client = static::createClient();

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(['registrationOpen' => true], ['id' => 'DESC'], 1, 0);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy(['lanParty' => $lanparty], ['id' => 'DESC'], 1, 0);

		$client->loginUser($registration->getAccount());

        $response = $client->request('POST', '/api/registrations', ['json' => [
			"lanParty" => "/api/lan_parties/".$registration->getLanParty()->getId()
		]]);

        $this->assertResponseStatusCodeSame(422);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testCreateInvalidRegistration(): void
    {
        $client = static::createClient();
		$client->loginUser($this->user);

		$response = $client->request('POST', '/api/registrations', ['json' => [
			"account" => "/api/users/0"
		]]);
        $this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testCreateRegistrationNotConnected(): void
    {
		$client = static::createClient();

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy(['registrationOpen' => true], ['id' => 'DESC'], 1, 0);

        $response = $client->request('POST', '/api/registrations', ['json' => [
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
		]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testGetSingleRegistration(): void
	{
		$client = static::createClient();
		$client->loginUser($this->user);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy(["account" => $this->user->getId()], ['id' => 'DESC'], 1, 0);

		$response = $client->request('GET', '/api/registrations/'.$registration->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"roles" => $registration->getRoles(),
			"status" => $registration->getStatus(),
			"account" => '/api/users/'.$this->user->getId(),
			"lanParty" => '/api/lan_parties/'.$registration->getLanParty()->getId(),
        ]);
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
	}

    public function testGetSingleRegistrationNotConnected(): void
	{
		$client = static::createClient();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$response = $client->request('GET', '/api/registrations/'.$registration->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testUpdateRegistrationByStaff(): void
    {
        $client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());

		// Get one player from the same LANParty
        $result = $registrationRepository->findByRoleAndLAN(["PLAYER"], $registration->getLanParty()->getId())[0];

		$client->request('PUT', '/api/registrations/'.$result->getId(), ['json' => [
            'status' => 'waiting',
        ]]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"status" => "waiting",
			"account" => "/api/users/".$result->getAccount()->getId(),
		]);
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
    }

	public function testUpdateRegistrationByUser(): void
    {
        $client = static::createClient();
		$client->loginUser($this->user);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy(["account" => $this->user->getId()], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/registrations/'.$registration->getId(), ['json' => [
            'roles' => ["STAFF"],
        ]]);
        $this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

	public function testUpdateRegistrationNotConnected(): void
    {
        $client = static::createClient();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/registrations/'.$registration->getId(), ['json' => [
            'roles' => ["STAFF"],
        ]]);
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

	public function testDeleteRegistration(): void
    {
        $client = static::createClient();
		$client->loginUser($this->user);

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy(["account" => $this->user], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/registrations/'.$registration->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $registrationRepository->findOneBy(['id' => $registration->getId()])
        );
    }

	public function testDeleteRegistrationByStaff(): void
    {
        $client = static::createClient();

		// Log as a staff user
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findByRole(["STAFF"])[0];
		$client->loginUser($registration->getAccount());

		// Get one player from the same LANParty
        $result = $registrationRepository->findByRoleAndLAN(["PLAYER"], $registration->getLanParty()->getId())[0];

        $client->request('DELETE', '/api/registrations/'.$result->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $registrationRepository->findOneBy(['id' => $result->getId()])
        );
    }

	public function testDeleteRegistrationByOtherUser(): void
    {
        $client = static::createClient();
		$client->loginUser($this->user);

		// Get one of his registrations
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy(["account" => $this->user->getId()], ['id' => 'DESC'], 1, 0);

		// Get one player from the same LANParty
        $result = $registrationRepository->findByRoleAndLAN(["PLAYER"], $registration->getLanParty()->getId())[0];

        $client->request('DELETE', '/api/registrations/'.$result->getId());

        $this->assertResponseStatusCodeSame(403);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testDeleteRegistrationNotConnected(): void
    {
        $client = static::createClient();

        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/registrations/'.$registration->getId());

        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }
}
