<?php
namespace App\Service;

use App\Repository\CardRepository;
use App\Repository\FolderRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserDataService
{
    private $cardRep;
    private $folderRep;
    private $em;

    public function __construct(CardRepository $cardRep, FolderRepository $folderRep, EntityManagerInterface $em)
    {
        $this->cardRep = $cardRep;
        $this->folderRep = $folderRep;
        $this->em = $em;
    }

    public function getCardRep(): CardRepository
    {
        return $this->cardRep;
    }

    public function getUserData(User $user): array
    {
        // Données des cartes dans les folders
        $userCardsInFolder = $user->getFolders()->map(fn($folder) => $folder->getCard())->toArray();

        // Données des souhaits
        $userWishes = $user->getWish()->toArray();

        // Infos des folders
        $userFolderDetails = [];
        foreach ($user->getFolders() as $folder) {
            $card = $folder->getCard();
            $cardId = $card->getId();
            
            $userFolderDetails[$cardId][] = [
                'quality' => $folder->getQuality(),
                'exchangeable' => $folder->isExchangeable(),
            ];
        }

        // Matches d'échange
        $matches = $this->folderRep->getMatches($user, $this->em);

        
        // Trouver les matchs d'échanges et leur réciprocité // matches // exchangeData
        $exchangeData = [];
        
        $matches = $this->folderRep->getMatches($user, $this->em);
        
        foreach ($matches as $match) {
            $target = $match['folder']->getOwner(); // Récupérer l'utilisateur cible
            
            if ($target) {
                $reverseMatches = $this->folderRep->findReciprocity($this->em, $user, $target);
                
                // Ajouter les données dans la structure souhaitée
                $exchangeData[] = [
                    'target' => $target,
                    'matches' => $match,
                    'reverse' => $reverseMatches,
                ];
            }
        }

        return [
            'userCardsInFolder' => $userCardsInFolder,
            'userWishes' => $userWishes,
            'userFolderDetails' => $userFolderDetails,
            'matches' => $matches,
            'exchangeData' => $exchangeData,
        ];
    }
}