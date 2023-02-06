<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsController]
class CurrentUserController extends AbstractController
{
    public function userInformations(TokenStorageInterface $tokenStorage, SerializerInterface $serializer): JsonResponse
    {
		$user = $tokenStorage->getToken()->getUser();
		if (!$user instanceof UserInterface) { throw new HttpException(401, 'Unauthorized'); }

        $json = $serializer->serialize($user, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
