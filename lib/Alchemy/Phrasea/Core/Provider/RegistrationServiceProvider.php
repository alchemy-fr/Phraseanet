<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

// write tests
class RegistrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['registration.fields'] = $app->share(function (Application $app){
            if($app['phraseanet.configuration']->has('registration-fields')) {
                return array_map(function($field) {
                    $chunks = explode('-', $field['name']);

                    if(count($chunks) > 1) {
                        $transformedName = '';
                        foreach($chunks as $chunk) {
                            $transformedName .= ucfirst($chunk);
                        }

                        $field['name'] = lcfirst($transformedName);
                    }

                    return $field;
                }, $app['phraseanet.configuration']->get('registration-fields'));
            } else {
                return array();
            }
        });

        $app['registration.optional-fields'] = $app->share(function (Application $app) {
            return array(
                'login'=> array(
                    'label' => _('admin::compte-utilisateur identifiant'),
                    'type'  => 'text',
                ),
                'gender' => array(
                    'label'   => _('admin::compte-utilisateur sexe'),
                    'type'    => 'choice',
                    'choices' => array(
                        '0' => _('admin::compte-utilisateur:sexe: mademoiselle'),
                        '1' => _('admin::compte-utilisateur:sexe: madame'),
                        '2' => _('admin::compte-utilisateur:sexe: monsieur'),
                    )
                ),
                'firstName' => array(
                    'label' => _('admin::compte-utilisateur prenom'),
                    'type' => 'text',
                ),
                'lastName' => array(
                    'label' => _('admin::compte-utilisateur nom'),
                    'type' => 'text',
                ),
                'address' => array(
                    'label' => _('admin::compte-utilisateur adresse'),
                    'type' => 'textarea',
                ),
                'zipCode' => array(
                    'label' => _('admin::compte-utilisateur code postal'),
                    'type' => 'text',
                ),
                'city' => array(
                    'label' => _('admin::compte-utilisateur ville'),
                    'type' => new \Alchemy\Phrasea\Form\Type\GeonameType(),
                ),
                'position' => array(
                    'label' => _('admin::compte-utilisateur poste'),
                    'type' => 'text',
                ),
                'company' => array(
                    'label' => _('admin::compte-utilisateur societe'),
                    'type' => 'text',
                ),
                'job' => array(
                    'label' => _('admin::compte-utilisateur activite'),
                    'type' => 'text',
                ),
                'tel' => array(
                    'label' => _('admin::compte-utilisateur tel'),
                    'type' => 'text',
                ),
                'fax' => array(
                    'label' => _('admin::compte-utilisateur fax'),
                    'type' => 'text',
                ),
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
