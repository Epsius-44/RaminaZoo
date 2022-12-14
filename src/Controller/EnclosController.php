<?php

namespace App\Controller;

use App\Entity\Enclos;
use App\Form\EnclosSupprimerType;
use App\Form\EnclosType;
use App\Form\QuarantaineType;
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
        //vérifie pour chaque enclos s'il contient un animal en quarantaine et retourne le résultat dans un tableau de booléens
        $enclosQuarantaine = [];
        $enclosRepository = $doctrine->getRepository(Enclos::class)->findAll();
        foreach ($enclosRepository as $enclos) {
            // compte le nombre d'animaux en quarantaine dans l'enclos
            $nbAnimauxQuarantaine = count($enclos->getAnimals()->filter(fn($animal) => $animal->isQuarantaine() == 1));
            // si le nombre d'animaux en quarantaine est supérieur à 0, on ajoute true au tableau
            $enclosQuarantaine[$enclos->getId()] = $nbAnimauxQuarantaine > 0 ;
        }

        // Pour aller chercher les enclos, je vais utiliser un repository
        // Pour me servir de doctrine j'ajoute le paramètre $doctrine à la méthode
        $repo = $doctrine->getRepository(Enclos::class);
        $enclos = $repo->findAll();
        return $this->render('enclos/index.html.twig', [
            'enclos' => $enclos,
            'enclosQuarantaine' => $enclosQuarantaine,
            'formulaire' => $form->createView()
        ]);
    }

    #[Route('/enclos/quarantaine/{id}', name: 'app_enclos_quarantaine')]
    public function enclosEnQuarantaine($id, ManagerRegistry $doctrine, Request $request): Response
    {
        // Pour aller chercher les enclos, je vais utiliser un repository
        // Pour me servir de doctrine j'ajoute le paramètre $doctrine à la méthode
        $enclos = $doctrine->getRepository(Enclos::class)->find($id);

        if(!$enclos){
            throw $this->createNotFoundException('Aucun enclos trouvé avec l\'id '.$id);
        }

        $form = $this->createForm(QuarantaineType::class, $enclos);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            // le handleRequest a rempli notre formulaire
            // qui n'est plus vide
            // on va mettre en quarantaine tous les animaux de l'enclos
            foreach ($enclos->getAnimals() as $animal) {
                $animal->setQuarantaine(1);
            }
            // pour sauvegarder, on va récupérer un entityManager de doctrine
            $em = $doctrine->getManager();
            $em->persist($enclos);
            // génération de l'insert
            $em->flush();
            return $this->redirectToRoute("app_enclos");
        }
        return $this->render('enclos/quarantaine.html.twig', [
            'enclos' => $enclos,
            'formulaire' => $form->createView()
        ]);
    }

    #[Route('/enclos/supprimer/{id}', name: 'app_enclos_supprimer')]
    public function supprimerEnclos($id, ManagerRegistry $doctrine, Request $request): Response
    {
        $enclos = $doctrine->getRepository(Enclos::class)->find($id);
        if (!$enclos) {
            throw $this->createNotFoundException(
                'Aucun animal trouvé pour cet id : '.$id
            );
        }

        $form = $this->createForm(EnclosSupprimerType::class, $enclos);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $nom = $data->getNom();
            //si l'enclos contient des animaux, on ne peut pas le supprimer
            if (count($enclos->getAnimals()) > 0) {
                $this->addFlash('error', 'L\'enclos '.$nom.' contient des animaux, il ne peut pas être supprimé');
                return $this->redirectToRoute('app_enclos');
            }
            $entityManager = $doctrine->getManager();
            $entityManager->remove($data);
            $entityManager->flush();
            $this->addFlash('success', "L'enclos $nom a bien été supprimé");
            return $this->redirectToRoute('app_enclos');
        }
        return $this->render('enclos/supprimer.html.twig', [
            'formulaire' => $form->createView(),
            'enclos' => $enclos,
        ]);
    }

    #[Route('/enclos/modifier/{id}', name: 'app_enclos_modifier')]
    public function modifierEnclos($id, ManagerRegistry $doctrine, Request $request): Response
    {
        $enclos = $doctrine->getRepository(Enclos::class)->find($id);
        if (!$enclos) {
            throw $this->createNotFoundException(
                'Aucun enclos trouvé pour cet id : '.$id
            );
        }
        $form = $this->createForm(EnclosType::class, $enclos);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $nom = $data->getNom();
            if (count($enclos->getAnimals()) > $data->getNbAnimauxMax()) {
                $this->addFlash('error', 'Le nombre d\'animaux maximum ne peut pas être inférieur au nombre d\'animaux actuel ('.count($enclos->getAnimals()).') dans l\'enclos '.$nom);
                return $this->render('enclos/modifier.html.twig', [
                    'formulaire' => $form->createView(),
                    'enclos' => $enclos,
                ]);
            }
            $entityManager = $doctrine->getManager();
            $entityManager->persist($data);
            $entityManager->flush();
            $this->addFlash('success', "L'enclos $nom a bien été modifié");
            return $this->redirectToRoute('app_enclos');
        }
        return $this->render('enclos/modifier.html.twig', [
            'formulaire' => $form->createView(),
            'enclos' => $enclos,
        ]);
    }
}
