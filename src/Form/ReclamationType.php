<?php

namespace App\Form;

use App\Entity\Reclamation;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           ->add('name')
            ->add('updated')
           
            ->add('commentaire')
            
            ->add('type', ChoiceType::class, [
                'label' => 'Choisissez un type',
                'choices' => [
                    'Transport' => 'transport',
                    'Événement' => 'evenement',
                    'Hôtel' => 'hotele',
                    'Restaurant' => 'restaurant',
                ],
            ])
            ->add('image' , FileType::class, array('data_class' => null , 'label'=>"image") )
        ;
        
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
