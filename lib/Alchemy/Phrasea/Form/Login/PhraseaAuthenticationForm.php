<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PhraseaAuthenticationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('login', 'text', [
            'label'       => 'Login',
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);

        $builder->add('password', 'password', [
            'label'       => 'Password',
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);

        $builder->add('remember-me', 'checkbox', [
            'label'    => 'Remember me',
            'mapped'   => false,
            'required' => false,
            'attr'     => [
                'checked' => 'checked',
                'value'   => '1',
            ]
        ]);

        $builder->add('redirect', 'hidden', [
            'required' => false,
        ]);
    }

    public function getName()
    {
        return null;
    }
}
