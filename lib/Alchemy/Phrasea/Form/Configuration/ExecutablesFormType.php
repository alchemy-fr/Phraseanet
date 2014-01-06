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
use Symfony\Component\Translation\TranslatorInterface;

class ExecutablesFormType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('h264-streaming-enabled', 'checkbox', [
            'label'        => 'Enable H264 stream mode',
            'help_message' => 'Use with mod_token. Attention requires the apache modules and mod_h264_streaming mod_auth_token',
        ]);
        $builder->add('auth-token-directory', 'text', [
            'label'        => 'Auth_token mount point',
        ]);
        $builder->add('auth-token-directory-path', 'text', [
            'label'        => 'Auth_token directory path',
        ]);
        $builder->add('auth-token-passphrase', 'text', [
            'label'        => 'Auth_token passphrase',
            'help_message' => 'Defined in Apache configuration',
        ]);
        $builder->add('php-conf-path', 'text', [
            'label'        => 'php.ini path',
            'help_message' => 'Empty if not used',
        ]);

        $imagineDoc = '<a href="http://imagine.readthedocs.org/en/latest/usage/introduction.html">http://imagine.readthedocs.org/en/latest/usage/introduction.html</a>';
        $help = $this->translator->trans('See documentation at %url%', ['%url%' => $imagineDoc]);

        $builder->add('imagine-driver', 'choice', [
            'label'        => 'Imagine driver',
            /** @Ignore */
            'help_message' => $help,
            'choices'      => ['' => 'Auto', 'gmagick' => 'GraphicsMagick', 'imagick' => 'ImageMagick', 'gd' => 'GD']
        ]);

        $builder->add('ffmpeg-threads', 'integer', [
            'label'        => 'Number of threads to use for FFMpeg',
        ]);
        $builder->add('pdf-max-pages', 'integer', [
            'label'        => 'Maximum number of pages to be extracted from PDF',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
