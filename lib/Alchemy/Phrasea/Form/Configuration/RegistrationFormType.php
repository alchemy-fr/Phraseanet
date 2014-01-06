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
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('auto-select-collections', 'checkbox', [
            'label'        => 'Auto select databases',
            'help_message' => 'This option disables the selecting of the databases on which a user can register himself, and registration is made on all granted databases.',
        ]);
        $builder->add('auto-register-enabled', 'checkbox', [
            'label'        => 'Enable auto registration',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
