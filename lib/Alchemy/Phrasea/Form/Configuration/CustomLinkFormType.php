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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomLinkFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('linkName', TextType::class, [
            'label' => false,
            'attr'  => [
                'placeholder' => 'setup::custom-link:name-link',
                'required'    => true,
                'maxlength'   => "30"
            ]
        ]);
        $builder->add('linkLanguage', ChoiceType::class, [
            'label'   => false,
            'attr'    => [
                'required' => true
            ],
            'choices' => [
                ''    => 'setup::custom-link:select-language',
                'all' => 'All',
                'fr'  => 'FR',
                'en'  => 'EN',
                'es'  => 'ES',
                'ar'  => 'AR',
                'de'  => 'DE',
                'du'  => 'DU'
            ]
        ]);
        $builder->add('linkUrl', UrlType::class, [
            'label' => false,
            'attr'  => [
                'placeholder' => 'setup::custom-link:placeholder-link-url',
                'required'    => true
            ]
        ]);
        $builder->add('linkLocation', ChoiceType::class, [
            'label'   => false,
            'attr'    => [
                'required' => true
            ],
            'choices' => [
                ''               => 'setup::custom-link:location',
                'help-menu'      => 'setup::custom-link:help-menu',
                'navigation-bar' => 'setup::custom-link:navigation-bar',
            ]
        ]);
        $builder->add('linkOrder', IntegerType::class, [
            'label' => false,
        ]);
        $builder->add('linkBold', CheckboxType::class, [
            'label' => false,
        ]);
        $builder->add('linkColor', ChoiceType::class, [
            'label'   => false,
            'choices' => [
                ''        => '#ad0800',
                '#ad0800' => '#ad0800',
                '#f06006' => '#f06006',
                '#f5842b' => '#f5842b',
                '#ffc322' => '#ffc322',
                '#f4ea5b' => '#f4ea5b',
                '#b8d84e' => '#b8d84e',
                '#5aa53b' => '#5aa53b',
                '#a1d0d0' => '#a1d0d0',
                '#4497d5' => '#4497d5',
                '#3567c6' => '#3567c6',
                '#b151ee' => '#b151ee',
                '#c875ea' => '#c875ea',
                '#e46990' => '#e46990',
                '#ffccd7' => '#ffccd7'
            ]
        ]);
    }

    public function getName()
    {
        return null;
    }
}