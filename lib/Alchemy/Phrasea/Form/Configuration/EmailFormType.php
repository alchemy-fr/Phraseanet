<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('emitter-email', 'text', [
            'label'        => 'Default mail sender address',
        ]);
        $builder->add('prefix', 'text', [
            'label'        => 'Prefix for notification emails',
        ]);
        $builder->add('smtp-enabled', 'checkbox', [
            'label'        => 'Use a SMTP server',
        ]);
        $builder->add('smtp-auth-enabled', 'checkbox', [
            'label'        => 'Enable SMTP authentication',
        ]);
        $builder->add('smtp-host', 'text', [
            'label'        => 'SMTP host',
        ]);
        $builder->add('smtp-port', 'text', [
            'label'        => 'SMTP port',
        ]);
        $builder->add('smtp-secure-mode', 'choice', [
            'label'        => 'SMTP encryption',
            'choices'      => ['none' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS'],
        ]);
        $builder->add('smtp-user', 'text', [
            'label'        => 'SMTP user',
        ]);
        $builder->add('smtp-password', 'text', [
            'label'        => 'SMTP password',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
