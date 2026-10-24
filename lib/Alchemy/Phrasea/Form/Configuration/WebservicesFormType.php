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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebservicesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('google-charts-enabled', CheckboxType::class, [
            'label'        => 'Use Google Chart API',
        ]);
        $builder->add('geonames-server', TextType::class, [
            'label'       => 'Geonames server address',
        ]);

        $builder->add('captcha-provider', ChoiceType::class, [
            'label'        => 'Captcha provider',
            'choices'      => ['none' => 'none', 'reCaptcha' => 'reCaptcha', 'hCaptcha' => 'hCaptcha']
        ]);

        $builder->add('recaptcha-public-key', TextType::class, [
            'label'       => 'Recaptcha public key',
        ]);
        $builder->add('recaptcha-private-key', TextType::class, [
            'label'       => 'Recaptcha private key',
        ]);
        $builder->add('trials-before-display', IntegerType::class, [
            'label'        => 'Trials before display captcha',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Type('integer'),
                new Assert\Regex(pattern: '/^[0-9]\d*$/', message: 'Please use only positive numbers.'),
            ),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
