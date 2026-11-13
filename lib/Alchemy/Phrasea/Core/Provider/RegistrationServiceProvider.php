<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Form\Constraint\NewLogin;
use Alchemy\Phrasea\Form\Type\GeonameType;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;
use Pimple\Container;

class RegistrationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['registration.fields'] = function (Application $app) {
            return $app['conf']->get('registration-fields', []);
        };

        $app['registration.manager'] = function (Application $app) {
            return new RegistrationManager($app['phraseanet.appbox'], $app['repo.registrations'], $app['locale']);
        };

        $app['registration.optional-fields'] = function (Application $app) {
            return [
                'login'=> [
                    'label'       => 'admin::compte-utilisateur identifiant',
                    'type'        => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                        NewLogin::create($app),
                    ]
                ],
                'gender' => [
                    'label'   => 'admin::compte-utilisateur sexe',
                    'type'    => ChoiceType::class,
                    'multiple' => false,
                    'expanded' => false,
                    'choices' => [
                        'admin::compte-utilisateur:sexe: mademoiselle' => User::GENDER_MISS,
                        'admin::compte-utilisateur:sexe: madame' => User::GENDER_MRS,
                        'admin::compte-utilisateur:sexe: monsieur' => User::GENDER_MR,
                    ]
                ],
                'firstname' => [
                    'label' => 'admin::compte-utilisateur prenom',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'lastname' => [
                    'label' => 'admin::compte-utilisateur nom',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'address' => [
                    'label' => 'admin::compte-utilisateur adresse',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'zipcode' => [
                    'label' => 'admin::compte-utilisateur code postal',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'geonameid' => [
                    'label' => 'admin::compte-utilisateur ville',
                    'type' => GeonameType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'job' => [
                    'label' => 'admin::compte-utilisateur poste',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'company' => [
                    'label' => 'admin::compte-utilisateur societe',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'position' => [
                    'label' => 'admin::compte-utilisateur activite',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'tel' => [
                    'label' => 'admin::compte-utilisateur tel',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'fax' => [
                    'label' => 'admin::compte-utilisateur fax',
                    'type' => TextType::class,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
            ];
        };
    }
}
