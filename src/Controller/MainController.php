<?php

namespace App\Controller;

use App\Entity\Folder;
use App\Repository\CardRepository;

use App\Service\UserDataService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(Request $request, UserDataService $userDataService): Response
    {
        $user = $this->getUser();

        // Récupérer les paramètres du filtre
        $context = $request->query->get('context');
        $name = $request->query->get('name');

        // Utiliser le repository via le service
        $toShow = $userDataService->getCardRep()->findByFilter($context, $name, $user);

        // Récupérer les autres données utilisateur via le service
        $userData = $userDataService->getUserData($user);

        return $this->render('main/index.html.twig', array_merge([
            'toShow' => $toShow,
        ], $userData));
    }

    /* #[Route('/', name: 'app_main')]
    public function index(Request $request, CardRepository $cardRep, UserRepository $userRep, FolderRepository $folderRep, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $matches = [];

        // Filtrer les cartes à afficher // toShow
        $context = $request->query->get('context'); // cards, folders, wishes
        $name = $request->query->get('name');
        $toShow = $cardRep->findByFilter($context, $name, $user);

        $userCardsInFolder = $user ? $user->getFolders()->map(fn($folder) => $folder->getCard())->toArray() : [];
        $userWishes = $user ? $user->getWish()->toArray() : [];

        // Cartes et infos du folder de l'utilisateur connecté // userFolderDetails
        $userFolderDetails = [];
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
        
        // Trouver les matchs d'échanges et leur réciprocité // matches // exchangeData
        $exchangeData = [];
        if ($this->getUser()) {
            $user = $this->getUser();
            $matches = $folderRep->getMatches($user, $em);

            foreach ($matches as $match) {
                $target = $match['folder']->getOwner(); // Récupérer l'utilisateur cible

                if ($target) {
                    $reverseMatches = $folderRep->findReciprocity($em, $user, $target);

                    // Ajouter les données dans la structure souhaitée
                    $exchangeData[] = [
                        'target' => $target,
                        'matches' => $match,
                        'reverse' => $reverseMatches,
                    ];
                }
            }
        }

        return $this->render('main/index.html.twig', [
            'toShow' => $toShow,
            'userCardsInFolder' => $userCardsInFolder,
            'userWishes' => $userWishes,
            'userFolderDetails' => $userFolderDetails,
            'matches' => $matches,
            'exchangeData' => $exchangeData,
        ]);
    } */

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
        return $this->redirect($this->generateUrl('app_main') . '#card' . $cardId);
    }

    #[Route('/removeWish/{cardId}', name: 'app_removeWish')]
    public function removeWish(int $cardId, EntityManagerInterface $em, CardRepository $cardRep): Response
    {
        $user = $this->getUser();
        $card = $cardRep->find($cardId);
        $user->removeWish($card);
        $em->persist($user);
        $em->flush();
        return $this->redirect($this->generateUrl('app_main') . '#card' . $cardId);
    }

    #[Route('/seeMatches/{cardId}', name: 'app_matches')]
    public function seeMatches(CardRepository $cardRep, UserDataService $userDataService, int $cardId)
    {
        $user = $this->getUser();
        $selectedCard = $cardRep->find($cardId);
        // Récupérer les autres données utilisateur via le service
        $userData = $userDataService->getUserData($user);

        return $this->render('/details/matches.html.twig', array_merge([
            'card' => $selectedCard,
        ], $userData));
    }
}
