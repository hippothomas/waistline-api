<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$builder
            ->add('username', TextType::class, $this->getConfiguration("Username", "Your username"))
            ->add('password', PasswordType::class, $this->getConfiguration("Password", "Choose a strong password!"))
            ->add('passwordConfirm', PasswordType::class, $this->getConfiguration("Password confirmation", "Please confirm your password"))
            ->add('agreeTerms', CheckboxType::class, [
				'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
