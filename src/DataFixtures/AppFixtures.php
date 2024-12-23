<?php

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Folder;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppFixtures extends Fixture
{
    private HttpClientInterface $httpClient;


    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }


    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Requête API pour récupérer ses données
        $response = $this->httpClient->request('GET', 'https://api.magicthegathering.io/v1/cards');
        $apiData = $response->toArray();
        $cardsFromApi = $apiData['cards'] ?? [];
    

        //Création de cartes depuis les données de l'API
        $cards = [];
        for ($i=0; $i < count($cardsFromApi); $i++) { 
            
            //élimination des doublons fournis par l'API ...
            if ($i % 2 === 0) {
                $card = new Card();
                $card->setName($cardsFromApi[$i]['name']);
                $card->setType($cardsFromApi[$i]['type']);
                if (isset($cardsFromApi[$i]['imageUrl'])) {
                    $card->setImage($cardsFromApi[$i]['imageUrl']);
                }
                $manager->persist($card);
                $cards[] = $card;
            }
        }
        
        //Création de l'admin
        $admin = new User();
        $admin->setEmail('john@gmail.com');
        $admin->setName('John');
        //mot de passe 1234
        $admin->setPassword('$2y$10$GZB5urTOYOkBw2bqKNhWW.po/o.SI52PpKK5G4r9wMY0u3b5yTiBu');
        $admin->setRoles(["ROLE_ADMIN"]);
        
         //Création des utilisateurs de test 
        $testUser = new User();
        $testUser->setEmail('bob@gmail.com');
        $testUser->setName('Bob');
        $testUser->setRoles(["ROLE_USER"]);
        //mot de passe 1234
        $testUser->setPassword('$2y$10$GZB5urTOYOkBw2bqKNhWW.po/o.SI52PpKK5G4r9wMY0u3b5yTiBu');
        $testUser2 = new User();
        $testUser2->setEmail('bob2@gmail.com');
        $testUser2->setName('Bob2');
        $testUser2->setRoles(["ROLE_USER"]);
        //mot de passe 1234
        $testUser2->setPassword('$2y$10$GZB5urTOYOkBw2bqKNhWW.po/o.SI52PpKK5G4r9wMY0u3b5yTiBu');
        


        //Création d'utilisateurs
        for ($i=0; $i < 8; $i++) { 
            $user = new User();
            $user->setEmail($faker->email());
            $user->setName($faker->firstName());
            $user->setRoles(["ROLE_USER"]);
            //mot de passe 1234
            $user->setPassword('$2y$10$GZB5urTOYOkBw2bqKNhWW.po/o.SI52PpKK5G4r9wMY0u3b5yTiBu');
            $manager->persist($user);
        }

        //Création du classeur de l'admin
        for ($i=0; $i < 8; $i++) { 
            $folder = new Folder();
            $folder->setOwner($admin);
            $folder->setQuality(5);
            $folder->setExchangeable(1);
            $folder->setCard($cards[$i]);
            $manager->persist($folder);
        }

        //Création du classeur des utlisateurs test
        for ($i=8; $i < 14; $i++) { 
            $folder = new Folder();
            $folder->setOwner($testUser);
            $folder->setQuality(5);
            $folder->setExchangeable(1);
            $folder->setCard($cards[$i]);
            $manager->persist($folder);
        }
        for ($i=12; $i < 20; $i++) { 
            $folder = new Folder();
            $folder->setOwner($testUser2);
            $folder->setQuality(5);
            $folder->setExchangeable(1);
            $folder->setCard($cards[$i]);
            $manager->persist($folder);
        }

        //Création d'entrées dans la Wish-List de l'admin
        for ($i=11; $i < 20; $i++) { 
            $admin->addWish($cards[$i]);
        }

        //Création d'entrées dans la Wish-List de l'utlisateur test (correspondant partiellement au folder de l'admin)
        for ($i=5; $i < 9; $i++) { 
            $testUser->addWish($cards[$i]);
        }
        for ($i=2; $i < 7; $i++) { 
            $testUser2->addWish($cards[$i]);
        }

        $manager->persist($testUser);
        $manager->persist($testUser2);
        $manager->persist($admin);

        $manager->flush();
    }
}
