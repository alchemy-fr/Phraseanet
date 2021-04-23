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
use Symfony\Component\Translation\TranslatorInterface;

class APIClientsFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('api-enabled', CheckboxType::class, [
            'label' => 'Enable Phraseanet Web API',
            'help_message' => /** @Ignore */ $this->translator->trans('The Phraseanet Web API allows other web application to rely on this instance')
        ]);

        $builder->add('navigator-enabled', CheckboxType::class, [
            'label'        => 'Authorize *Phraseanet Navigator*',
            'help_message' => /** @Ignore */ $this->translator->trans('*Phraseanet Navigator* is a smartphone application that allow user to connect on this instance'),
        ]);

        $builder->add('office-enabled', CheckboxType::class, [
            'label'        => /** @Ignore */ $this->translator->trans('Authorize Microsoft Office Plugin to connect.'),
        ]);

        $builder->add('adobe_cc-enabled', CheckboxType::class, [
            'label'        => /** @Ignore */ $this->translator->trans('Authorize Adobe cc Plugin to connect.'),
        ]);
    }

    public function getName()
    {
        return null;
    }
}
