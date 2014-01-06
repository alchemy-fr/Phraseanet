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

class GeneralFormType extends AbstractType
{
    private $availableLanguages;

    public function __construct(array $availableLanguages)
    {
        $this->availableLanguages = $availableLanguages;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', [
            'label'         => 'Application title',
        ]);
        $builder->add('keywords', 'text', [
            'label'         => 'Keywords used for indexing purposes by search engines robots',
        ]);
        $builder->add('description', 'textarea', [
            'label'         => 'Application description',
        ]);
        $builder->add('analytics', 'text', [
            'label'         => 'Google Analytics identifier',
        ]);
        $builder->add('allow-indexation', 'checkbox', [
            'label'         => 'Allow the website to be indexed by search engines like Google',
        ]);
        $builder->add('home-presentation-mode', 'choice', [
            'label'         => 'Homepage slideshow',
            'choices'       => [
                'DISPLAYx1' => 'Single image',
                'SCROLL'    => 'Slide show',
                'COOLIRIS'  => 'Cooliris',
                'CAROUSEL'  => 'Carousel',
                'GALLERIA'  => 'Gallery',
            ],
        ]);
    }

    public function getName()
    {
        return null;
    }
}
