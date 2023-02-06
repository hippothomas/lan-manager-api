<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;

final class UserDecorator implements OpenApiFactoryInterface
{
	public function __construct(
		private OpenApiFactoryInterface $decorated
	) {}

	public function __invoke(array $context = []): OpenApi
	{
		$openApi = ($this->decorated)($context);

		$pathItem = new Model\PathItem(
			ref: 'User',
			get: new Model\Operation(
				operationId: 'current_user',
				tags: ['User'],
				responses: [
					'200' => [
						'description' => 'User resource',
						'content' => [
							'application/json' => [
								'schema' => [
									'$ref' => '#/components/schemas/User',
								],
							],
						],
					]
				],
				summary: 'Retrieves current User.',
			),
		);
		$openApi->getPaths()->addPath('/api/users/@me', $pathItem);

		return $openApi;
	}
}
