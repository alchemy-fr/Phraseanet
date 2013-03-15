<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PhraseaAuthenticationWithMappingForm extends PhraseaAuthenticationForm
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('login', 'hidden', array(
            'required'    => true,
            'disabled'    => $options['disabled'],
            'constraints' => array(
                new Assert\NotBlank(),
            ),
        ));
    }

    public function getName()
    {
        return null;
    }
}
