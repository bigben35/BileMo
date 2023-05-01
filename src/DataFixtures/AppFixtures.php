<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    public function __construct(
        private UserPasswordHasherInterface $clientPasswordHasher)
    {
    }


    public function load(ObjectManager $manager): void
    {
        $listClient = [];
        for ($i=1; $i <= 3 ; $i++) { 
            $client = new Client();
            $client->setCompanyName("Entreprise cliente n°" . $i)
                    ->setEmail("company".$i."@gmail.com")
                    ->setSiren(1324646546 * $i);
            
            $client->setPassword($this->clientPasswordHasher->hashPassword($client, 'password'));
            $client->setRoles(['ROLE_USER']);
            $manager->persist($client);
            $listClient[] = $client; // Ajouter le client créé dans le tableau des clients
        }

        for ($j=1; $j < 10; $j++) { 
            $user = new User();
            $user->setFirstname("Prénom " . $j)
                ->setLastname("Nom " . $j)
                ->setEmail("user".$j."@gmail.com")
                ->setClient($listClient[array_rand($listClient)]);
            $manager->persist($user);
        }


        for ($k=1; $k < 20; $k++) { 
            $product = new Product();
            $product->setBrand("Marque du phone " . $k)
                    ->setModel("Model du phone " .$k)
                    ->setDescription("Description du phone " .$k)
                    ->setPrice(70.5 * $k);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
