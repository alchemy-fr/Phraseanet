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
use Symfony\Component\Validator\Constraints as Assert;

class WebservicesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $recaptchaDoc = '<a href="http://www.google.com/recaptcha">http://www.google.com/recaptcha</a>';

        $builder->add('GV_google_api', 'checkbox', array(
            'label'        => _('Use Google Chart API'),
            'data'         => true,
        ));
        $builder->add('GV_i18n_service', 'text', array(
            'label'       => _('Geonames server address'),
            'data'        => 'https://geonames.alchemyasp.com/',
        ));
        $builder->add('GV_captchas', 'checkbox', array(
            'label'        => _('Use recaptcha API'),
            'data'         => false,
            'help_message' => _(sprintf('See documentation at %s', $recaptchaDoc)),
        ));
        $builder->add('GV_captcha_public_key', 'text', array(
            'label'       => _('Recaptcha public key'),
            'data'        => '',
        ));
        $builder->add('GV_captcha_private_key', 'text', array(
            'label'       => _('Recaptcha private key'),
            'data'        => '',
        ));
    }

    public function getName()
    {
        return null;
    }
}
