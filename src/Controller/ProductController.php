<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProducts(ProductRepository $productRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getProducts-" . $page . "-" . $limit;

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
          echo ("L'ELEMENT N'EST PAS ENCORE EN CACHE ! \n");
          $item->tag("productsCache");
          $productList = $productRepository->findAllWithPagination($page, $limit);
          return $serializer->serialize($productList, 'json');
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailProduct(int $id, Product $product, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $idCache = "getProduct-" . $id;

        $jsonProduct = $cache->get($idCache, function (ItemInterface $item) use ($product, $serializer) {
          echo ("L'ELEMENT N'EST PAS ENCORE EN CACHE ! \n");
          $item->tag("productCache");
          return $serializer->serialize($product, 'json');
        });

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }

    #[Route('/api/products/{id}', name: 'deleteProduct', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to delete a product')]
    public function deleteProduct(Product $product, EntityManagerInterface $em, TagAwareCacheInterface $cache)
    {
        $cache->invalidateTags(["productsCache"]);
        $cache->invalidateTags(["productCache"]);
        $em->remove($product);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/products', name: 'createProduct', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to create a product')]
    public function createProduct(Request $request, SerializerInterface $serializer, EntityManagerInterface $em,
      UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache)
    {
        $cache->invalidateTags(["productsCache"]);

        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');
        $content = $request->toArray();

        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }

          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($product);
        $em->flush();

        $jsonProduct = $serializer->serialize($product, 'json');
        $location = $urlGenerator->generate('detailProduct', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/products/{id}', name:"updateProduct", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to update a product')]
    public function updateUser(Request $request, SerializerInterface $serializer,
      Product $currentProduct, EntityManagerInterface $em, TagAwareCacheInterface $cache)
    {
        $cache->invalidateTags(["productsCache"]);
        $cache->invalidateTags(["productCache"]);
        $updatedProduct = $serializer->deserialize($request->getContent(),
                Product::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentProduct]);
        $content = $request->toArray();

        $em->persist($updatedProduct);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }

}
