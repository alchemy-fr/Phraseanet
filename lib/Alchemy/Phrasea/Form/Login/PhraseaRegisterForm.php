<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Form\Constraint\NewEmail;
use Alchemy\Phrasea\Utilities\StringHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PhraseaRegisterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Application $app */
        $app = $options['app'];
        $available = $options['available_fields'];

        $builder->add('email', EmailType::class, [
            'label'       => 'E-mail',
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
                NewEmail::create($app),
            ],
        ]);

        $builder->add('password', RepeatedType::class, [
            'type'              => PasswordType::class,
            'required'          => true,
            'invalid_message'   => 'Please provide the same passwords.',
            'first_name'        => 'password',
            'second_name'       => 'confirm',
            'first_options'     => ['label' => 'Password'],
            'second_options'    => ['label' => 'Password (confirmation)'],
            'constraints'       => [
                new Assert\NotBlank(),
                new Assert\Length(min: 5),
            ],
        ]);

        if ($app->hasTermsOfUse()) {
            $builder->add('accept-tou', CheckboxType::class, [
                'label'         => 'Terms of Use',
                'mapped'        => false,
                "constraints"   => [
                    new Assert\IsTrue(message: "Please accept the Terms and conditions in order to register.")],
            ]);
        }

        $builder->add('provider-id', HiddenType::class);

        $choices = $baseIds = $collectionNames = [];

        foreach ($app['registration.manager']->getRegistrationSummary() as $baseInfo) {
            $dbName = $baseInfo['db-name'];
            foreach ($baseInfo['collections'] as $baseId => $collInfo) {
                if (false === $collInfo['can-register']) {
                    continue;
                }
                $choices[$dbName][] = $baseId;
                $collectionNames[$baseId] = $collInfo['coll-name'];
                $baseIds[] = $baseId;
            }
        }

        if (!$app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $builder->add('collections', ChoiceType::class, [
                'choices'     => $choices,
                'choice_label' => function ($baseId) use ($collectionNames) {
                    return $collectionNames[$baseId];
                },
                'multiple'    => true,
                'expanded'    => false,
                'constraints' => [
                    new Assert\Choice(choices: $baseIds, minMessage: 'You must select at least %s collection.', multiple: true, min: 1),
                ],
            ]);
        }

        foreach ($options['registration_fields'] as $param) {
            $name = $param['name'];
            if (!preg_match('/[a-zA-Z]+/', $name)) {
                throw new InvalidArgumentException(sprintf('%s is not a valid fieldname', $name));
            }
            if (isset($available[$name])) {
                $fieldOptions = array_merge($available[$name], ['required' => $param['required']]);
                if (!$param['required']) {
                    unset($fieldOptions['constraints']);
                }
                unset($fieldOptions['type']);

                $builder->add(
                    // angular does not support hyphens
                    StringHelper::camelize($name, '-'),
                    $available[$name]['type'],
                    $fieldOptions
                );
            }
        }
        $builder->add('captcha', HiddenType::class, [
            'error_bubbling' => false
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields'  => true,
            'available_fields'    => [],
            'registration_fields' => [],
        ]);
        $resolver->setRequired('app');
        $resolver->setAllowedTypes('app', Application::class);
        $resolver->setAllowedTypes('available_fields', 'array');
        $resolver->setAllowedTypes('registration_fields', 'array');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
