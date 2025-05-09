<?php

namespace App\Form;

use App\Entity\Gestion_Evenement\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Event Name',
                'constraints' => [
                    new NotBlank(['message' => 'Please fill in the event name.']),
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Event Type',
                'choices' => [
                    'Conferences' => 'Conferences',
                    'Seminars & Workshops' => 'Seminars & Workshops',
                    'Trade Shows & Expos' => 'Trade Shows & Expos',
                    'Team-Building Events' => 'Team-Building Events',
                    'Product Launches' => 'Product Launches',
                    'Networking Events' => 'Networking Events',
                    'Award Ceremonies' => 'Award Ceremonies'
                ],
                'placeholder' => 'Select Event Type',
                'constraints' => [
                    new NotBlank(['message' => 'Please select the event type.']),
                ]
            ])
            ->add('nombreInvite', IntegerType::class, [
                'label' => 'Number of Guests',
                'constraints' => [
                    new NotBlank(['message' => 'Please fill in the number of guests.']),
                ]
            ])
            ->add('dateDebut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'constraints' => [
                    new NotBlank(['message' => 'Please select the start date.']),
                ]
            ])
            ->add('dateFin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'End Date',
                'constraints' => [
                    new NotBlank(['message' => 'Please select the end date.']),
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Event Description',
                'constraints' => [
                    new NotBlank(['message' => 'Please provide a description for the event.']),
                ]
            ])
            ->add('lieuEvenement', ChoiceType::class, [
                'label' => 'Event Location',
                'choices' => [
                    'Paris' => 'Paris',
                    'New York' => 'New York',
                    'Dubai' => 'Dubai',
                    'London' => 'London',
                    'Istanbul' => 'Istanbul',
                    'Rome' => 'Rome',
                    'Madrid' => 'Madrid',
                    'Tokyo' => 'Tokyo',
                    'Beijing' => 'Beijing',
                    'Sydney' => 'Sydney',
                    'Riyadh' => 'Riyadh',
                    'Ottawa' => 'Ottawa'
                ],
                'placeholder' => 'Select a location',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a location for the event.']),
                ]
            ])
            ->add('budgetPrevu', IntegerType::class, [
                'label' => 'Expected Budget',
                'constraints' => [
                    new NotBlank(['message' => 'Please specify the expected budget.']),
                ]
            ])
            ->add('activities', TextareaType::class, [
                'label' => 'Activities',
                'constraints' => [
                    new NotBlank(['message' => 'Please describe the activities of the event.']),
                ]
            ])
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}