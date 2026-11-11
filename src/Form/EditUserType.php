<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EditUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 50),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Account Role',
                'mapped' => false,
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(choices: [
                        'ROLE_ADMIN',
                        'ROLE_STAFF',
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'mapped' => false,
                'first_options' => [
                    'label' => 'New Password (Optional)',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The passwords do not match.',
                'constraints' => [
                    new Assert\Length(min: 12),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
        ]);
    }
}
