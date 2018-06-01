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

class PersonalisationLogoForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('originalChoiceInput', 'choice', [
            'label'    => false,
            'choices'  => ['original' => 'original-choice-label'],
            'expanded' => true,

        ]);
        $builder->add('personaliseChoiceInput', 'choice', [
            'label'    => false,
            'choices'  => ['personalise' => 'personalise-choice-label'],
            'expanded' => true,

        ]);
        $builder->add('personalizeLogoInput', 'file', [
            'label' => false,
        ]);
        $builder->add('logoChoice', 'hidden', [
            'label' => false,
        ]);
    }

    public function getName()
    {
        return null;
    }
}