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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ActionsFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('download-max-size', IntegerType::class, [
            'label'        => 'Maximum megabytes allowed for download',
            'help_message' => /** @Ignore */ $this->translator->trans('If request is bigger, then mail is still available'),
        ]);
        $builder->add('validation-reminder-time-left-percent', IntegerType::class, [
            'label'       => 'Percent of the time left before the end of the validation to send a reminder email',
        ]);
        $builder->add('validation-expiration-days', IntegerType::class, [
            'label'        => 'Default validation links duration',
            'help_message' => /** @Ignore */ $this->translator->trans('If set to 0, duration is permanent'),
        ]);
        $builder->add('auth-required-for-export', CheckboxType::class, [
            'label'        => 'Require authentication to download documents',
            'help_message' => /** @Ignore */ $this->translator->trans('Used for guest account'),
        ]);
        $builder->add('tou-validation-required-for-export', CheckboxType::class, [
            'label'        => 'Users must accept Terms of Use for each export',
        ]);
        $builder->add('export-title-choice', CheckboxType::class, [
            'label'        => 'Choose the title of the document to export',
        ]);
        $builder->add('default-export-title', ChoiceType::class, [
            'label'        => 'Default export title',
            'choices'      => ['title' => 'Document title', 'original' => 'Original name'],
        ]);
        $builder->add('social-tools', ChoiceType::class, [
            'label'        => 'Enable this setting to share on Facebook and Twitter',
            'choices'      => ['none' => 'Disabled', 'publishers' => 'Publishers', 'all' => 'Enabled'],
        ]);
        $builder->add('enable-push-authentication', CheckboxType::class, [
            'label'        => 'Enable Forcing authentication to see push content',
            'help_message' => /** @Ignore */ $this->translator->trans('Adds an option to the push form submission to restrict push recipient(s) to Phraseanet users only.'),
        ]);
        $builder->add('force-push-authentication', CheckboxType::class, [
            'label'        => 'Disallow the possibility for the end user to disable push authentication',
        ]);
        $builder->add('enable-feed-notification', CheckboxType::class, [
            'label'        => 'Enable possibility to notify users when publishing a new feed entry',
        ]);
        $builder->add('download-link-validity', IntegerType::class, [
            'label'        => 'Validity period of the download links',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
