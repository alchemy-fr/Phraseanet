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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PhraseaAuthenticationForm extends AbstractType
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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

        if ($this->app['phraseanet.configuration']['session']['idle'] < 1) {
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
            'allow_extra_fields' => true,
        ));
    }
}
