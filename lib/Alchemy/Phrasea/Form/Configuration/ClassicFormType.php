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

class ClassicFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('search-tab', 'integer', [
            'label'        => 'Search tab position',
        ]);
        $builder->add('adv-search-tab', 'integer', [
            'label'        => 'Advanced search tab position',
        ]);
        $builder->add('topics-tab', 'integer', [
            'label'        => 'Topics tab position',
        ]);
        $builder->add('active-tab', 'integer', [
            'label'        => 'Active tab position',
        ]);

        $builder->add('render-topics', 'choice', [
            'label'        => 'Topics display mode',
            'choices'      => ['tree' => 'Trees', 'popups' => 'Drop-down'],
        ]);

        $builder->add('stories-preview', 'checkbox', [
            'label'        => 'Enable roll-over on stories',
        ]);
        $builder->add('basket-rollover', 'checkbox', [
            'label'        => 'Enable roll-over on basket elements',
        ]);

        $builder->add('collection-presentation', 'choice', [
            'label'        => 'Collections display mode',
            'choices'      => ['popup' => 'Drop-down', 'checkbox' => 'Check-box'],
        ]);

        $builder->add('basket-size-display', 'checkbox', [
            'label'        => 'Display the total size of the document basket',
        ]);
        $builder->add('auto-show-proposals', 'checkbox', [
            'label'        => 'Display proposals tab',
        ]);
        $builder->add('collection-display', 'checkbox', [
            'label'        => 'Display the name of databases and collections',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
