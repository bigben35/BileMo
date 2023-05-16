<?php

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder à la liste des utilisateurs")]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer, PaginatorInterface $paginator, Request $request, LoggerInterface $logger, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        
        //pagination
    $page = $request->query->getInt('page', 1); // Numéro de page par défaut
    $limit = $request->query->getInt('limit', 10); // Nombre d'utilisateurs par page par défaut
    $idCache = "getAllUsers-" . $page . "-" . $limit;
    $logger->debug('Cache key: '.$idCache);
    $userList = $cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $page, $limit, $logger, $client) {
        // echo "L'élément n'est pas encore en cache !\n";
        $logger->warning("L'élément n'est pas encore en cache !\n");
        $item->tag("usersCache");
        $userList = $userRepository->findUsersByClient($client);
        $logger->info("Récupération des utilisateurs depuis la base de données.");
        return $userList;
    });
    
    //pagination
    $pagination = $paginator->paginate(
    $userList,/* query NOT result */
    $page,/*page number*/
    $limit/*limit per page*/
    );

    //pagination
    $currentPage = $pagination->getCurrentPageNumber();
    $lastPage = $pagination->getTotalItemCount() > 0 ? ceil($pagination->getTotalItemCount() / $pagination->getItemNumberPerPage()) : 1;

    // Vérification que la page demandée existe
    if ($currentPage > $lastPage) {
        $logger->warning("La page demandée n'existe pas");
        return new JsonResponse(['message' => "La page demandée n'existe pas"], Response::HTTP_NOT_FOUND);
    }

    $jsonProductList = $serializer->serialize($pagination, 'json', ['groups' => 'getUsers']);
    return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }


    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder à l'utilisateur demandé")]
    public function getDetailUser(User $user, SerializerInterface $serializer, UserRepository $userRepository): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $userRepository->findOneBy(['id' => $user->getId(), 'client' => $client]);

        if ($user){
            $jsonUser = $serializer->serialize($user, 'json',['groups' => 'getUsers']);
            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_FORBIDDEN);

    }



    #[Route('/api/users', name:"createUser", methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour créer un utilisateur")]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse
    {
        $client = $this->getUser(); // Récupère le client connecté
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setClient($client);

        // On vérifie les erreurs
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        
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

