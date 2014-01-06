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

class SearchEngineFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('min-letters-truncation', 'integer', [
            'label'        => 'Minimum number of letters before truncation',
            'help_message' => 'Used in search engine',
        ]);
        $builder->add('default-query', 'text', [
            'label'        => 'Default query',
        ]);
        $builder->add('default-query-type', 'choice', [
            'label'        => 'Default searched type',
            'help_message' => 'Used when opening the application',
            'choices'      => ['0' => 'Documents', '1' => 'Stories'],
        ]);
    }

    public function getName()
    {
        return null;
    }
}
