<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

#[Route('/api', name: 'api_')]
#[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour l'accès aux clients")] //à modifier si je crée un role_admin (compte d'un développeur BileMo par ex)
class ClientController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer (GET) l'ensemble des clients.
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des clients",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClients"}))
     *     )
     * )
     * @OA\Tag(name="Clients")
     *
     * @param ClientRepository $clientRepositoryry
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */

    //endpoint to display all clients
    #[Route('/clients', name: 'clients', methods: ['GET'])]
    public function getAllClients(ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
    {

        $clientList = $clientRepository->findAll();

        $context = SerializationContext::create()->setGroups(['getClients']);
        $jsonClientList = $serializer->serialize($clientList, 'json', $context);
        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }


    /**
     * Cette route permet (GET) de récupérer un client en détail grâce à son ID.
     *
     * @OA\Tag(name="Clients")
     * 
     * @param Client $client
     * @param ClientRepository $clientRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[Route('/clients/{id}', name: 'detailClient', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder au client demandé")]
    public function getDetailClient(Client $client, SerializerInterface $serializer, ClientRepository $clientRepository): JsonResponse
    {
        //$client = $this->getUser(); // Récupère le client connecté
        $authenticatedUser = $this->getUser();

    // Vérifier si l'utilisateur authentifié est le propriétaire du compte
    if ($authenticatedUser->getId() !== $client->getId()) {
        return new JsonResponse("Vous n'avez pas l'autorisation pour accéder à ce client", Response::HTTP_FORBIDDEN);
    }
        $client = $clientRepository->findOneBy(['id' => $client->getId()]);

        if ($client){
            $context = SerializationContext::create()->setGroups(['getClients']);
            $jsonClient = $serializer->serialize($client, 'json',$context);
            return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_FORBIDDEN);

    }

    /**
     * Cette méthode permet de créer (POST) un nouveau client.
     * 
     * exemple à mettre dans body pour créer un nouveau client. "email", "company_name" et "siren" doivent être uniques.
     * {
     *  "email": "nomcompany2@g.com",
     *  "password": "password",
     *  "company_name": "nom de l'entreprise2",
     *  "siren": "1234656462"
     * }
     *
     * @OA\RequestBody(@Model(type=Client::class, groups={"createClient"}))
     * @OA\Response(
     *     response=201,
     *      description="Retourne le détail du client créé",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Client::class, groups={"getClients"}))
     *     )
     * )
     * @OA\Tag(name="Clients")
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/clients', name: 'createClient', methods: ['POST'])]
    // #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour l'accès aux clients")] //à modifier si je crée un role_admin (compte d'un développeur BileMo par ex)
    public function createClient(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passworhasher): JsonResponse
    {
        // $client = $this->getUser(); // Récupère le client connecté
        $client = $serializer->deserialize($request->getContent(), Client::class, 'json');

        // Encodage du mot de passe du client
        $hashPassword = $passworhasher->hashPassword($client, $client->getPassword());
        $client->setPassword($hashPassword);

        // Attribution du rôle ROLE_USER
        $client->setRoles(['ROLE_USER']);

        // On vérifie les erreurs
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($client);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getClients']);
        $jsonClient = $serializer->serialize($client, 'json', $context);
        
        $location = $urlGenerator->generate('detailClient', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true);
    }


    /**
     * Cette méthode permet de mettre à jour ('PUT', 'PATCH') un client si c'est bien le client lui-même qui se modifie. 
     * Exemple de données :
     * {
     *  "email": "john.doe1@g.com",
     *  "password": "password",
     *  "company_name": "Example Inc.1",
     *  "siren": "123456789005"
     * }
     * 
     *  
     * @OA\Tag(name="Clients")
     * 
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param UserPasswordHasherInterface $passworhasher
     * @return JsonResponse
     */

    #[Route('/clients/{id}', name: 'updateClient', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour mettre à jour le client")]
    public function updateClient(Request $request, Client $client, SerializerInterface $serializer, EntityManagerInterface $em, ValidatorInterface $validator, UserPasswordHasherInterface $passworhasher): JsonResponse
    {
        // Vérifier que le client connecté correspond à l'ID du client à modifier
        $connectedClient = $this->getUser();
        
        if (!$connectedClient instanceof Client) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }
        
        if ($connectedClient->getId() !== $client->getId()) {
            return new JsonResponse('Vous n\'êtes pas autorisé à modifier ce client.', Response::HTTP_FORBIDDEN);
        }

        // Modifier le client avec les données envoyées dans la requête
        $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
        $hashPassword = $passworhasher->hashPassword($newClient, $newClient->getPassword());
        $client->setPassword($hashPassword);
        $client->setEmail($newClient->getEmail());
        $client->setCompanyName($newClient->getCompanyName());
        $client->setSiren($newClient->getSiren());

        // On vérifie les erreurs
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($client);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getClients']);
        $jsonClient = $serializer->serialize($client, 'json', $context);

        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }


    /**
     * Cette méthode supprime ('DELETE') un client en fonction de son id. 
     * En cascade, les utilisateurs associés aux clients seront aux aussi supprimés. 
     *
     *  
     * @OA\Tag(name="Clients")
     * 
     * @param Client $client
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */

    #[Route('/clients/{id}', name: 'deleteClient', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour supprimer le client")]
    public function deleteClient(Client $client, EntityManagerInterface $em): JsonResponse
    {
        // Vérifier que le client connecté correspond à l'ID du client à supprimer
        $connectedClient = $this->getUser();
        
        if (!$connectedClient instanceof Client) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }
        
        if ($connectedClient->getId() !== $client->getId()) {
            return new JsonResponse('Vous n\'êtes pas autorisé à supprimer ce client.', Response::HTTP_FORBIDDEN);
        }
        
        $em->remove($client);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}