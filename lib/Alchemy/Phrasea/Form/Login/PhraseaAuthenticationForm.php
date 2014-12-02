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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

        if ($this->app['phraseanet.configuration']['session']['idle'] < 1) {
            $builder->add('remember-me', 'checkbox' , array(
                'label'    =>  _('Remember me'),
                'mapped'   => false,
                'required' => false,
                'attr'     => array(
                    'value'   => '1',
                )
            ));
        } else {
            $builder->add('remember-me', 'hidden' , array(
                'label'    =>  '',
                'mapped'   => false,
                'required' => false
            ));
        }

        $builder->add('redirect', 'hidden', array(
            'required' => false,
        ));
    }

    public function getName()
    {
        return null;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $csrf = true;
        if (isset($this->app['phraseanet.configuration']['auth-csrf-protection'])) {
            $csrf = (Boolean) $this->app['phraseanet.configuration']['auth-csrf-protection'];
        }
        $resolver->setDefaults(array(
            'csrf_protection' => $csrf,
        ));
    }
}
