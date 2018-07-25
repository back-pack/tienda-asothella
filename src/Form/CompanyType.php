<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('address', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('email', EmailType::class, ['attr' => ['class' => 'form-control']])
            ->add('phone', TextType::class, ['attr' => ['class' => 'form-control']])
            ->add('contactName', TextType::class, ['attr' => ['class' => 'form-control']])
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
            'data_class' => Company::class,
            'validation_groups' => false,
        ]);
    }
}
