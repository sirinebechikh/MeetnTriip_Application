<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Event Name'])
            ->add('type', TextType::class, ['label' => 'Event Type'])
            ->add('nombreInvite', IntegerType::class, ['label' => 'Number of Guests'])
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date'
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'End Date'
            ])
            ->add('description', TextareaType::class, ['label' => 'Event Description'])
            ->add('lieuEvenement', TextType::class, ['label' => 'Event Location'])
            ->add('budgetPrevu', IntegerType::class, ['label' => 'Expected Budget'])
            ->add('activities', TextareaType::class, ['label' => 'Activities'])
            ->add('imagePath', FileType::class, [
                'label' => 'Event Image',
                'mapped' => false, 
                'required' => false, 
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, or WEBP).',
                    ])
                ]
                    ]);
     }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}