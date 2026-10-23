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

use JMS\TranslationBundle\Annotation\Ignore;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class APIClientsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('api-enabled', CheckboxType::class, [
            'label' => 'Enable Phraseanet Web API',
            'help_message' => /** @Ignore */ $options['translator']->trans('The Phraseanet Web API allows other web application to rely on this instance')
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('translator');
        $resolver->setAllowedTypes('translator', TranslatorInterface::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
