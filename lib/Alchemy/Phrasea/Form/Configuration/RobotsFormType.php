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
use Symfony\Component\Validator\Constraints as Assert;

class RobotsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_homeTitle', 'text', array(
            'label'       => _('Application title'),
            'data'        => 'Phraseanet',
        ));
        $builder->add('GV_metaKeywords', 'text', array(
            'label'       => _('Keywords used for indexing purposes by search engines robots'),
        ));
        $builder->add('GV_metaDescription', 'textarea', array(
            'label'       => _('Application description'),
        ));
        $builder->add('GV_googleAnalytics', 'text', array(
            'label'       => _('Google Analytics identifier'),
        ));
        $builder->add('GV_allow_search_engine', 'checkbox', array(
            'label'       => _('Allow the website to be indexed by search engines like Google'),
            'data'        => true,
        ));
    }

    public function getName()
    {
        return null;
    }
}
