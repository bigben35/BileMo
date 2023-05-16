<?php

namespace App\Controller;

use App\Entity\Product;
use Psr\Log\LoggerInterface;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]
#[IsGranted('ROLE_USER', message: "Vous n'avez pas les droits suffisants pour l'accès aux produits")]
class ProductController extends AbstractController
{
    //endpoint to display all phones
    #[Route('/products', name: 'products', methods: ['GET'])]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer, Request $request, LoggerInterface $logger, TagAwareCacheInterface $cache): JsonResponse
    {

        // $productList = $productRepository->findAll();
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 8);

        // Vérifier si la page demandée existe
    $totalPages = ceil($productRepository->getTotalPages() / $limit);
    if ($page > $totalPages || $page < 1) {
        return new JsonResponse(['message' => "La page demandée n'existe pas"], Response::HTTP_NOT_FOUND);
    }

        $idCache = "getAllProducts-" . $page . "-" . $limit;
        $logger->debug('Cache key: '.$idCache);

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $logger, $serializer) {
            // echo ("L'élément n'est pas encore en cache !\n");
            $logger->warning("L'élément n'est pas encore en cache !\n");
            $item->tag("productsCache");
            $productList = $productRepository->findAllWithPagination($page, $limit);
            $logger->info("Récupération des utilisateurs depuis la base de données.");
            return $serializer->serialize($productList, 'json');
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }


    //endpoint to display a phone with details
    #[Route('/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailProduct(SerializerInterface $serializer, Product $product): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true); // = OK code 200
    }
}
