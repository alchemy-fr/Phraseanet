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

class HomepageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_home_publi', 'choice', array(
            'label'        => _('Homepage slideshow'),
            'data'         => 'GALLERIA',
            'choices'      => array('DISPLAYx1' => _('Single image'), 'SCROLL'    => _('Slide show'), 'COOLIRIS'  => 'Cooliris', 'CAROUSEL'  => _('Carousel'), 'GALLERIA'  => _('Gallery')),
        ));
    }

    public function getName()
    {
        return null;
    }
}
