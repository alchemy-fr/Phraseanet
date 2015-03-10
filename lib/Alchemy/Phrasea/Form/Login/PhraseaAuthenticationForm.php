<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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

        if ($this->app['phraseanet.configuration']['session']['idle'] < 1) {
            $builder->add('remember-me', 'checkbox' , [
                'label'    =>  'Remember me',
                'mapped'   => false,
                'required' => false,
                'attr'     => [
                    'value'   => '1',
                ]
            ]);
        } else {
            $builder->add('remember-me', 'hidden' , [
                'label'    =>  '',
                'mapped'   => false,
                'required' => false
            ]);
        }

        $builder->add('redirect', 'hidden', [
            'required' => false,
        ]);
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
