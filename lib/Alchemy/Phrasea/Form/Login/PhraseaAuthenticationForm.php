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
        $builder->add('login', 'text', array(
            'label'       => _('Login'),
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ));

        $builder->add('password', 'password', array(
            'label'       => _('Password'),
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ));

        $builder->add('remember-me', 'checkbox', array(
            'label'    => _('Remember me'),
            'mapped'   => false,
            'required' => false,
            'attr'     => array(
                'checked' => 'checked',
                'value'   => '1',
            )
        ));
    }

    public function getName()
    {
        return null;
    }
}
