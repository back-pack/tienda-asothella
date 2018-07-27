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
            ->add('type', EntityType::class,
                [
                    'class' => RoofTile::class,
                    'choice_value' => 'cost',
                    'choice_label' => 'type',
                    'expanded' => false,
                    'multiple' => false,
                    'label' => false,
                ])
            ->add('cost', TextType::class, ['label' => false, 'attr' => ['readonly' => true]])
            ->add('colour', TextType::class, ['label' => false])
            ->add('quantity', NumberType::class, ['label' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductRequest::class,
        ]);
    }
}
