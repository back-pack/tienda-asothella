<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class)
            ->add('lastName', TextType::class)
            ->add('email', EmailType::class)
            ->add('phone', TextType::class)
            ->add('username', TextType::class)
            ->add('role', ChoiceType::class, ['choices' => ['Vendedor' => 'ROLE_SALES', 'Logistica' => 'ROLE_LOGISTIC']])
            ->add('plainPassword', RepeatedType::class, ['type' => PasswordType::class,
            'first_options' => ['label' => 'Contraseña', 'attr' => ['class' => 'form-control']],
            'second_options' => ['label' => 'Repetir contraseña', 'attr' => ['class' => 'form-control']],
            ])
            ->add('save', SubmitType::class, ['label' => 'Registrar' ,'attr' => ['class' => 'btn btn-info']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
