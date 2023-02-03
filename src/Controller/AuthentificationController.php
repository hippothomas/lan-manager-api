<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthentificationController extends AbstractController
{
	private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/api/token', methods: ['POST'], name: 'api_token')]
    public function index(Request $request): JsonResponse
    {
		$code = $request->get('code');
		$type = $request->get('type');

		if (!empty($code) && !empty($type)) {
			if ($type == "discord") {
				$endpoint = "https://discord.com/api/oauth2/token";
				$headers = [
					'Content-Type' => 'application/x-www-form-urlencoded'
				];
				$body = [
						'client_id' => $this->getParameter('oauth.discord.id'),
						'client_secret' => $this->getParameter('oauth.discord.secret'),
						'grant_type' => 'authorization_code',
						'code' => $code,
						'redirect_uri' => $this->getParameter('oauth.discord.url')
				];
				if (!empty($request->get('code_verifier'))) { $body["code_verifier"] = $request->get('code_verifier'); }
				$response = $this->client->request('POST', $endpoint, [
					'headers' => $headers,
					'body' => $body
				]);

				if ($response->getStatusCode(false) !== 200) {
					throw new HttpException(400, 'Your code might be expired... If the error persist contact the support.');
				}

				$content = $response->toArray(false);
				$response = [
					"source" => "discord",
					"access_token" => $content["access_token"],
					"expires_in" => $content["expires_in"],
					"refresh_token" => $content["refresh_token"]
				];

				return $this->json($response);
			} else {
				throw new HttpException(400, 'Bad Request');
			}
		} else {
			throw new HttpException(400, 'Bad Request');
		}
    }
}
