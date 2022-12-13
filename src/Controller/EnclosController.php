<?php

namespace App\Controller;

use App\Entity\Enclos;
use App\Form\EnclosType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnclosController extends AbstractController
{
    #[Route('/enclos', name: 'app_enclos')]
    public function voirEnclos(ManagerRegistry $doctrine, Request $request): Response
    {
        // Création du formulaire d'ajout
        $enclos = new Enclos();
        $form = $this->createForm(EnclosType::class, $enclos);

        // Gestion du retour du formulaire
        // on ajoute Request dans les paramètres comme dans le projet précédent
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            // le handleRequest a rempli notre formulaire
            // qui n'est plus vide
            // pour sauvegarder, on va récupérer un entityManager de doctrine
            $em = $doctrine->getManager();
            $em->persist($enclos);
            // génération de l'insert
            $em->flush();
            // Rediriger vers la page d'accueil pour clear le form
            return $this->redirectToRoute("app_enclos");
        }

        // Pour aller chercher les enclos, je vais utiliser un repository
        // Pour me servir de doctrine j'ajoute le paramètre $doctrine à la méthode
        $repo = $doctrine->getRepository(Enclos::class);
        $enclos = $repo->findAll();
        return $this->render('enclos/index.html.twig', [
            'enclos' => $enclos,
            'formulaire' => $form->createView()
        ]);
    }
}
