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
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'setup::custom-link:select-language' => '',
                'All' => 'all',
                'FR'  => 'fr',
                'EN'  => 'en',
                'ES'  => 'es',
                'AR'  => 'ar',
                'DE'  => 'de',
                'DU'  => 'du'
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
                'setup::custom-link:location'       => '',
                'setup::custom-link:help-menu'      => 'help-menu',
                'setup::custom-link:navigation-bar' => 'navigation-bar',
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
                '',
                '#ad0800',
                '#f06006',
                '#f5842b',
                '#ffc322',
                '#f4ea5b',
                '#b8d84e',
                '#5aa53b',
                '#a1d0d0',
                '#4497d5',
                '#3567c6',
                '#b151ee',
                '#c875ea',
                '#e46990',
                '#ffccd7'
            ],
            'choice_label' => function ($color) {
                return '' === $color ? '#ad0800' : $color;
            },
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}