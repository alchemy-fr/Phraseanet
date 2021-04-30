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

class FtpExportFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('ftp-enabled', CheckboxType::class, [
            'label'        => 'Enable FTP export',
            'help_message' => /** @Ignore */ $this->translator->trans('Available in multi-export tab'),
        ]);
        $builder->add('ftp-user-access', CheckboxType::class, [
            'label'        => 'Enable FTP for users',
            'help_message' => /** @Ignore */ $this->translator->trans('By default it is available for admins'),
        ]);
    }

    public function getName()
    {
        return null;
    }
}
