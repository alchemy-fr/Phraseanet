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

class APIClientsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('api-enabled', 'checkbox', [
            'label' => 'Enable Phraseanet Web API',
            'help_message' => 'The Phraseanet Web API allows other web application to rely on this instance'
        ]);

        $builder->add('navigator-enabled', 'checkbox', [
            'label'        => 'Authorize *Phraseanet Navigator*',
            'help_message' => '*Phraseanet Navigator* is a smartphone application that allow user to connect on this instance',
        ]);

        $builder->add('office-enabled', 'checkbox', [
            'label'        => 'Authorize Microsoft Office Plugin to connect.',
        ]);

        $builder->add('adobe_cc-enabled', 'checkbox', [
            'label'        => 'Authorize Adobe cc Plugin to connect.',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
