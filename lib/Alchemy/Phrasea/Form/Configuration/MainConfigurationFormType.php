<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Validator\Constraints as Assert;

class MainConfigurationFormType extends AbstractType
{
    private $languages;
    private $generator;

    public function __construct(array $languages, UrlGenerator $generator)
    {
        $this->languages = $languages;
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('http', new HttpServerFormType($this->languages), array(
            'label'    => _('HTTP Server'),
            'required' => false,
        ));
        $builder->add('maintenance', new MaintenanceFormType(), array(
            'label' => _('Maintenance state'),
            'required' => false,
        ));
        $builder->add('webservices', new WebservicesFormType(), array(
            'label' => _('Webservices connectivity'),
            'required' => false,
        ));
        $builder->add('youtube-api', new YoutubeFormType($this->generator), array(
            'label' => _('Youtube connectivity'),
            'required' => false,
        ));
        $builder->add('flickr-api', new FlickrFormType($this->generator), array(
            'label' => _('FlickR connectivity'),
            'required' => false,
        ));
        $builder->add('dailymotion-api', new DailymotionFormType($this->generator), array(
            'label' => _('Dailymotion connectivity'),
            'required' => false,
        ));
        $builder->add('phraseanet-client', new PhraseanetClientAPIFormType(), array(
            'label' => _('Phraseanet client API'),
            'required' => false,
        ));
        $builder->add('storage', new StorageFormType(), array(
            'label' => _('Documents storage'),
            'required' => false,
        ));
        $builder->add('executables', new ExecutablesFormType(), array(
            'label' => _('Executables settings'),
            'required' => false,
        ));
        $builder->add('homepage', new HomepageFormType(), array(
            'label' => _('Main configuration'),
            'required' => false,
        ));
        $builder->add('display', new DisplayFormType(), array(
            'label' => _('Homepage'),
            'required' => false,
        ));
        $builder->add('searchengine', new SearchEngineFormType(), array(
            'label' => _('Search engine'),
            'required' => false,
        ));
        $builder->add('report', new ReportFormType(), array(
            'label' => _('Report'),
            'required' => false,
        ));
        $builder->add('modules', new ModulesFormType(), array(
            'label' => _('Additionnal modules'),
            'required' => false,
        ));
        $builder->add('email', new EmailFormType(), array(
            'label' => _('Emails'),
            'required' => false,
        ));
        $builder->add('client', new FtpExportFormType(), array(
            'label' => _('FTP Export'),
            'required' => false,
        ));
        $builder->add('client', new ClientFormType(), array(
            'label' => _('Client'),
            'required' => false,
        ));
        $builder->add('registration', new RegistrationFormType(), array(
            'label' => _('Registration'),
            'required' => false,
        ));
        $builder->add('push', new PushFormType(), array(
            'label' => _('Push configuration'),
            'required' => false,
        ));
        $builder->add('robots', new RobotsFormType(), array(
            'label' => _('Robot indexing'),
            'required' => false,
        ));
    }

    public function getName()
    {
        return null;
    }
}
