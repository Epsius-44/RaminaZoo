<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Form\AnimalType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnimauxController extends AbstractController
{
    #[Route('/animaux/liste/{id}', name: 'liste_animaux')]
    public function listeAnimaux($id, ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Animal::class);
        $animaux = $repository->findAll();
        return $this->render('animaux/index.html.twig', [
            'controller_name' => 'AnimauxController',
            'animaux' => $animaux,
        ]);
    }

    #[Route('/animaux/ajouter/', name: 'ajouter_animal')]
    public function ajouterAnimal(ManagerRegistry $doctrine, Request $request): Response
    {
        $error = false;
        $animal = new Animal();
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $identification = $data->getIdentification();
            //le champ identification est unique, on vérifie qu'il n'existe pas déjà
            $repository = $doctrine->getRepository(Animal::class);
            $animalIdentification = $repository->findOneBy(['identification' => $identification]);
            //si le numéro d'identification existe déjà, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
            if ($animalIdentification) {
                $this->addFlash('error', 'Le numéro d\'identification existe déjà');
                $error = true;
            }
            //si la date de naissance est supérieure à la date du jour et contient une date, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
            if ($data->getDateNaissance() > new \DateTime() && $data->getDateNaissance() != null) {
                $this->addFlash('error', 'La date de naissance ne peut pas être supérieure à la date du jour');
                $error = true;
            }
            //si la date d'arrivée est inférieure à la date de naissance, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
            if ($data->getDateArrivee() < $data->getDateNaissance() && $data->getDateNaissance() != null) {
                $this->addFlash('error', 'La date d\'arrivée ne peut pas être inférieure à la date de naissance');
                $error = true;
            }
            //si la date de départ est inférieure à la date d'arrivée, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
            if ($data->getDateDepart() < $data->getDateArrivee() && $data->getDateDepart() != null) {
                $this->addFlash('error', 'La date de départ ne peut pas être inférieure à la date d\'arrivée');
                $error = true;
            }

            if ($error){
                return $this->render('animaux/ajouter.html.twig', [
                    'formulaire' => $form->createView(),
                ]);
            } else {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($data);
                $entityManager->flush();
                $this->addFlash('success', 'L\'animal a bien été ajouté');
                return $this->redirectToRoute('liste_animaux',['id' => $animal->getId()]);
            }
        }
        return $this->render('animaux/ajouter.html.twig', [
            'controller_name' => 'AnimauxController',
            'formulaire' => $form->createView(),
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
