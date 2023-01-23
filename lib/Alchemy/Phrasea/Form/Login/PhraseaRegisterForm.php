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
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PhraseaRegisterForm extends AbstractType
{
    private $available;
    private $params;
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app, array $available, array $params = [])
    {
        $this->app = $app;
        $this->available = $available;
        $this->params = $params;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
            'label'       => 'E-mail',
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
                NewEmail::create($this->app),
            ],
        ]);

        $builder->add('password', RepeatedType::class, [
            'type'              => 'password',
            'required'          => true,
            'invalid_message'   => 'Please provide the same passwords.',
            'first_name'        => 'password',
            'second_name'       => 'confirm',
            'first_options'     => ['label' => 'Password'],
            'second_options'    => ['label' => 'Password (confirmation)'],
            'constraints'       => [
                new Assert\NotBlank(),
                new Assert\Length(['min' => 5]),
            ],
        ]);

        if ($this->app->hasTermsOfUse()) {
            $builder->add('accept-tou', CheckboxType::class, [
                'label'         => 'Terms of Use',
                'mapped'        => false,
                "constraints"   => [
                    new Assert\IsTrue([
                        "message" => "Please accept the Terms and conditions in order to register."
                    ])],
            ]);
        }

        $builder->add('provider-id', HiddenType::class);

        $choices = $baseIds = [];

        foreach ($this->app['registration.manager']->getRegistrationSummary() as $baseInfo) {
            $dbName = $baseInfo['db-name'];
            foreach ($baseInfo['collections'] as $baseId => $collInfo) {
                if (false === $collInfo['can-register']) {
                    continue;
                }
                $choices[$dbName][$baseId] = $collInfo['coll-name'];
                $baseIds[] = $baseId;
            }
        }

        if (!$this->app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $builder->add('collections', ChoiceType::class, [
                'choices'     => $choices,
                'multiple'    => true,
                'expanded'    => false,
                'constraints' => [
                    new Assert\Choice([
                        'choices' => $baseIds,
                        'minMessage' => 'You must select at least %s collection.',
                        'multiple' => true,
                        'min'      => 1,
                    ]),
                ],
            ]);
        }

        foreach ($this->params as $param) {
            $name = $param['name'];
            if (!preg_match('/[a-zA-Z]+/', $name)) {
                throw new InvalidArgumentException(sprintf('%s is not a valid fieldname'));
            }
            if (isset($this->available[$name])) {
                $options = array_merge($this->available[$name], ['required' => $param['required']]);
                if (!$param['required']) {
                    unset($options['constraints']);
                }
                unset($options['type']);

                $builder->add(
                    // angular does not support hyphens
                    StringHelper::camelize($name, '-'),
                    $this->getType($name),
                    $options
                );
            }
        }
        $builder->add('captcha', 'hidden', [
            'error_bubbling' => false
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
        ]);
    }

    public function getName()
    {
        return null;
    }

    private function getType($name)
    {
        return $this->available[$name]['type'];
    }
}
