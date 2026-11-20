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
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MainConfigurationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translator = $options['translator'];

        $builder->add('general', GeneralFormType::class, [
            'label'    => 'General configuration',
        ]);
        $builder->add('modules', ModulesFormType::class, [
            'label' => 'Additionnal modules',
            'translator' => $translator,
        ]);
        $builder->add('actions', ActionsFormType::class, [
            'label' => 'Push configuration',
            'translator' => $translator,
        ]);
        $builder->add('ftp', FtpExportFormType::class, [
            'label' => 'FTP Export',
            'translator' => $translator,
        ]);
        $builder->add('registration', RegistrationFormType::class, [
            'label' => 'Registration',
            'translator' => $translator,
        ]);
        $builder->add('maintenance', MaintenanceFormType::class, [
            'label' => 'Maintenance state',
        ]);
        $builder->add('api-clients', APIClientsFormType::class, [
            'label' => 'Phraseanet client API',
            'translator' => $translator,
        ]);
        $builder->add('webservices', WebservicesFormType::class, [
            'label' => 'Webservices connectivity',
        ]);
        $builder->add('executables', ExecutablesFormType::class, [
            'label' => 'Executables settings',
            'translator' => $translator,
        ]);
        $builder->add('searchengine', SearchEngineFormType::class, [
            'label' => 'Search engine',
            'translator' => $translator,
        ]);
        $builder->add('email', EmailFormType::class, [
            'label' => 'Emails',
        ]);
        $builder->add('custom-links', CollectionType::class, [
            'entry_type'    => CustomLinkFormType::class,
            'entry_options' => ['label' => false],
            'allow_add'     => true,
            'allow_delete'  => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required'           => false,
            'allow_extra_fields' => true
        ]);
        $resolver->setRequired('translator');
        $resolver->setAllowedTypes('translator', TranslatorInterface::class);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
