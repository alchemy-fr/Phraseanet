<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Silex\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PhraseaAuthenticationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('login', TextType::class, [
            'label'       => 'Login',
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);

        $builder->add('password', PasswordType::class, [
            'label'       => 'Password',
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);

        if ($options['app']['phraseanet.configuration']['session']['idle'] < 1) {
            $builder->add('remember-me', CheckboxType::class, [
                'label'    =>  'Remember me',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'value'   => '1',
                ]
            ]);
        } else {
            $builder->add('remember-me', HiddenType::class, [
                'label'    =>  '',
                'mapped'   => false,
                'required' => false
            ]);
        }

        $builder->add('redirect', HiddenType::class, [
            'required' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('app');
        $resolver->setAllowedTypes('app', Application::class);
        $resolver->setDefaults(array(
            'csrf_protection' => function (Options $options) {
                $configuration = $options['app']['phraseanet.configuration'];

                return isset($configuration['auth-csrf-protection']) ? (bool) $configuration['auth-csrf-protection'] : true;
            },
            'allow_extra_fields' => true,
        ));
    }
}
