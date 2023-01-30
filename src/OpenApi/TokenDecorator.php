<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;

final class TokenDecorator implements OpenApiFactoryInterface
{
	public function __construct(
		private OpenApiFactoryInterface $decorated
	) {}

	public function __invoke(array $context = []): OpenApi
	{
		$openApi = ($this->decorated)($context);
		$schemas = $openApi->getComponents()->getSchemas();

		$schemas['Token'] = new \ArrayObject([
			'type' => 'object',
			'properties' => [
				'source' => [
					'type' => 'string',
					'readOnly' => true,
				],
				'access_token' => [
					'type' => 'string',
					'readOnly' => true,
				],
				'expires_in' => [
					'type' => 'integer',
					'readOnly' => true,
				],
			],
		]);
		$schemas['Credentials'] = new \ArrayObject([
			'type' => 'object',
			'properties' => [
				'type' => [
					'type' => 'string',
					'example' => 'discord',
				],
				'code' => [
					'type' => 'string'
				],
			],
		]);

		$schemas = $openApi->getComponents()->getSecuritySchemes() ?? [];

		$pathItem = new Model\PathItem(
			ref: 'Token',
			post: new Model\Operation(
				operationId: 'postCredentialsItem',
				tags: ['Token'],
				responses: [
					'200' => [
						'description' => 'Get an Authentification token',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/Token',
								],
							],
						],
					],
				],
				summary: 'Get a token to login.',
				requestBody: new Model\RequestBody(
					description: 'Generate new Token',
					content: new \ArrayObject([
						'application/json' => [
							'schema' => [
								'$ref' => '#/components/schemas/Credentials',
							],
						],
					]),
				),
				security: [],
			),
		);
		$openApi->getPaths()->addPath('/api/token', $pathItem);

		return $openApi;
	}
}
