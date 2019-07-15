<?php

namespace AuthBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignInType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ["constraints" => [new NotBlank()]])
            ->add('password', TextType::class, ["constraints" => [new NotBlank()]])
            ->add('dont_remember', CheckboxType::class, ["required" => false])
        ;
    }
}