<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

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
            $colorIndex = array_rand($colorList);
            $priceIndex = array_rand($priceList);
            $product->setColor($colorList[$colorIndex]);
            $product->setPrice($priceList[$priceIndex]);
            $manager->persist($product);
        }
        $manager->flush();
    }
}
