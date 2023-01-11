<?php

namespace App\Tests\Api;

use App\Entity\Registration;
use App\Repository\UserRepository;
use App\Repository\LANPartyRepository;
use App\Repository\RegistrationRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class RegistrationTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/api/registrations');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/Registration",
			"@id" => "/api/registrations",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);

        $this->assertCount(20, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(Registration::class);
    }

    public function testCreateRegistration(): void
    {
		$client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $response = static::createClient()->request('POST', '/api/registrations', ['json' => [
			"roles" => ["PLAYER"],
			"status" => "registered",
			"account" => "/api/users/".$user->getId(),
			"lanParty" => "/api/lan_parties/".$lanparty->getId()
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"roles" => ["PLAYER"],
			"status" => "registered",
			"account" => [
				"@id" => "/api/users/".$user->getId()
			],
			"lanParty" => [
				"@id" => "/api/lan_parties/".$lanparty->getId()
			]
        ]);
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
    }

    public function testCreateInvalidRegistration(): void
    {
        $response = static::createClient()->request('POST', '/api/registrations', ['json' => [
			"roles" => ["PLAYER"],
			"status" => "registered",
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

    public function testGetSingleRegistration(): void
	{
		$client = static::createClient();
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$response = $client->request('GET', '/api/registrations/'.$registration->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
            '@type' => 'Registration',
			"roles" => $registration->getRoles(),
			"status" => $registration->getStatus(),
			"account" => [],
			"lanParty" => [],
        ]);
        $this->assertGreaterThan(0, count($response->toArray()['account']));
        $this->assertGreaterThan(0, count($response->toArray()['lanParty']));
        $this->assertMatchesResourceItemJsonSchema(Registration::class);
	}

	public function testUpdateRegistration(): void
    {
        $client = static::createClient();
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/registrations/'.$registration->getId(), ['json' => [
            'status' => 'waiting',
        ]]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/Registration",
			"@id" => "/api/registrations/".$registration->getId(),
            '@type' => 'Registration',
            'status' => 'waiting',
        ]);
    }

    public function testDeleteRegistration(): void
    {
        $client = static::createClient();
        $registrationRepository = static::getContainer()->get(RegistrationRepository::class);
        $registration = $registrationRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/registrations/'.$registration->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $registrationRepository->findOneBy(['id' => $registration->getId()])
        );
    }
}
