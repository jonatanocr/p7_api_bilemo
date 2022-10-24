<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to view this entity')]
    public function getClients(ClientRepository $clientRepository, SerializerInterface $serializer,
    Request $request, TagAwareCacheInterface $cache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getClients-" . $page . "-" . $limit;

        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonClientList = $cache->get($idCache, function (ItemInterface $item) use ($clientRepository, $page, $limit, $serializer, $context) {
          $item->tag("clientsCache");
          $clientList = $clientRepository->findAllWithPagination($page, $limit);
          return $serializer->serialize($clientList, 'json', $context);
        });

        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/clients/{id}', name: 'detailClient', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to view this entity')]
    public function getDetailClient(int $id, Client $client, SerializerInterface $serializer, TagAwareCacheInterface $cache)
    {
        $idCache = "getCient-" . $id;

        $context = SerializationContext::create()->setGroups(["getUsers"]);
        $jsonClient = $cache->get($idCache, function (ItemInterface $item) use ($client, $serializer, $context) {
          $item->tag("clientCache");
          return $serializer->serialize($client, 'json', $context);
        });

        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }

    #[Route('/api/clients', name: 'createClient', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to create a client')]
    public function createClient(Request $request, SerializerInterface $serializer, EntityManagerInterface $em,
      UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, TagAwareCacheInterface $cache,
      UserPasswordHasherInterface $clientPasswordHasher)
    {
        $cache->invalidateTags(["clientsCache"]);

        $client = $serializer->deserialize($request->getContent(), Client::class, 'json');
        $content = $request->toArray();
        $client->setPassword($clientPasswordHasher->hashPassword($client, $content["password"]));
        $client->setRoles(["ROLE_USER"]);
        $errors = $validator->validate($client);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }

          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }
        $em->persist($client);
        $em->flush();

        $jsonClient = $serializer->serialize($client, 'json');
        $location = $urlGenerator->generate('detailClient', ['id' => $client->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        return new JsonResponse($jsonClient, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/clients/{id}', name:"updateClient", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'You don\'t have the right to update a client')]
    public function updateClient(Request $request, SerializerInterface $serializer,
      Client $currentClient, EntityManagerInterface $em, TagAwareCacheInterface $cache,
      UserPasswordHasherInterface $clientPasswordHasher, ValidatorInterface $validator)
    {
        $cache->invalidateTags(["clientsCache"]);
        $cache->invalidateTags(["clientCache"]);

        $newClient = $serializer->deserialize($request->getContent(), Client::class, 'json');
        $currentClient->setEmail($newClient->getEmail());
        $currentClient->setPassword($clientPasswordHasher->hashPassword($currentClient, $newClient->getPassword()));
        $currentClient->setName($newClient->getName());

        $errors = $validator->validate($currentClient);
        if ($errors->count() > 0) {
          $messages = [];
           foreach ($errors as $error) {
                $messages[] = $error->getMessage();
           }

          return new JsonResponse($serializer->serialize($messages, 'json'), JsonResponse::HTTP_BAD_REQUEST);
        }
        $em->persist($currentClient);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
   }

}
