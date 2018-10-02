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

class PersonalisationLogoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('logoChoice', 'choice', [
            'label'    => false,
            'choices'  => [
                'original' => 'original logo',
                'personalize' => 'personalize logo'
            ],
            'expanded' => true,
            'multiple' => false,

        ]);

        $builder->add('personalizeLogoInput', 'file', [
            'label' => false,
        ]);

        $builder->add('personalizeFile', 'hidden', [
            'label' => false,
        ]);

        $builder->add('fileType', 'hidden', [
            'label' => false,
        ]);

    }

    public function getName()
    {
        return null;
    }
}