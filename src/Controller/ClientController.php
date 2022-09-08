<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ClientController extends AbstractController
{
    #[Route('/api/clients', name: 'clients', methods: ['GET'])]
    public function getClients(ClientRepository $clientRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $clientList = $clientRepository->findAllWithPagination($page, $limit);
        $jsonClientList = $serializer->serialize($clientList, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/clients/{id}', name: 'detailClient', methods: ['GET'])]
    public function getDetailClient(Customer $client, SerializerInterface $serializer)
    {
        $jsonClient = $serializer->serialize($client, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonClient, Response::HTTP_OK, [], true);
    }

}
