<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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


    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder à la liste des utilisateurs")]
    public function getDetailUser(User $user, SerializerInterface $serializer, UserRepository $userRepository): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $userRepository->findOneBy(['id' => $user->getId(), 'client' => $client]);

        if ($user){
            $jsonUser = $serializer->serialize($user, 'json',['groups' => 'getUsers']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_NOT_FOUND);

    }


    #[Route('/api/users', name:"createUser", methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour créer un utilisateur")]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ClientRepository $clientRepository): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setClient($client);

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
