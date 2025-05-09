<?php

namespace App\Form\Gestion_Evenement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AiEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'constraints' => [new NotBlank()]
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Conferences' => 'Conferences',
                    'Seminars & Workshops' => 'Seminars & Workshops',
                    'Trade Shows & Expos' => 'Trade Shows & Expos',
                    'Team-Building Events' => 'Team-Building Events',
                    'Product Launches' => 'Product Launches',
                    'Networking Events' => 'Networking Events',
                    'Award Ceremonies' => 'Award Ceremonies'
                ],
                'constraints' => [new NotBlank()]
            ])
            ->add('nombreInvite', IntegerType::class, [
                'constraints' => [new NotBlank()]
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [new NotBlank()]
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'constraints' => [new NotBlank()]
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'POST',
            'csrf_protection' => true,
            'validation_groups' => ['ai_generation']
        ]);
    }
}