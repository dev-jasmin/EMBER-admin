<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Current Password',
                'constraints' => [
                    new NotBlank(message: 'Please enter your current password.'),
                ],
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The new passwords do not match.',
                'first_options' => [
                    'label' => 'New Password',
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Please enter a new password.'),
                    new Length(
                        min: 12,
                        minMessage: 'Your password must contain at least {{ limit }} characters.'
                    ),
                ],
            ]);
    }
}
