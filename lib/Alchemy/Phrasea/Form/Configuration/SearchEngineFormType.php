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
use Symfony\Component\Translation\TranslatorInterface;

class SearchEngineFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('min-letters-truncation', IntegerType::class, [
            'label'        => 'Minimum number of letters before truncation',
            'help_message' => /** @Ignore */ $this->translator->trans('Used in search engine'),
        ]);
        $builder->add('default-query', TextType::class, [
            'label'        => 'Default query',
        ]);
        $builder->add('default-query-type', ChoiceType::class, [
            'label'        => 'Default searched type',
            'help_message' => /** @Ignore */ $this->translator->trans('Used when opening the application'),
            'choices'      => ['0' => 'Documents', '1' => 'Stories'],
        ]);
    }

    public function getName()
    {
        return null;
    }
}
