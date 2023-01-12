<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class UserTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
        $response = static::createClient()->request('GET', '/api/users');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/User",
			"@id" => "/api/users",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);

        $this->assertCount(20, $response->toArray()['hydra:member']);
    }

    public function testCreateUser(): void
    {
        $response = static::createClient()->request('POST', '/api/users', ['json' => [
			"username" => "bestplayer"
		]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => "/api/contexts/User",
            '@type' => 'User',
			"username" => "bestplayer",
			'roles' => [],
			'registrations' => []
        ]);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testCreateInvalidUser(): void
    {
        $response = static::createClient()->request('POST', '/api/users', ['json' => [
			"username" => 3
		]]);
        $this->assertResponseStatusCodeSame(400);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testGetSingleUser(): void
	{
		$client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('GET', '/api/users/'.$user->getId());
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/User",
            '@type' => 'User',
			"username" => $user->getUsername(),
			"roles" => $user->getRoles(),
			"registrations" => []
        ]);
	}

	public function testUpdateUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/users/'.$user->getId(), ['json' => [
            'username' => 'goodplayer',
        ]]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => "/api/contexts/User",
			"@id" => "/api/users/".$user->getId(),
            '@type' => 'User',
            'username' => 'goodplayer',
        ]);
    }

    public function testDeleteUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/users/'.$user->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $userRepository->findOneBy(['id' => $user->getId()])
        );
    }
}
