<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{

    /**
    * Method to get all the company's users
    *
    * @OA\Response(
    *     response=200,
    *     description="Return company's users",
    *     @OA\JsonContent(
    *        type="array",
    *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
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
    * @OA\Tag(name="Users")
    *
    * @param UserRepository $userRepository
    * @param SerializerInterface $serializer
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    public function getUsers(UserRepository $userRepository, SerializerInterface $serializer, Request $request,
    UserInterface $client, TagAwareCacheInterface $cache): JsonResponse
    {
        $linkedClient = 0;
        if (!in_array('ROLE_ADMIN', $client->getRoles())) {
          $linkedClient = $client->getId();
        }

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getUsers-" . $page . "-" . $limit;

        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use (
          $userRepository, $page, $limit, $serializer, $linkedClient, $context) {
          $item->tag("usersCache");
          $userList = $userRepository->findAllWithPagination($page, $limit, $linkedClient);
          return $serializer->serialize($userList, 'json', $context);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
    * Method to get detail of one user
    *
    * @OA\Response(
    *     response=200,
    *     description="Return detail of one users",
    *     @OA\JsonContent(
    *        type="array",
    *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
    *     )
    * )
    * @OA\Tag(name="Users")
    *
    * @param UserRepository $userRepository
    * @param SerializerInterface $serializer
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    public function getDetailUser(int $id, User $user, SerializerInterface $serializer,
    UserInterface $client, TagAwareCacheInterface $cache)
    {
        if (!in_array('ROLE_ADMIN', $client->getRoles()) && $user->getClient() != $client) {
          return new JsonResponse('User not found.', JsonResponse::HTTP_NOT_FOUND);
        }

        $idCache = "getUser-" . $id;
        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonUser = $cache->get($idCache, function (ItemInterface $item) use ($user, $serializer, $context) {
          $item->tag("userCache");
          return $serializer->serialize($user, 'json', $context);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
    * Method to delete user
    *
    * @OA\Response(
    *     response=204,
    *     description="Return empty JsonResponse",
    * )
    * @OA\Tag(name="Users")
    *
    * @param EntityManagerInterface $em
    * @param UserInterface $client
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em,
    UserInterface $client, TagAwareCacheInterface $cache)
    {
        if (!in_array('ROLE_ADMIN', $client->getRoles()) && $user->getClient() != $client) {
          return new JsonResponse('User not found.', JsonResponse::HTTP_NOT_FOUND);
        }
        $cache->invalidateTags(["usersCache"]);
        $cache->invalidateTags(["userCache"]);
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

/*
 @Model(type=User::class, groups={"non_sensitive_data"})
*/

    /**
    * Method to get all the company's users
    *
    * @OA\Response(
    *     response=201,
    *     description="Return created user",
    *     @OA\JsonContent(
    *        type="array",
    *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
    *     )
    * )
    * @OA\RequestBody(
    *     @Model(type=User::class, groups={"apiDoc"})
    * )
    *
    * @OA\Tag(name="Users")
    *
    * @param UserRepository $userRepository
    * @param SerializerInterface $serializer
    * @param Request $request
    * @return JsonResponse
    */
    #[Route('/api/users', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em,
      UrlGeneratorInterface $urlGenerator, ClientRepository $clientRepository, ValidatorInterface $validator,
      UserInterface $client, TagAwareCacheInterface $cache)
    {
        $cache->invalidateTags(["usersCache"]);
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $content = $request->toArray();

        if (in_array('ROLE_ADMIN', $client->getRoles())) {
          $idClient = $content['idClient'] ?? -1;
          $user->setClient($clientRepository->find($idClient));
        } else {
          $user->setClient($client);
        }

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }

          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/users/{id}', name:"updateUser", methods:['PUT'])]
    public function updateUser(Request $request, SerializerInterface $serializer,
      User $currentUser, EntityManagerInterface $em, ClientRepository $clientRepository,
      UserInterface $client, TagAwareCacheInterface $cache, ValidatorInterface $validator)
    {
        $cache->invalidateTags(["usersCache"]);
        $cache->invalidateTags(["userCache"]);
        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        $currentUser->setName($newUser->getName());
        $currentUser->setAddress($newUser->getAddress());
        $currentUser->setTelephone($newUser->getTelephone());
        $content = $request->toArray();
        if (in_array('ROLE_ADMIN', $client->getRoles())) {
          $idClient = $content['idClient'] ?? -1;
          $currentUser->setClient($clientRepository->find($idClient));
        } elseif ($newUser->getClient() == $client) {
          $currentUser->setClient($client);
        } else {
          return new JsonResponse('User not found.', JsonResponse::HTTP_NOT_FOUND);
        }
        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }
          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }
        $em->persist($currentUser);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
}
