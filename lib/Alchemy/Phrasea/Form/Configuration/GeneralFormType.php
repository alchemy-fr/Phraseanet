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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class GeneralFormType extends AbstractType
{
    private $availableLanguages;

    public function __construct(array $availableLanguages)
    {
        $this->availableLanguages = $availableLanguages;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
            'label'         => 'Application title',
        ]);
        $builder->add('keywords', TextType::class, [
            'label'         => 'Keywords used for indexing purposes by search engines robots',
        ]);
        $builder->add('description', TextareaType::class, [
            'label'         => 'Application description',
        ]);
        $builder->add('analytics', TextType::class, [
            'label'         => 'Google Analytics identifier',
        ]);
        $builder->add('matomo-analytics-url', TextType::class, [
            'label'         => 'Matomo Analytics url',
        ]);
        $builder->add('matomo-analytics-id', TextType::class, [
            'label'         => 'Matomo Analytics identifier',
        ]);
        $builder->add('allow-indexation', CheckboxType::class, [
            'label'         => 'Allow the website to be indexed by search engines like Google',
        ]);
        $builder->add('home-presentation-mode', ChoiceType::class, [
            'label'         => 'Homepage slideshow',
            'choices'       => [
                'DISPLAYx1' => 'Single image',
                'SCROLL'    => 'Slide show',
                'COOLIRIS'  => 'Cooliris',
                'CAROUSEL'  => 'Carousel',
                'GALLERIA'  => 'Gallery',
            ],
        ]);
        $builder->add('default-subdef-url-ttl', IntegerType::class, [
            'label'       => 'Default TTL in seconds of sub-definition url',
            'attr'        => ['min' => -1],
            'constraints' => new GreaterThanOrEqual(['value' => -1]),
        ]);
        $builder->add('personalize-logo-choice', new PersonalisationLogoFormType(), [
            'label' => 'Design of personalization logo section',
            'attr'  => [
                'id' => 'personalize-logo-container'
            ]
        ]);
    }

    public function getName()
    {
        return null;
    }
}
