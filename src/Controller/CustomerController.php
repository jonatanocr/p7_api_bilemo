<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CustomerController extends AbstractController
{
    #[Route('/api/customers', name: 'customers', methods: ['GET'])]
    public function getCustomers(CustomerRepository $customerRepository, SerializerInterface $serializer): JsonResponse
    {
        $customerList = $customerRepository->findAll();
        $jsonCustomerList = $serializer->serialize($customerList, 'json', ['groups' => 'getUsers']);

        return new JsonResponse($jsonCustomerList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/customers/{id}', name: 'detailCustomer', methods: ['GET'])]
    public function getDetailCustomer(Customer $customer, SerializerInterface $serializer)
    {
        $jsonCustomer = $serializer->serialize($customer, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonCustomer, Response::HTTP_OK, [], true);
    }

}
