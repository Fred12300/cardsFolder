<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Folder;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(Request $request, CardRepository $cardRep): Response
    {
        $user = $this->getUser();

        // Filtrer les cartes à afficher
        $context = $request->query->get('context'); // cards, folders, wishes
        $name = $request->query->get('name');
        $toShow = $cardRep->findByFilter($context, $name, $user);

        $userCardsInFolder = $user ? $user->getFolders()->map(fn($folder) => $folder->getCard())->toArray() : [];
        $userWishes = $user ? $user->getWish()->toArray() : [];
        
        // Cartes et infos du folder de l'utilisateur connecté
        if ($user) {
            foreach ($user->getFolders() as $folder) {
                $card = $folder->getCard();
                $cardId = $card->getId();
                
                // Regrouper les informations par carte
                $userFolderDetails[$cardId][] = [
                    'quality' => $folder->getQuality(),
                    'exchangeable' => $folder->isExchangeable(),
                ];
            }
        }
        

        return $this->render('main/index.html.twig', [
            'toShow' => $toShow,
            'userCardsInFolder' => $userCardsInFolder,
            'userWishes' => $userWishes,
            'userFolderDetails' => $userFolderDetails
        ]);
    }

    #[Route('/addFolder/{cardId}', name: 'app_addFolder', methods: ['POST'])]
    public function addFolder(
        int $cardId, 
        CardRepository $cardRep, 
        Request $request, 
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();
        $card = $cardRep->find($cardId);

        // Récupération des données du formulaire
        $quality = (int) $request->request->get('quality');
        $exchangeable = $request->request->get('exchangeable') === '1';

        // Création du nouveau Folder
        $folder = new Folder();
        $folder->setCard($card);
        $folder->setOwner($user);
        $folder->setQuality($quality);
        $folder->setExchangeable($exchangeable);

        $em->persist($folder);
        $em->flush();

        return $this->redirectToRoute('app_main');
    }

    #[Route('/addWish/{cardId}', name: 'app_addWish')]
    public function addWish(int $cardId, EntityManagerInterface $em, CardRepository $cardRep): Response
    {
        $user = $this->getUser();
        $card = $cardRep->find($cardId);
        $user->addWish($card);
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_main');
    }

    #[Route('/removeWish/{cardId}', name: 'app_removeWish')]
    public function removeWish(int $cardId, EntityManagerInterface $em, CardRepository $cardRep): Response
    {
        $user = $this->getUser();
        $card = $cardRep->find($cardId);
        $user->removeWish($card);
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_main');
    }
}
