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

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

class SearchEngineFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_min_letters_truncation', 'integer', array(
            'label'        => _('Minimum number of letters before truncation'),
            'data'         => 1,
            'help_message' => _('Used in search engine'),
        ));
        $builder->add('GV_defaultQuery', 'text', array(
            'label'        => _('Default query'),
            'data'         => 'all',
        ));
        $builder->add('GV_defaultQuery_type', 'choice', array(
            'label'        => _('Default searched type'),
            'data'         => 0,
            'help_message' => _('Used when opening the application'),
            'choices'      => array('0' => _('Documents'), '1' => _('Stories')),
        ));
    }

    public function getName()
    {
        return null;
    }
}
