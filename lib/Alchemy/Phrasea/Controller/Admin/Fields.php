<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Metadata\TagProvider;
use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPExiftool\Exception\TagUnknown;

class Fields implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['admin.fields.controller'] = $this;

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']
                ->requireAccessToModule('admin')
                ->requireRight('bas_modify_struct');
        });

        $controllers->get('/language.json', 'admin.fields.controller:getLanguage')
            ->bind('admin_fields_language');

        $controllers->get('/{sbas_id}', 'admin.fields.controller:displayApp')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields');

        $controllers->put('/{sbas_id}/fields', 'admin.fields.controller:updateFields')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_register');

        $controllers->get('/{sbas_id}/fields', 'admin.fields.controller:listFields')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_list');

        $controllers->post('/{sbas_id}/fields', 'admin.fields.controller:createField')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_create_field');

        $controllers->get('/{sbas_id}/fields/{id}', 'admin.fields.controller:getField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_show_field');

        $controllers->put('/{sbas_id}/fields/{id}', 'admin.fields.controller:updateField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_update_field');

        $controllers->delete('/{sbas_id}/fields/{id}', 'admin.fields.controller:deleteField')
            ->assert('id', '\d+')
            ->assert('sbas_id', '\d+')
            ->bind('admin_fields_delete_field');

        $controllers->get('/tags/search', 'admin.fields.controller:searchTag')
            ->bind('admin_fields_search_tag');

        $controllers->get('/tags/{tagname}', 'admin.fields.controller:getTag')
            ->bind('admin_fields_show_tag');

        $controllers->get('/vocabularies', 'admin.fields.controller:listVocabularies')
            ->bind('admin_fields_list_vocabularies');

        $controllers->get('/vocabularies/{type}', 'admin.fields.controller:getVocabulary')
            ->bind('admin_fields_show_vocabulary');

        $controllers->get('/dc-fields', 'admin.fields.controller:listDcFields')
            ->bind('admin_fields_list_dc_fields');

        $controllers->get('/dc-fields/{name}', 'admin.fields.controller:getDcFields')
            ->bind('admin_fields_get_dc_fields');

        return $controllers;
    }

    public function updateFields(Application $app, Request $request, $sbas_id)
    {
        $json = array(
            'success'   => false,
            // use to store the updated collection
            'fields'    => array(),
            'messages'  => array()
        );

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $connection = $databox->get_connection();
        $data = $this->getFieldsJsonFromRequest($app, $request);

        // calculate max position
        try {
            $stmt = $connection->prepare('SELECT MAX(sorter) as max_position FROM metadatas_structure');
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $maxPosition = $row['max_position'] + 1;
        } catch (\PDOException $e) {
            $app->abort(500);
        }

        $connection->beginTransaction();
        $i = 0;
        foreach ($data as $jsonField) {
            try {
                $jsonField['sorter'] = $jsonField['sorter'] + $maxPosition;
                $field = \databox_field::get_instance($app, $databox, $jsonField['id']);
                $this->updateFieldWithData($app, $field, $jsonField);
                $field->save();
                $json['fields'][] = $field->toArray();
                $i++;
            } catch (\PDOException $e) {
                if ($e->errorInfo[1] == 1062) {
                    $json['messages'][] = _(sprintf('Field name %s already exists', $jsonField['name']));
                } else {
                    $json['messages'][] = _(sprintf('Field %s could not be saved, please retry or contact an administrator if problem persists', $jsonField['name']));
                }
            } catch (\Exception $e) {
                if ($e instanceof \Exception_Databox_metadataDescriptionNotFound || $e->getPrevious() instanceof TagUnknown) {
                    $json['messages'][] = _(sprintf('Provided tag %s is unknown', $jsonField['tag']));
                } else {
                    $json['messages'][] = _(sprintf('Field %s could not be saved, please retry or contact an administrator if problem persists', $jsonField['name']));
                }
            }
        }

        if ($i === count($data)) {
            // update field position in database, this query forces to update all fields each time
            $stmt = $connection->prepare(sprintf('UPDATE metadatas_structure SET sorter = (sorter - %s)', $maxPosition));
            $row = $stmt->execute();
            $stmt->closeCursor();

            $connection->commit();

            $json['success'] = true;
            $json['messages'][] = _('Fields configuration has been saved');

            // update field position in array
            array_walk($json['fields'], function(&$field) use ($maxPosition) {
               $field['sorter'] = $field['sorter'] - $maxPosition;
            });
        } else {
            $connection->rollback();
        }

        return $app->json($json);
    }

    public  function getLanguage(Application $app, Request $request)
    {
        return $app->json(array(
            'something_wrong'           => _('Something wrong happened, please try again or contact an admin if problem persists'),
            'deleted_success'           => _('%s field has been deleted with success'),
            'are_you_sure_delete'       => _('Do you really want to delete the field %s ?'),
            'validation_blank'          => _('Field can not be blank'),
            'validation_name_exists'    => _('Field name already exists'),
            'validation_tag_invalid'    => _('Field source is not valid'),
            'field_error'               => _('Field %s contains errors'),
        ));
    }

    public function displayApp(Application $app, Request $request, $sbas_id)
    {
        return  $app['twig']->render('/admin/fields/index.html.twig', array(
            'sbas_id' => $sbas_id
        ));
    }

    public function listDcFields(Application $app, Request $request)
    {
        $data = $app['serializer']->serialize(array_values(\databox::get_available_dcfields()), 'json');

        return new Response($data, 200, array('content-type' => 'application/json'));
    }

    public function listVocabularies(Application $app, Request $request)
    {
        $vocabularies = VocabularyController::getAvailable($app);

        return $app->json(array_map(function ($vocabulary) {
            return array(
                'type' => $vocabulary->getType(),
                'name' => $vocabulary->getName(),
            );
        }, $vocabularies));
    }

    public function getVocabulary(Application $app, Request $request, $type)
    {
        $vocabulary = VocabularyController::get($app, $type);

        return $app->json(array(
                'type' => $vocabulary->getType(),
                'name' => $vocabulary->getName(),
        ));
    }

    public function searchTag(Application $app, Request $request)
    {
        $term = trim(strtolower($request->query->get('term')));
        $res = array();

        if ($term) {
            $provider = new TagProvider();

            foreach ($provider->getLookupTable() as $namespace => $tags) {
                $ns = strpos($namespace, $term);

                foreach ($tags as $tagname => $datas) {
                    if ($ns === false && strpos($tagname, $term) === false) {
                        continue;
                    }

                    $res[] = array(
                        'id'    => $namespace . '/' . $tagname,
                        'label' => $datas['namespace'] . ' / ' . $datas['tagname'],
                        'value' => $datas['namespace'] . ':' . $datas['tagname'],
                    );
                }
            }
        }

        return $app->json($res);
    }

    public function getTag(Application $app, Request $request, $tagname)
    {
        $tag = \databox_field::loadClassFromTagName($tagname);
        $json = $app['serializer']->serialize($tag, 'json');

        return new Response($json, 200, array('Content-Type' => 'application/json'));
    }

    public function createField(Application $app, Request $request, $sbas_id) {
        $json = array(
            'success' => false,
            'message' => _('Something wrong happened, please try again or contact an admin if problem persists'),
            'field'   => array()
        );
        $headers = array();

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $data = $this->getFieldJsonFromRequest($app, $request);

        try {
            $field = \databox_field::create($app, $databox, $data['name'], $data['multi']);

            $this->updateFieldWithData($app, $field, $data);
            $field->save();

            $json['success'] = true;
            $headers['location'] = $app->path('admin_fields_show_field', array(
                'sbas_id' => $sbas_id,
                'id'      => $field->get_id(),
            ));
            $json['message'] = _(sprintf('Tag name %s has been created successfully', $data['name']));
            $json['field'] = $field->toArray();
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $json['message'] = _(sprintf('Field name %s already exists', $data['name']));
            }
        } catch (\Exception $e) {
            if ($e instanceof \Exception_Databox_metadataDescriptionNotFound || $e->getPrevious() instanceof TagUnknown) {
                $json['message'] = _(sprintf('Provided tag %s is unknown', $data['tag']));
            }
        }

        return $app->json($json, 201, $headers);
    }

    public function listFields(Application $app, $sbas_id) {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        return $app->json($databox->get_meta_structure()->toArray());
    }

    public function getField(Application $app, $sbas_id, $id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $id);

        return $app->json($field->toArray());
    }

    public function updateField(Application $app, Request $request, $sbas_id, $id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $id);
        $data = $this->getFieldJsonFromRequest($app, $request);

        $this->updateFieldWithData($app, $field, $data);
        $field->save();

        return $app->json($field->toArray());
    }

    public function deleteField(Application $app, $sbas_id, $id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        \databox_field::get_instance($app, $databox, $id)->delete();

        return new Response('', 204);
    }

    private function getFieldJsonFromRequest(Application $app, Request $request)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $app->abort(400, 'Body must contain a valid JSON payload');
        }

        $required = $this->getMandatoryFieldProperties();

        foreach ($required as $key) {
            if (false === array_key_exists($key, $data)) {
                $app->abort(400, sprintf('The entity must contain a key `%s`', $key));
            }
        }

        return $data;
    }

    private function getFieldsJsonFromRequest(Application $app, Request $request)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $app->abort(400, 'Body must contain a valid JSON payload');
        }

        $required = $this->getMandatoryFieldProperties();

        foreach($data as $field) {
            foreach ($required as $key) {
                if (false === array_key_exists($key, $field)) {
                    $app->abort(400, sprintf('The entity must contain a key `%s`', $key));
                }
            }
        }

        return $data;
    }

    private function updateFieldWithData(Application $app, \databox_field $field, array $data)
    {
        $field
            ->set_name($data['name'])
            ->set_thumbtitle($data['thumbtitle'])
            ->set_tag(\databox_field::loadClassFromTagName($data['tag']))
            ->set_business($data['business'])
            ->set_indexable($data['indexable'])
            ->set_required($data['required'])
            ->set_separator($data['separator'])
            ->set_readonly($data['readonly'])
            ->set_type($data['type'])
            ->set_tbranch($data['tbranch'])
            ->set_report($data['report'])
            ->setVocabularyControl(null)
            ->setVocabularyRestricted(false);

        if (isset($data['sorter'])) {
            $field->set_position($data['sorter']);
        }

        try {
            $vocabulary = VocabularyController::get($app, $data['vocabulary-type']);
            $field->setVocabularyControl($vocabulary);
            $field->setVocabularyRestricted($data['vocabulary-restricted']);
        } catch (\InvalidArgumentException $e) {

        }

        $dces_element = null;

        $class = '\databox_Field_DCES_' . $data['dces-element'];
        if (class_exists($class)) {
            $dces_element = new $class();
        }

        $field->set_dces_element($dces_element);
    }

    private function getMandatoryFieldProperties()
    {
        return array(
            'name', 'multi', 'thumbtitle', 'tag', 'business', 'indexable',
            'required', 'separator', 'readonly', 'type', 'tbranch', 'report',
            'vocabulary-type', 'vocabulary-restricted', 'dces-element'
        );
    }
}
