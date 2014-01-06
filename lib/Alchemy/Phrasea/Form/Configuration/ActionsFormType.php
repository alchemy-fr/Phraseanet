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

class ActionsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('download-max-size', 'integer', [
            'label'        => 'Maximum megabytes allowed for download',
            'help_message' => 'If request is bigger, then mail is still available',
        ]);
        $builder->add('validation-reminder-days', 'integer', [
            'label'       => 'Number of days before the end of the validation to send a reminder email',
        ]);
        $builder->add('validation-expiration-days', 'integer', [
            'label'        => 'Default validation links duration',
            'help_message' => 'If set to 0, duration is permanent',
        ]);
        $builder->add('auth-required-for-export', 'checkbox', [
            'label'        => 'Require authentication to download documents',
            'help_message' => 'Used for guest account',
        ]);
        $builder->add('tou-validation-required-for-export', 'checkbox', [
            'label'        => 'Users must accept Terms of Use for each export',
        ]);
        $builder->add('export-title-choice', 'checkbox', [
            'label'        => 'Choose the title of the document to export',
        ]);
        $builder->add('default-export-title', 'choice', [
            'label'        => 'Default export title',
            'choices'      => ['title' => 'Document title', 'original' => 'Original name'],
        ]);
        $builder->add('social-tools', 'choice', [
            'label'        => 'Enable this setting to share on Facebook and Twitter',
            'choices'      => ['none' => 'Disabled', 'publishers' => 'Publishers', 'all' => 'Enabled'],
        ]);
    }

    public function getName()
    {
        return null;
    }
}
