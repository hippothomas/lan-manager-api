<?php

namespace App\Tests\Api;

use App\Entity\LANParty;
use App\Repository\LANPartyRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class LANPartyTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/api/lan_parties');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/lan_parties",
			"@id" => "/api/lan_parties",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);

        $this->assertCount(3, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(LANParty::class);
    }

    public function testCreateLANParty(): void
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
			"cost" => 23.99,
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
			"cost" => 23.99,
			"description" => "You should really come to my amazing LAN Party !",
			"dateStart" => $dateStart,
			"dateEnd" => $dateEnd,
			'registrations' => []
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
    }

    public function testCreateInvalidLANParty(): void
    {
        $response = static::createClient()->request('POST', '/api/lan_parties', ['json' => [
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

    public function testGetSingleLANParty(): void
	{
		$client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

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

	public function testUpdateLANParty(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/lan_parties/'.$lanparty->getId(), ['json' => [
            'name' => 'updated name',
        ]]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/lan_parties",
			"@id" => "/api/lan_parties/".$lanparty->getId(),
            '@type' => 'lan_parties',
            'name' => 'updated name',
        ]);
        $this->assertMatchesResourceItemJsonSchema(LANParty::class);
    }

    public function testDeleteLANParty(): void
    {
        $client = static::createClient();
        $LANPartyRepository = static::getContainer()->get(LANPartyRepository::class);
        $lanparty = $LANPartyRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/lan_parties/'.$lanparty->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $LANPartyRepository->findOneBy(['id' => $lanparty->getId()])
        );
    }
}
