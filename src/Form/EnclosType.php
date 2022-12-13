<?php

namespace App\Form;

use App\Entity\Enclos;
use App\Entity\Espace;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnclosType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('superficie', NumberType::class, [
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                ],
                'html5' => true,
            ])
            ->add('nbAnimauxMax', IntegerType::class, [
                'attr' => ['min' => '0']
            ])
            ->add('espace', EntityType::class, [
                'class' => Espace::class, // choix de la classe liée
                'choice_label' => "nom", // choix de ce qui sera affiché comme texte
                'multiple' => false,
                'expanded' => false
            ])
            ->add('Ok', SubmitType::class, ["label"=>"💾 Enregistrer"])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enclos::class,
        ]);
    }
}
