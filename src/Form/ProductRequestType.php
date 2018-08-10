<?php

namespace App\Form;

use App\Entity\ProductRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\RoofTile;
class ProductRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cost', TextType::class, ['label' => false, 'attr' => ['readonly' => true, 'class' => 'text form-control']])
            ->add('colour', TextType::class, ['label' => false])
            ->add('quantity', NumberType::class, ['label' => false, 'attr' => ['class' => 'number form-control', 'defaultValue' => 1]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductRequest::class,
        ]);
    }
}
