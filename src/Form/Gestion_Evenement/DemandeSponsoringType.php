<?php 

namespace App\Form; use App\Entity\DemandeSponsoring; use App\Entity\User; use
    Symfony\Component\Form\AbstractType; use Symfony\Component\Form\FormBuilderInterface; use
    Symfony\Component\OptionsResolver\OptionsResolver; use Symfony\Component\Form\Extension\Core\Type\ChoiceType; use
    Symfony\Component\Form\Extension\Core\Type\TextType; class DemandeSponsoringType extends AbstractType { public
    function buildForm(FormBuilderInterface $builder, array $options) { 
        $builder ->add('sponsor', ChoiceType::class, [
'choices' => $options['sponsors'], // Liste des sponsors passée dans le contrôleur
'choice_label' => function (User $user) {
return $user->getNom(); // Affichage du nom du sponsor
},
'label' => 'Sélectionner le sponsor',
])
->add('justification', TextType::class, [
'required' => false,
'label' => 'Justification (facultatif)',
]);
}

public function configureOptions(OptionsResolver $resolver)
{
$resolver->setDefaults([
'data_class' => DemandeSponsoring::class,
'sponsors' => [], // Liste des sponsors à passer depuis le contrôleur
]);
}
}