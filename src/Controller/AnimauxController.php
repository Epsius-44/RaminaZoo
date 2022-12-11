<?php

namespace App\Controller;

use App\Entity\Animal;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnimauxController extends AbstractController
{
    #[Route('/animaux/{id}', name: 'liste_animaux')]
    public function listeAnimaux($id, ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Animal::class);
        $animaux = $repository->findAll();
        return $this->render('animaux/index.html.twig', [
            'controller_name' => 'AnimauxController',
            'animaux' => $animaux,
        ]);
    }

    #[Route('/animaux/ajouter', name: 'ajouter_animal')]
    public function ajouterAnimal(): Response
    {
        return $this->render('animaux/ajouter.html.twig', [
            'controller_name' => 'AnimauxController',
        ]);
    }

    #[Route('/animaux/modifier/{id}', name: 'animal_modifier')]
    public function modifierAnimal(): Response
    {
        return $this->render('animaux/modifier.html.twig', [
            'controller_name' => 'AnimauxController',
        ]);
    }

    #[Route('/animaux/supprimer/{id}', name: 'animal_supprimer')]
    public function supprimerAnimal(): Response
    {
        return $this->render('animaux/supprimer.html.twig', [
            'controller_name' => 'AnimauxController',
        ]);
    }
}
