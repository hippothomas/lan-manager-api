<?php

namespace App\Tests\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

class UserTest extends ApiTestCase
{
    public function testGetCollection(): void
    {
		$client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$client->loginUser($user);

        $response = $client->request('GET', '/api/users');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"@context" => "/api/contexts/User",
			"@id" => "/api/users",
			"@type" => "hydra:Collection",
			"hydra:member" => []
		]);

        $this->assertCount(20, $response->toArray()['hydra:member']);
    }

    public function testGetCollectionNotConnected(): void
    {
        $response = static::createClient()->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }

    public function testGetMe(): void
    {
		$client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$client->loginUser($user);

        $response = $client->request('GET', '/api/users/@me');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
			"username" => $user->getUsername(),
			"roles" => $user->getRoles(),
			"registrations" => []
		]);
        $this->assertMatchesResourceItemJsonSchema(User::class);
    }

    public function testGetMeNotConnected(): void
    {
        $response = static::createClient()->request('GET', '/api/users/@me');
        $this->assertResponseStatusCodeSame(401);
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
		$client->loginUser($user);

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

    public function testGetSingleUserNotConnected(): void
	{
		$client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('GET', '/api/users/'.$user->getId());
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

	public function testUpdateUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$client->loginUser($user);

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

	public function testUpdateUserNotConnected(): void
	{
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

		$client->request('PUT', '/api/users/'.$user->getId(), ['json' => [
            'username' => 'goodplayer',
        ]]);
        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
	}

    public function testDeleteUser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);
		$client->loginUser($user);

        $client->request('DELETE', '/api/users/'.$user->getId());

        $this->assertResponseStatusCodeSame(204);
        $this->assertNull(
            $userRepository->findOneBy(['id' => $user->getId()])
        );
    }

    public function testDeleteUserNotConnected(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy([], ['id' => 'DESC'], 1, 0);

        $client->request('DELETE', '/api/users/'.$user->getId());

        $this->assertResponseStatusCodeSame(401);
		$this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
			"@context" => "/api/contexts/Error",
			"@type" => "hydra:Error",
			"hydra:title" => "An error occurred"
		]);
    }
}
