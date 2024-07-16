<?php

namespace App\Form\Security;

use App\DTO\Security\RegisterDataDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterForm extends AbstractType
{
    /**
     * @param FormBuilderInterface<FormBuilder> $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class)
            ->add('password', TextType::class)
            ->add('username', PasswordType::class)
            ->add('confirmedPassword', PasswordType::class)
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('zip', TextType::class)
            ->add('street', TextType::class)
            ->add('city', TextType::class)
            ->add('homeNumber', TextType::class)
            ->add('country', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class"         => RegisterDataDTO::class,
            "allow_extra_fields" => true,
        ]);
    }

}
