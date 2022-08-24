<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{

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

        $customerList = ['Orange', 'SFR', 'Bouygues', 'Free'];
        $objectCustomerList = [];
        foreach ($customerList as $key => $value) {
          $psw = uniqid();
          $customer = new Customer;
          $customer->setUsername($value);
          $customer->setPassword($psw);
          $customer->setEmail('admin@'.$value.'.com');
          $manager->persist($customer);
          $objectCustomerList[] = $customer;
        }

        //$faker = Faker\Factory::create('fr_FR');
        $faker = Factory::create('fr_FR');
        for ($i=0; $i < 50; $i++) {
          $user = new User;
          $user->setName('Boutique ' . $i+1);
          $user->setAddress($faker->address());
          $user->setTelephone($faker->serviceNumber());
          $user->setCustomer($objectCustomerList[array_rand($objectCustomerList)]);
          $manager->persist($user);
        }

        $manager->flush();
    }
}