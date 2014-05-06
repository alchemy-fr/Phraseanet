<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Silex\Application;

class PhraseaAuthenticationForm extends AbstractType
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

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

        $builder->add('remember-me', $this->app['phraseanet.configuration']['session']['idle'] < 1 ? 'checkbox' : 'hidden', array(
            'label'    => $this->app['phraseanet.configuration']['session']['idle'] < 1 ? _('Remember me') : "",
            'mapped'   => false,
            'required' => false,
            'attr'     => array(
                'value'   => '1',
            )
        ));

        $builder->add('redirect', 'hidden', array(
            'required' => false,
        ));
    }

    public function getName()
    {
        return null;
    }
}
