<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Form\AnimalSupprimerType;
use App\Form\AnimalType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnimauxController extends AbstractController
{
    #[Route('/animaux/ajouter/', name: 'ajouter_animal')]
    public function ajouterAnimal(ManagerRegistry $doctrine, Request $request): Response
    {
        $error = false;
        $animal = new Animal();
        $enclosId = null;
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data->getSexe() != 2 && $data->getSexe() != 1) {
                $data->setSterile(0);
            }
            $identification = $data->getIdentification();
            $error = checkAddModifyAnimal($data, $doctrine, $animal, $enclosId);
            if (count($error) != 0) {
                for ($i = 0; $i < count($error); $i++) {
                    $this->addFlash('error', $error[$i]);
                }
                return $this->render('animaux/ajouter.html.twig', [
                    'formulaire' => $form->createView(),
                ]);
            } else {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($data);
                $entityManager->flush();
                $this->addFlash('success', "L'animal avec l'identifiant $identification a bien été ajouté");
                return $this->redirectToRoute('liste_animaux', ['id' => $animal->getEnclos()->getId()]);
            }
        }
        return $this->render('animaux/ajouter.html.twig', [
            'formulaire' => $form->createView(),
        ]);
    }

    #[Route('/animaux/modifier/{id}', name: 'animal_modifier')]
    public function modifierAnimal($id, ManagerRegistry $doctrine, Request $request): Response
    {
        $animal = $doctrine->getRepository(Animal::class)->find($id);
        $enclosId = $animal->getEnclos()->getId();
        if (!$animal) {
            throw $this->createNotFoundException(
                'Aucun animal trouvé pour cet id : '.$id
            );
        }
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($data->getSexe() != 0 && $data->getSexe() != 1) {
                $data->setSterile(0);
            }
            $identification = $data->getIdentification();
            $error = checkAddModifyAnimal($data, $doctrine, $animal, $enclosId);
            if (count($error) != 0) {
                for ($i = 0; $i < count($error); $i++) {
                    $this->addFlash('error', $error[$i]);
                }
                return $this->render('animaux/modifier.html.twig', [
                    'formulaire' => $form->createView(),
                ]);
            } else {
                $entityManager = $doctrine->getManager();
                $entityManager->persist($data);
                $entityManager->flush();
                $this->addFlash('success', "L'animal avec l'identifiant $identification a bien été modifié");
                return $this->redirectToRoute('liste_animaux', ['id' => $animal->getEnclos()->getId()]);
            }
        }
        return $this->render('animaux/modifier.html.twig', [
            'formulaire' => $form->createView(),
        ]);
    }

    #[Route('/animaux/supprimer/{id}', name: 'animal_supprimer')]
    public function supprimerAnimal($id, ManagerRegistry $doctrine, Request $request): Response
    {
        $animal = $doctrine->getRepository(Animal::class)->find($id);
        if (!$animal) {
            throw $this->createNotFoundException(
                'Aucun animal trouvé pour cet id : '.$id
            );
        }

        $form = $this->createForm(AnimalSupprimerType::class, $animal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $identification = $data->getIdentification();
            $entityManager = $doctrine->getManager();
            $entityManager->remove($data);
            $entityManager->flush();
            $this->addFlash('success', "L'animal avec l'identifiant $identification a bien été supprimé");
            return $this->redirectToRoute('liste_animaux', ['id' => $animal->getEnclos()->getId()]);
        }
        return $this->render('animaux/supprimer.html.twig', [
            'formulaire' => $form->createView(),
            'animal' => $animal,
        ]);
    }
    #[Route('/animaux/{id}', name: 'liste_animaux')]
    public function listeAnimaux($id, ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Animal::class);
        //sélectionne tous les animaux de l'enclos avec l'id $id
        $animaux = $repository->findBy(['enclos' => $id]);
        return $this->render('animaux/index.html.twig', [
            'controller_name' => 'AnimauxController',
            'animaux' => $animaux,
        ]);
    }
}



function checkAddModifyAnimal($data, $doctrine, $animal, $enclosId): array
{
    $error = array();
    //le champ identification est unique, on vérifie qu'il n'existe pas déjà
    $repository = $doctrine->getRepository(Animal::class);
    $animalIdentification = $repository->findOneBy(['identification' => $data->getIdentification()]);
    //si l'animal existe déjà, que l'id de l'animal n'est pas le même que celui de l'animal passé en paramètre et que l'animal n'est pas null
    if ($animal != null && $animalIdentification != null && $animalIdentification->getId() != $animal->getId()) {
        $error[] = "L'identifiant ".$data->getIdentification()." est déjà attribué à un autre animal";
    }
    //si l'identifiant n'a pas 14 chiffres
    if (strlen($data->getIdentification()) != 14) {
        $error[] = "L'identifiant doit contenir 14 chiffres";
    }
    //si l'identifiant contient autre chose que des chiffres
    if (!ctype_digit($data->getIdentification())) {
        $error[] = "L'identifiant ne doit contenir que des chiffres";
    }
    //si la date de naissance est supérieure à la date du jour et contient une date, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
    if ($data->getDateNaissance() > new \DateTime() && $data->getDateNaissance() != null) {
        $error[] = 'La date de naissance ne peut pas être supérieure à la date du jour';
    }
    //si la date d'arrivée est inférieure à la date de naissance, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
    if ($data->getDateArrivee() < $data->getDateNaissance() && $data->getDateNaissance() != null) {
        $error[] = 'La date d\'arrivée ne peut pas être inférieure à la date de naissance';
    }
    //si la date de départ est inférieure à la date d'arrivée, on retourne l'utilisateur sur le formulaire rempli avec un message d'erreur
    if ($data->getDateDepart() < $data->getDateArrivee() && $data->getDateDepart() != null) {
        $error[] = 'La date de départ ne peut pas être inférieure à la date d\'arrivée';
    }

    if ($enclosId == null && count($data->getEnclos()->getAnimals()) >= $data->getEnclos()->getNbAnimauxMax()) {
        $error[] = 'L\'enclos '.$animal->getEnclos().' est plein';
    } elseif ($enclosId != null && count($data->getEnclos()->getAnimals()) >= $data->getEnclos()->getNbAnimauxMax() && $data->getEnclos()->getId() != $enclosId) {
        $error[] = 'L\'enclos '.$animal->getEnclos().' est plein';
    }
    return $error;
}