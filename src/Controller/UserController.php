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
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{
     /**
     * Cette méthode permet de récupérer (GET) l'ensemble des utilisateurs d'un client.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs liés à un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     *
     * @param UserRepository $productRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */


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


    /**
     * Cette méthode permet de récupérer (GET) le détail d'un utilisateur en fonction de son id.
     *
     * @OA\Tag(name="Users")
     * 
     * @param User $user
     * @param UserRepository $userRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */

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


    /**
     * Cette méthode permet de créer (POST) un nouvel utilisateur.
     * 
     * exemple à mettre dans body pour créer un nouvel utilisateur. "email" doit être unique.
     * {
     * "firstname": "prénom 10",
     * "lastname": "nom 10",
     * "email": "email11@g.com"
     * }
     *
     * @OA\RequestBody(@Model(type=User::class, groups={"createUser"}))
     * @OA\Response(
     *     response=201,
     *      description="Retourne le détail de l'utilisateur créé lié au client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Tag(name="Users")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */

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



    /**
     * Cette méthode supprime un utilisateur en fonction de son id. 
     * En cascade, les utilisateurs associés aux clients seront eux aussi supprimés. 
     * 
     * @OA\Tag(name="Users")
     * 
     * @param User $user
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */

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

