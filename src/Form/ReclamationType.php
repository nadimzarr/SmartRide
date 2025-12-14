<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Enum\TypeReclamation;
use App\Entity\Reclamation;
use App\Entity\Reponse;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
->add('nom', TextType::class, [
    'required' => false,
    'empty_data' => '',
    'attr' => [
        'pattern' => '.*',
        'novalidate' => 'novalidate',
        'class' => 'form-control',
        'placeholder'=>'écrire votre nom ' ,
    ],
    
])

         ->add('prenom', TextType::class, [
    'required' => false,
    'empty_data' => '',
    'attr' => [
        
        'class' => 'form-control',
        'placeholder'=>'écrire votre prénom ' ,
      
    ],
])
            ->add('type_reclamation',choiceType::class,[
                'choices'=> [
                    'Incident'=> TypeReclamation::INCIDENT, 
                    'Retard'=> TypeReclamation::RETARD,
                    'Comportement du conducteur' => TypeReclamation::COMPORTEMENT_CONDUCTEUR,
                    'Propreté du véhicule' => TypeReclamation::PROPRETE_VEHICULE,
                    'Annulation de trajet' => TypeReclamation::ANNULATION_TRAJET,
                    'Problème de paiement' => TypeReclamation::PROBLEME_PAIEMENT, 
                    'Autre'=> TypeReclamation::AUTRE, 
                ],
                'placeholder'=>'choisissez un type ' ,
                'required' => false, 
                'label' => 'Type de réclamation',



            ])
            ->add('message',TextareaType::class, [
    'label' => 'Description',
    'attr' => ['class' => 'form-control','placeholder' => 'Décrivez le problème en détails...'],
    
    'required' =>false, 
])
            ->add('date_reclamation', DateType::class, [
    'widget' => 'single_text',
    'html5' => true,
    'label' => 'Date de réclamation',
    'data' => new \DateTime('today'), 
])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
