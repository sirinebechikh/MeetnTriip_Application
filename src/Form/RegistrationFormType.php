<?php

namespace App\Form;

use App\Entity\gestion_user\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\CallbackTransformer;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Full Name'
            ])
            ->add('email', EmailType::class)
            ->add('telephone', TextType::class, [
                'label' => 'Phone Number'
            ])
            ->add('passport_number', TextType::class, [
                'label' => 'Passport Number',
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                'choices' => [
                    'Administrateur' => 'ADMIN',
                    'Client' => 'CLIENT',
                    'Sponsor' => 'SPONSOR',
                    'Employee' => 'EMPLOY',
                ],
                'label' => 'Register as',
                'required' => true,
            ])
            ->add('name_company', ChoiceType::class, [
                'label' => 'Company Name',
                'required' => false,
                'choices' => $options['clients'],
                'choice_label' => 'nom',
                'placeholder' => 'Select a client',
                'mapped' => false // Change to false so we can handle it manually
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Initial Amount (TND)',
                'currency' => 'TND',
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid JPG or PNG image',
                    ])
                ],
            ])
        ;
        
        // Add a form event listener to set the name_company field with the client's name
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            
            if ($form->has('name_company') && $form->get('name_company')->getData() instanceof User) {
                $client = $form->get('name_company')->getData();
                $data->setNameCompany($client->getNom());
            }
        });
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'clients' => [], // Add this line for the client list option
        ]);
    }
}
