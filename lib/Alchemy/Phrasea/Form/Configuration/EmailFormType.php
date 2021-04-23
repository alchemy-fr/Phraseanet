<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('emitter-email', TextType::class, [
            'label'        => 'Default mail sender address',
        ]);
        $builder->add('prefix', TextType::class, [
            'label'        => 'Prefix for notification emails',
        ]);
        $builder->add('smtp-enabled', CheckboxType::class, [
            'label'        => 'Use a SMTP server',
        ]);
        $builder->add('smtp-auth-enabled', CheckboxType::class, [
            'label'        => 'Enable SMTP authentication',
        ]);
        $builder->add('smtp-host', TextType::class, [
            'label'        => 'SMTP host',
        ]);
        $builder->add('smtp-port', TextType::class, [
            'label'        => 'SMTP port',
        ]);
        $builder->add('smtp-secure-mode', ChoiceType::class, [
            'label'        => 'SMTP encryption',
            'choices'      => ['none' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'],
        ]);
        $builder->add('smtp-user', TextType::class, [
            'label'        => 'SMTP user',
        ]);
        $builder->add('hidden-password', PasswordType::class, [
            'label' => '',
            'attr' =>  [
                'style' => 'display:none'
            ]
        ]);
        $builder->add('smtp-password', PasswordType::class, [
            'label'        => 'SMTP password',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
