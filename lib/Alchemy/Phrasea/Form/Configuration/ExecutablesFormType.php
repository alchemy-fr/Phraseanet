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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

class ExecutablesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_h264_streaming', 'checkbox', array(
            'label'        => _('Enable H264 stream mode'),
            'data'         => false,
            'help_message' => _('Use with mod_token. Attention requires the apache modules and mod_h264_streaming mod_auth_token'),
        ));
        $builder->add('GV_mod_auth_token_directory', 'text', array(
            'label'        => _('Auth_token mount point'),
        ));
        $builder->add('GV_mod_auth_token_directory_path', 'text', array(
            'label'        => _('Auth_token directory path'),
        ));
        $builder->add('GV_mod_auth_token_passphrase', 'text', array(
            'label'        => _('Auth_token passphrase'),
            'help_message' => _('Defined in Apache configuration'),
        ));
        $builder->add('GV_PHP_INI', 'text', array(
            'label'        => _('php.ini path'),
            'help_message' => _('Empty if not used'),
        ));

        $imagineDoc = '<a href="http://imagine.readthedocs.org/en/latest/usage/introduction.html">http://imagine.readthedocs.org/en/latest/usage/introduction.html</a>';
        $builder->add('GV_imagine_driver', 'choice', array(
            'label'        => _('Imagine driver'),
            'data'         => '',
            'help_message' => _(sprintf('See documentation at %s', $imagineDoc)),
            'choices'      => array('' => 'Auto', 'gmagick' => 'GraphicsMagick', 'imagick' => 'ImageMagick', 'gd' => 'GD')
        ));

        $builder->add('GV_ffmpeg_threads', 'integer', array(
            'label'        => _('Number of threads to use for FFMpeg'),
            'data'         => 2,
        ));
        $builder->add('GV_pdfmaxpages', 'integer', array(
            'label'        => _('Maximum number of pages to be extracted from PDF'),
            'data'         => 5,
        ));
    }

    public function getName()
    {
        return null;
    }
}
