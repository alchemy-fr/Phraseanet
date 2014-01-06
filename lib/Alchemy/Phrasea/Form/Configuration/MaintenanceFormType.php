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

class MaintenanceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('message', 'text', [
            'label'       => 'Maintenance message',
        ]);
        $builder->add('enabled', 'checkbox', [
            'label'       => 'Enable maintenance message broadcast',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
