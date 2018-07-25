<?php

namespace App\Form;
use App\Entity\Requirement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class RequirementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productRequests', CollectionType::class, [
                'entry_type' => ProductRequestType::class,
                'allow_add' => true,
                'prototype' => true,
                'label' => false,
                // 'entry_options' => false
            ])
            ->add('finalCost', TextType::class)
            ->add('save', SubmitType::class, ['label' => 'Comprar' ,'attr' => ['class' => 'btn btn-info']])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Requirement::class,
        ]);
    }
}
