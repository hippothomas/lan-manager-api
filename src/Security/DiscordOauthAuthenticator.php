<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class DiscordOauthAuthenticator extends AbstractAuthenticator
{
	private $client;
	private $entityManager;

	public function __construct(GenericProvider $client, EntityManagerInterface $entityManager)
	{
		$this->client = $client;
		$this->entityManager = $entityManager;
	}

    public function supports(Request $request): ?bool
    {
		return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
		$accessToken = explode(' ', $request->headers->get('Authorization'))[1];
		if (null === $accessToken) {
			throw new AuthenticationException('No Authorization token provided');
		}

		return new SelfValidatingPassport(
            new UserBadge($accessToken, function ($accessToken) { return $this->getUser($accessToken); })
        );
    }

	public function getUser($accessToken): ?User
	{
		$credentials = new \League\OAuth2\Client\Token\AccessToken(['access_token' => $accessToken]);
		$discordUser = $this->client->getResourceOwner($credentials)->toArray();

		// If access token is not valid
		if (!empty($discordUser["message"])) {
			throw new AuthenticationException($discordUser["message"]);
		}

		$username = $discordUser["user"]["username"];

		$user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
		if ($user == null) {
			$discord_username = $discordUser["user"]["username"]."#".$discordUser["user"]["discriminator"];
			$discord_avatar   = "https://cdn.discordapp.com/avatars/".$discordUser["user"]["id"]."/".$discordUser["user"]["avatar"].".png";

			$user = (new User())->setUsername($username);
			$user->setDiscordId($discordUser["user"]["id"]);
			$user->setDiscordUsername($discord_username);
			$user->setAvatar($discord_avatar);
			$this->entityManager->persist($user);
			$this->entityManager->flush();
		}

		return $user;
	}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
		$data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        /*
         * If you would like this class to control what happens when an anonymous user accesses a
         * protected page (e.g. redirect to /login), uncomment this method and make this class
         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
         *
         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
         */

		$data = [
            'message' => "You need to be connected in order to access to endpoint."
        ];
		return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
