<?php

namespace App\Form;
use App\Entity\Requirement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Company;

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
        if($options['company']) {
            $builder->add('company', EntityType::class,
                [
                    'class' => Company::class,
                    'choice_value' => 'id',
                    'choice_label' => 'name',
                    'expanded' => false,
                    'multiple' => false,
                    'label' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Requirement::class,
            'company' => false,
        ]);
    }
}
