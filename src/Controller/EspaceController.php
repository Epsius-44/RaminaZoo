<?php

namespace App\Controller;

use App\Entity\Enclos;
use App\Entity\Espace;
use App\Form\EspaceSupprimerType;
use App\Form\EspaceType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EspaceController extends AbstractController
{

    #[Route('/', name: 'app_home')]

    public function home(): Response
    {

        return $this->redirectToRoute('app_espace_home');

    }


    #[Route('/espace', name: 'app_espace_home')]
    public function index(ManagerRegistry $doctrine, Request $request): Response
    {
        $espace = new Espace(); //on crée un espage vide
        //on crée un formulaire à partir de la class EspaceType et de notre objet vide
        $form = $this->createForm(EspaceType::class, $espace);

        //gestion du retour du formulaire
        $form->handleRequest($request);

        //pour aller cherher les espaces dans la table espace, je vais utiliser un repository
        //pour me servir de doctrine j'ajoute le parametre $doctrine à la méthode
        $repo = $doctrine->getRepository(Espace::class);
        $espaces = $repo->findAll();


        return $this->render('espace/index.html.twig', [
            'espaces' => $espaces,
        ]);
    }

    #[Route('/espace/ajouter', name: 'app_espace_add')]
    public function ajouterEspace(ManagerRegistry $doctrine, Request $request)
    {
        //creation du formulaire d'ajout
        $espace = new Espace(); //on crée un espage vide
        //on crée un formulaire à partir de la class EspaceType et de notre objet vide
        $form = $this->createForm(EspaceType::class, $espace);

        //gestion du retour du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $error = checkInsertData($espace);
            if (count($error) != 0) {
                for ($i = 0; $i < count($error); $i++) {
                    $this->addFlash('error', $error[$i]);
                }
                return $this->render('espace/ajouter.html.twig', [
                    'formulaire' => $form->createView(),
                ]);
                
            } else {
                //pour sauvegarder,on va récupérer un entityManager de doctrine
                //qui comme son nom l'indique gère les entités
                $em = $doctrine->getManager();
                //on lui dit de ranger dans la BDD
                $em->persist($espace);

                //générer l'insert
                $em->flush();

                //retour à l'accueil
                return $this->redirectToRoute("app_espace_home");
            }
        }
        return $this->render("espace/ajouter.html.twig", [
            'espace' => $espace,
            'formulaire' => $form->createView()
        ]);
    }

    #[Route('/espace/modifier/{id}', name: 'app_espace_edit')]
    public function modifierEspace($id, ManagerRegistry $doctrine, Request $request)
    {
        //récupérer la categorie dans la BDD
        $espace = $doctrine->getRepository(Espace::class)->find($id);

        //si on n'a rien trouvé -> 404
        if (!$espace) {
            throw $this->createNotFoundException("Aucun espace avec l'id $id");
        }

        //si on arrive là, c'est qu'on a trouvé une catégorie
        //on crée le formulaire avec (il sera rempli avec ses valeurs)
        $form = $this->createForm(EspaceType::class, $espace);

        //gestion du retour du formulaire
        //on ajoute Request dans les parametres comme dans le projet precedent
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //le handleRequest a rempli notre objet $categorie
            //qui n'est plus vide

            //vérification sur le remplissage des champs
            $error = checkInsertData($espace);
            if (count($error) != 0) {
                for ($i = 0; $i < count($error); $i++) {
                    $this->addFlash('error', $error[$i]);
                }
                return $this->render('espace/modifier.html.twig', [
                    'espace' => $espace,
                    'formulaire' => $form->createView(),
                ]);
                
            } else {
                //pour sauvegarder,on va récupérer un entityManager de doctrine
                //qui comme son nom l'indique gère les entités
                $em = $doctrine->getManager();
                //on lui dit de ranger dans la BDD
                $em->persist($espace);

                //générer l'insert
                $em->flush();

                //retour à l'accueil
                return $this->redirectToRoute("app_espace_home");
            }
        }

        return $this->render("espace/modifier.html.twig", [
            'espace' => $espace,
            'formulaire' => $form->createView()
        ]);
    }

    #[Route('/espace/supprimer/{id}', name: 'app_espace_supprimer')]
    public function supprimerEspace($id, ManagerRegistry $doctrine, Request $request): Response
    {
        $espace = $doctrine->getRepository(Espace::class)->find($id);
        if (!$espace) {
            throw $this->createNotFoundException(
                'Aucun animal trouvé pour cet id : '.$id
            );
        }

        $form = $this->createForm(EspaceSupprimerType::class, $espace);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $nom = $data->getNom();
            //si l'espace contient des enclos, on ne peut pas le supprimer
            if (count($espace->getEnclos()) > 0) {
                $this->addFlash('error', 'L\'espace '.$nom.' contient des enclos, il ne peut pas être supprimé');
                return $this->redirectToRoute('app_espace_home');
            }
            $entityManager = $doctrine->getManager();
            $entityManager->remove($data);
            $entityManager->flush();
            $this->addFlash('success', "L'espace $nom a bien été supprimé");
            return $this->redirectToRoute('app_espace_home');
        }
        return $this->render('espace/supprimer.html.twig', [
            'formulaire' => $form->createView(),
            'espace' => $espace,
        ]);
    }
}

function checkInsertData($espace): array
{
    $error = array();
    if (empty($espace->getNom())) {
        $error[] = "Obligatoire est le nom !";
    }
    if (empty($espace->getSuperficie())) {
        $error[] = "Obligatoire est la superficie !";
    }
    if (0 > $espace->getSuperficie()) {
        $error[] = "Supérieure à 0, la superficie doit être !";
    }
    if (empty($espace->getOuverture()) and !empty($espace->getFermeture())) {
        $error[] = "Fermer tu ne pourras si ouvrert tu n'as pas !";
    }
    if ($espace->getOuverture() > $espace->getFermeture()) {
        $error[] = "Après la date d'ouverture, la date de fermeture doit avoir lieu !";
    }
    return $error;
}