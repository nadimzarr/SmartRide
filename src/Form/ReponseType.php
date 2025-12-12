<?php

namespace App\Form;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Doctrine\ORM\EntityRepository; 
use App\Entity\Reponse;
use App\Entity\Reclamation;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
         $isEdit = $options['is_edit'];
        $reclamations = $options['reclamations'];

        $builder
             ->add('contenu', TextareaType::class, [
        'label' => 'Contenu',
        'required' => false,       // obligatoire
        'attr' => ['class' => 'form-control'],
    ])
    
            ->add('date_reponse', DateType::class, [
    'widget' => 'single_text', // champ HTML5
    'html5' => true,
    'data' => new \DateTime(), // pré-rempli avec la date du jour
])

            ->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
                'choice_label' => fn(Reclamation $rec) => $rec->getMessage(),
                'placeholder' => 'Sélectionnez une réclamation',
                
                'required' => false,
                'disabled' => $options['is_edit'] ?? false,
                'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('r')
                          ->leftJoin('r.reponse', 'rep')
                          ->where('rep.id IS NULL'); // ne récupérer que les réclamations sans réponse
            },
            ])

            ->add('nom', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'id' => 'nom',
                    'readonly' => true
                ]
            ])

            ->add('prenom', TextType::class, [
                'mapped' => false,
                'attr' => [
                    'id' => 'prenom',
                    'readonly' => true
                ]
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
            'reclamations' => [],
             'is_edit' => false, 
        ]);
    }
}
