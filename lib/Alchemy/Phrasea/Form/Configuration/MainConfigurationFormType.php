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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MainConfigurationFormType extends AbstractType
{
    private $languages;
    private $translator;

    public function __construct(\Symfony\Contracts\Translation\TranslatorInterface $translator, array $languages)
    {
        $this->languages = $languages;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('general', \Alchemy\Phrasea\Form\Configuration\GeneralFormType::class, [
            'label'    => 'General configuration',
        ]);
        $builder->add('modules', \Alchemy\Phrasea\Form\Configuration\ModulesFormType::class, [
            'label' => 'Additionnal modules',
        ]);
        $builder->add('actions', \Alchemy\Phrasea\Form\Configuration\ActionsFormType::class, [
            'label' => 'Push configuration',
        ]);
        $builder->add('ftp', \Alchemy\Phrasea\Form\Configuration\FtpExportFormType::class, [
            'label' => 'FTP Export',
        ]);
        $builder->add('registration', \Alchemy\Phrasea\Form\Configuration\RegistrationFormType::class, [
            'label' => 'Registration',
        ]);
        $builder->add('maintenance', \Alchemy\Phrasea\Form\Configuration\MaintenanceFormType::class, [
            'label' => 'Maintenance state',
        ]);
        $builder->add('api-clients', \Alchemy\Phrasea\Form\Configuration\APIClientsFormType::class, [
            'label' => 'Phraseanet client API',
        ]);
        $builder->add('webservices', \Alchemy\Phrasea\Form\Configuration\WebservicesFormType::class, [
            'label' => 'Webservices connectivity',
        ]);
        $builder->add('executables', \Alchemy\Phrasea\Form\Configuration\ExecutablesFormType::class, [
            'label' => 'Executables settings',
        ]);
        $builder->add('searchengine', \Alchemy\Phrasea\Form\Configuration\SearchEngineFormType::class, [
            'label' => 'Search engine',
        ]);
        $builder->add('email', \Alchemy\Phrasea\Form\Configuration\EmailFormType::class, [
            'label' => 'Emails',
        ]);
        $builder->add('custom-links', CollectionType::class, [
            'entry_type'    => CustomLinkFormType::class,
            'entry_options' => ['label' => false],
            'allow_add'     => true,
            'allow_delete'  => true,
        ]);
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required'           => false,
            'allow_extra_fields' => true
        ]);
    }

    public function getBlockPrefix(): string
    {
        return null;
    }
}
