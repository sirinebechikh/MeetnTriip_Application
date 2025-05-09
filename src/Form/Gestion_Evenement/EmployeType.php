<?php
namespace App\Form;
 use App\Entity\gestion_user\User;
  use Symfony\Component\Form\AbstractType; 
  use
    Symfony\Component\Form\FormBuilderInterface; 
    use Symfony\Component\Form\Extension\Core\Type\TextType; 
    use
    Symfony\Component\Form\Extension\Core\Type\PasswordType; 
    use Symfony\Component\Form\Extension\Core\Type\FileType;
    use Symfony\Component\OptionsResolver\OptionsResolver;
     class EmployeType extends AbstractType { 
        public function
    buildForm(FormBuilderInterface $builder, array $options):
     void { $builder ->add('nom', TextType::class)
    ->add('email', TextType::class)
    ->add('motDePasse', PasswordType::class)
    ->add('telephone', TextType::class, [
    'required' => false,
    ])
    // Ajouter un champ pour télécharger l'image
    ->add('photo', FileType::class, [
    'label' => 'Photo de profil',
    'mapped' => false, // Important si tu ne veux pas directement mapper cette valeur à une entité
    'required' => false,
    'attr' => ['accept' => 'image/*'] // Accepte seulement les images
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    $resolver->setDefaults([
    'data_class' => User::class,
    ]);
    }
    }