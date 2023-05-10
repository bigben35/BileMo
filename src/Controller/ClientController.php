<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api', name: 'api_')]
#[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour l'accès aux clients")] //à modifier si je crée un role_admin (compte d'un développeur BileMo par ex)
class ClientController extends AbstractController
{
    //endpoint to display all clients
    #[Route('/clients', name: 'clients', methods: ['GET'])]
    public function getAllClients(ClientRepository $clientRepository, SerializerInterface $serializer): JsonResponse
    {

        $clientList = $clientRepository->findAll();

        $jsonClientList = $serializer->serialize($clientList, 'json', ['groups' => 'getClients']);
        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }


    #[Route('/clients/{id}', name: 'detailClient', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour accéder au client demandé")]
    public function getDetailClient(Client $client, SerializerInterface $serializer, ClientRepository $clientRepository): JsonResponse
    {
        // $client = $this->getUser(); // Récupère le client connecté
        $client = $clientRepository->findOneBy(['id' => $client->getId()]);

        if ($client){
            $jsonClient = $serializer->serialize($client, 'json',['groups' => 'getClients']);
            return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
        }
        return new JsonResponse(null, Response::HTTP_FORBIDDEN);

    }

    #[Route('/clients', name: 'createClient', methods: ['POST'])]
    #[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour l'accès aux clients")] //à modifier si je crée un role_admin (compte d'un développeur BileMo par ex)
    public function createClient(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, UserPasswordHasherInterface $passworhasher): JsonResponse
    {
        // $client = $this->getUser(); // Récupère le client connecté
        $client = $serializer->deserialize($request->getContent(), Client::class, 'json');

        // Encodage du mot de passe du client
        $hashPassword = $passworhasher->hashPassword($client, $client->getPassword());
        $client->setPassword($hashPassword);

        // On vérifie les erreurs
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($client);
        $em->flush();

        $jsonClient = $serializer->serialize($client, 'json', ['groups' => 'getClients']);
        
        $location = $urlGenerator->generate('detailClient', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true);
    }


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
        $serializer->deserialize($request->getContent(), Client::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $client]);
        $hashPassword = $passworhasher->hashPassword($client, $client->getPassword());
        $client->setPassword($hashPassword);

        // On vérifie les erreurs
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($client);
        $em->flush();

        $jsonClient = $serializer->serialize($client, 'json', ['groups' => 'getClients']);

        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }

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