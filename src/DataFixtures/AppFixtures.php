<?php

namespace App\DataFixtures;

use App\Entity\Administrator;
use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $clientPasswordHasher;

    public function __construct(UserPasswordHasherInterface $clientPasswordHasher)
    {
      $this->clientPasswordHasher = $clientPasswordHasher;
    }

    public function load(ObjectManager $manager)
    {
        $colorList = ['Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Rose'];
        $priceList = ['199$', '259$', '399$', '429$', '499$', '899$'];
        for ($i = 0; $i < 10; $i++) {
            $product = new Product();
            $product->setName('Univers S' . $i+1);
            $product->setDescription('Description of BileMo model Univers S' . $i+1);
            $product->setColor($colorList[array_rand($colorList)]);
            $product->setPrice($priceList[array_rand($priceList)]);
            $manager->persist($product);
        }

        $clientList = ['orange', 'sfr', 'bouygues', 'free'];
        $objectClientList = [];
        foreach ($clientList as $key => $value) {
          $client = new Client();
          $client->setEmail('client@'.$value.'.com');
          $client->setName($value);
          $client->setRoles(['ROLE_USER']);
          $client->setPassword($this->clientPasswordHasher->hashPassword($client, 'password'));
          $manager->persist($client);
          $objectClientList[] = $client;
        }

        $exemple = new Client();
        $exemple->setEmail('exemple@bilemo.com');
        $exemple->setName('Test client');
        $exemple->setRoles(['ROLE_USER']);
        $exemple->setPassword($this->clientPasswordHasher->hashPassword($exemple, 'password'));
        $manager->persist($exemple);
        $objectClientList[] = $exemple;

        $admin = new Client();
        $admin->setEmail('apiadmin1@bilemo.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->clientPasswordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $faker = Factory::create('fr_FR');
        for ($i=0; $i < 50; $i++) {
          $user = new User;
          $user->setName('Boutique ' . $i+1);
          $user->setAddress($faker->address());
          $user->setTelephone($faker->serviceNumber());
          $user->setClient($objectClientList[array_rand($objectClientList)]);
          $manager->persist($user);
        }

        $manager->flush();
    }
}
