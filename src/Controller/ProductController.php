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
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{

    /**
    * Method to get all the products
    *
    * @OA\Response(
    *     response=200,
    *     description="Return list of products",
    *     @OA\JsonContent(
    *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class))
    *     )
    * )
    * @OA\Parameter(
    *     name="page",
    *     in="query",
    *     description="The specific page to get",
    *     @OA\Schema(type="int")
    * )
    *
    * @OA\Parameter(
    *     name="limit",
    *     in="query",
    *     description="Number of elements",
    *     @OA\Schema(type="int")
    * )
    * @OA\Tag(name="Products")
    *
    * @param ProductRepository $productRepository
    * @param SerializerInterface $serializer
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProducts(ProductRepository $productRepository, SerializerInterface $serializer,
    Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getProducts-" . $page . "-" . $limit;

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
          $item->tag("productsCache");
          $productList = $productRepository->findAllWithPagination($page, $limit);
          return $serializer->serialize($productList, 'json');
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    /**
    * Method to get all detail of one product
    *
    * @OA\Response(
    *     response=200,
    *     description="Return detail of one product",
    *     @OA\JsonContent(
    *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class))
    *     )
    * )
    * @OA\Tag(name="Products")
    *
    * @param ProductRepository $productRepository
    * @param SerializerInterface $serializer
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailProduct(int $id, Product $product, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $idCache = "getProduct-" . $id;

        $jsonProduct = $cache->get($idCache, function (ItemInterface $item) use ($product, $serializer) {
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
    public function updateProduct(Request $request, SerializerInterface $serializer,
      Product $currentProduct, EntityManagerInterface $em, TagAwareCacheInterface $cache, ValidatorInterface $validator)
    {
        $cache->invalidateTags(["productsCache"]);
        $cache->invalidateTags(["productCache"]);

        $newProduct = $serializer->deserialize($request->getContent(), Product::class, 'json');
        $currentProduct->setName($newProduct->getName());
        $currentProduct->setDescription($newProduct->getDescription());
        $currentProduct->setColor($newProduct->getColor());
        $currentProduct->setPrice($newProduct->getPrice());

        $errors = $validator->validate($currentProduct);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }
          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($currentProduct);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }

}
