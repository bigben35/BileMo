<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Repository\UserRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonProductList = $serializer->serialize($userList, 'json', $context);
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }


    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder à l'utilisateur demandé")]
    public function getDetailUser(User $user, SerializerInterface $serializer, UserRepository $userRepository): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $userRepository->findOneBy(['id' => $user->getId(), 'client' => $client]);

        if ($user){
            $context = SerializationContext::create()->setGroups(['getUsers', 'getClients']);
            $jsonUser = $serializer->serialize($user, 'json', $context);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse("Vous n'avez pas l'autorisation pour voir cet utilisateur", Response::HTTP_FORBIDDEN);

    }



    #[Route('/api/users', name:"createUser", methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour créer un utilisateur")]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setClient($client);
        $createdAt = new DateTimeImmutable();
        $user->setCreatedAt($createdAt);

        // On vérifie les erreurs
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers', 'getClients']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }



    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    public function deleteUser(User $user, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse 
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $userRepository->findOneBy(['id' => $user->getId(), 'client' => $client]);
        if (null === $user) {
            return new JsonResponse('Vous n\'êtes pas autorisé à supprimer cet utilisateur.', Response::HTTP_FORBIDDEN);
        }

        // Vérifier si le client est lié à l'utilisateur
        // if ($user->getClient() !== $client) {
        // return new JsonResponse('Vous n\'êtes pas autorisé à supprimer cet utilisateur.', Response::HTTP_FORBIDDEN);
        // }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
    }

