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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExecutablesFormType extends AbstractType
{
    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('h264-streaming-enabled', CheckboxType::class, [
            'label'        => 'Enable H264 stream mode',
            'help_message' => /** @Ignore */ $this->translator->trans('Use with mod_token. Attention requires the apache modules and mod_h264_streaming mod_auth_token'),
        ]);
        $builder->add('auth-token-directory', TextType::class, [
            'label'        => 'Auth_token mount point',
        ]);
        $builder->add('auth-token-directory-path', TextType::class, [
            'label'        => 'Auth_token directory path',
        ]);
        $builder->add('auth-token-passphrase', TextType::class, [
            'label'        => 'Auth_token passphrase',
            'help_message' => /** @Ignore */ $this->translator->trans('Defined in Apache configuration'),
        ]);
        $builder->add('php-conf-path', TextType::class, [
            'label'        => 'php.ini path',
            'help_message' => /** @Ignore */ $this->translator->trans('Empty if not used'),
        ]);

        $imagineDoc = '<a href="http://imagine.readthedocs.org/en/latest/usage/introduction.html">http://imagine.readthedocs.org/en/latest/usage/introduction.html</a>';

        $builder->add('imagine-driver', ChoiceType::class, [
            'label'        => 'Imagine driver',
            'help_message' => /** @Ignore */ $this->translator->trans('See documentation at %url%', ['%url%' => $imagineDoc]),
            'choices'      => ['' => 'Auto', 'gmagick' => 'GraphicsMagick', 'imagick' => 'ImageMagick', 'gd' => 'GD'],
        ]);

        $builder->add('ffmpeg-threads', IntegerType::class, [
            'label'        => 'Number of threads to use for FFMpeg',
        ]);
        $builder->add('pdf-max-pages', IntegerType::class, [
            'label'        => 'Maximum number of pages to be extracted from PDF',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
