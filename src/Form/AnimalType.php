<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnimalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('identification', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 1, 'max' => 14, 'exactMessage' => 'L\'identification doit comporter entre 1 et 14 chiffres']),
                ],
                'attr' => ['minlength' => 1, 'maxlength' => 14],

            ])
            ->add('nom')
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['min' => '1970-01-01', 'max' => date('Y-m-d')],
                'required' => false,
            ])
            ->add('dateArrivee',DateType::class, [
                'widget' => 'single_text',
                'attr' => ['min' => '1980-01-01', 'max' => '2100-01-01', 'value' => date('Y-m-d')],
            ])
            ->add('dateDepart',DateType::class, [
                'widget' => 'single_text',
                'attr' => ['min' => '1970-01-01', 'max' => '2069-12-31'],
                'required' => false,
            ])
            ->add('zooProprietaire')
            ->add('genre')
            ->add('espece')
            ->add('sexe', ChoiceType::class, [
                'choices' => [
                    'Non détérminé' => 2,
                    'Mâle' => 0,
                    'Femelle' => 1,
                ],
                'expanded' => false,
                'multiple' => false,
                'required' => true,


            ])
            ->add('sterile')
            ->add('quarantaine')
            ->add('Valider', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary'],
            ])
        ;
        //TODO: Ajouter la relation avec l'enclos
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Animal::class,
        ]);
    }
}
