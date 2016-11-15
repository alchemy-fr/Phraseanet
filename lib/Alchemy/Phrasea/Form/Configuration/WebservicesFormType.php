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

class WebservicesFormType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $recaptchaDoc = '<a href="http://www.google.com/recaptcha">http://www.google.com/recaptcha</a>';

        $builder->add('google-charts-enabled', 'checkbox', [
            'label'        => 'Use Google Chart API',
        ]);
        $builder->add('geonames-server', 'text', [
            'label'       => 'Geonames server address',
        ]);

        $help = $this->translator->trans('See documentation at %url%', ['%url%' => $recaptchaDoc]);

        $builder->add('captchas-enabled', 'checkbox', [
            'label'        => 'Use recaptcha API',
            'help_message' => $help
        ]);
        $builder->add('trials-before-display', 'number', [
            'label'       => 'Trials before display captcha',
        ]);
        $builder->add('recaptcha-public-key', 'text', [
            'label'       => 'Recaptcha public key',
        ]);
        $builder->add('recaptcha-private-key', 'text', [
            'label'       => 'Recaptcha private key',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
