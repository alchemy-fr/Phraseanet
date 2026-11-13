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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchEngineFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('min-letters-truncation', IntegerType::class, [
            'label'        => 'Minimum number of letters before truncation',
            'help_message' => /** @Ignore */ $options['translator']->trans('Used in search engine'),
        ]);
        $builder->add('default-query', TextType::class, [
            'label'        => 'Default query',
        ]);
        $builder->add('default-query-type', ChoiceType::class, [
            'label'        => 'Default searched type',
            'help_message' => /** @Ignore */ $options['translator']->trans('Used when opening the application'),
            'choices'      => ['Documents' => 0, 'Stories' => 1],
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
