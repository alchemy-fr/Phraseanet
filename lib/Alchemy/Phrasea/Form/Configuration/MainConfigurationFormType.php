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
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Translation\TranslatorInterface;

class MainConfigurationFormType extends AbstractType
{
    private $languages;
    private $translator;

    public function __construct(TranslatorInterface $translator, array $languages)
    {
        $this->languages = $languages;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('general', new GeneralFormType($this->languages), [
            'label'    => 'General configuration',
        ]);
        $builder->add('modules', new ModulesFormType(), [
            'label' => 'Additionnal modules',
        ]);
        $builder->add('actions', new ActionsFormType(), [
            'label' => 'Push configuration',
        ]);
        $builder->add('ftp', new FtpExportFormType(), [
            'label' => 'FTP Export',
        ]);
        $builder->add('registration', new RegistrationFormType(), [
            'label' => 'Registration',
        ]);
        $builder->add('classic', new ClassicFormType(), [
            'label' => 'Client',
        ]);
        $builder->add('maintenance', new MaintenanceFormType(), [
            'label' => 'Maintenance state',
        ]);
        $builder->add('api-clients', new APIClientsFormType(), [
            'label' => 'Phraseanet client API',
        ]);
        $builder->add('webservices', new WebservicesFormType($this->translator), [
            'label' => 'Webservices connectivity',
        ]);
        $builder->add('executables', new ExecutablesFormType($this->translator), [
            'label' => 'Executables settings',
        ]);
        $builder->add('searchengine', new SearchEngineFormType(), [
            'label' => 'Search engine',
        ]);
        $builder->add('email', new EmailFormType(), [
            'label' => 'Emails',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    public function getName()
    {
        return null;
    }
}
