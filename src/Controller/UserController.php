<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder à la liste des utilisateurs")]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $userList = $userRepository->findUsersByClient($client);

        $jsonProductList = $serializer->serialize($userList, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }
}
