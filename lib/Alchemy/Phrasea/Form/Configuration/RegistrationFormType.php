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

class RegistrationFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('auto-select-collections', CheckboxType::class, [
            'label'        => 'Auto select databases',
            'help_message' => /** @Ignore */ $this->translator->trans('This option disables the selecting of the databases on which a user can register himself, and registration is made on all granted databases.'),
        ]);
        $builder->add('auto-register-enabled', CheckboxType::class, [
            'label'        => 'Enable auto registration',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
