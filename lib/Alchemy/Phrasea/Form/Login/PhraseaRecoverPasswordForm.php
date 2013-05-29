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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Form\Constraint\PasswordToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Form used to renew password when password lost
 */
class PhraseaRecoverPasswordForm extends AbstractType
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', 'hidden', array(
            'required' => true,
            'constraints' => array(
                new PasswordToken($this->app, $this->app['tokens'])
            )
        ));

        $builder->add('password', 'password', array(
            'label' => _('New password'),
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            )
        ));

        $builder->add('passwordConfirm', 'password', array(
            'label' => _('New password (confirmation)'),
            'required' => false,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            )
        ));
    }

    public function getName()
    {
        return null;
    }
}
