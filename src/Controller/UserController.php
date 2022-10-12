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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
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

        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $page, $limit, $serializer, $linkedClient) {
          $item->tag("usersCache");
          $userList = $userRepository->findAllWithPagination($page, $limit, $linkedClient);
          return $serializer->serialize($userList, 'json', ['groups' => 'getUsers']);
        });

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    public function getDetailUser(int $id, User $user, SerializerInterface $serializer,
    UserInterface $client, TagAwareCacheInterface $cache)
    {
        if (!in_array('ROLE_ADMIN', $client->getRoles()) && $user->getClient() != $client) {
          // TODO changer la JsonResponse en 404
          return new JsonResponse('User not found.', JsonResponse::HTTP_FORBIDDEN);
        }

        $idCache = "getUser-" . $id;

        $jsonUser = $cache->get($idCache, function (ItemInterface $item) use ($user, $serializer) {
          $item->tag("userCache");
          return $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em,
    UserInterface $client, TagAwareCacheInterface $cache)
    {
        if (!in_array('ROLE_ADMIN', $client->getRoles()) && $user->getClient() != $client) {
          // TODO changer la JsonResponse en 404
          return new JsonResponse('User not found.', JsonResponse::HTTP_FORBIDDEN);
        }
        $cache->invalidateTags(["usersCache"]);
        $cache->invalidateTags(["userCache"]);
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

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

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/users/{id}', name:"updateUser", methods:['PUT'])]
    public function updateUser(Request $request, SerializerInterface $serializer,
      User $currentUser, EntityManagerInterface $em, ClientRepository $clientRepository,
      UserInterface $client, TagAwareCacheInterface $cache)
    {
        $cache->invalidateTags(["usersCache"]);
        $cache->invalidateTags(["userCache"]);

        $updatedUser = $serializer->deserialize($request->getContent(),
                User::class,
                'json',
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);
        $content = $request->toArray();
        if (in_array('ROLE_ADMIN', $client->getRoles())) {
          $idClient = $content['idClient'] ?? -1;
          $updatedUser->setClient($clientRepository->find($idClient));
        } elseif ($updatedUser->getClient() == $client) {
          $updatedUser->setClient($client);
        } else {
          // TODO changer la JsonResponse en 404
          return new JsonResponse('User not found.', JsonResponse::HTTP_FORBIDDEN);
        }

        $em->persist($updatedUser);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }
}
