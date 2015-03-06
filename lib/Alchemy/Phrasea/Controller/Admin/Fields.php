<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Metadata\TagProvider;
use Alchemy\Phrasea\Vocabulary\Controller as VocabularyController;
use JMS\TranslationBundle\Annotation\Ignore;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Fields implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['admin.fields.controller'] = $this;

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
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
        $fields = [];
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $metaStructure = $databox->get_meta_structure();
        $connection = $databox->get_connection();
        $data = $this->getFieldsJsonFromRequest($app, $request);

        $connection->beginTransaction();

        foreach ($data as $jsonField) {
            try {
                $field = \databox_field::get_instance($app, $databox, $jsonField['id']);

                if ($field->get_name() !== $jsonField['name']) {
                    $this->validateNameField($metaStructure, $jsonField);
                }

                $this->validateTagField($jsonField);

                $this->updateFieldWithData($app, $field, $jsonField);
                $field->save();
                $fields[] = $field->toArray();
            } catch (\Exception $e) {
                $connection->rollback();
                $app->abort(500, $app->trans('Field %name% could not be saved, please try again or contact an admin.', ['%name%' => $jsonField['name']]));
                break;
            }
        }

        $connection->commit();

        return $app->json($fields);
    }

    public function getLanguage(Application $app, Request $request)
    {
        return $app->json([
            'something_wrong'           => $app->trans('Something wrong happened, please try again or contact an admin.'),
            'created_success'           => $app->trans('%s field has been created with success.'),
            'deleted_success'           => $app->trans('%s field has been deleted with success.'),
            'are_you_sure_delete'       => $app->trans('Do you really want to delete the field %s ?'),
            'validation_blank'          => $app->trans('Field can not be blank.'),
            'validation_name_exists'    => $app->trans('Field name already exists.'),
            'validation_name_invalid'   => $app->trans('Field name is not valid.'),
            'validation_tag_invalid'    => $app->trans('Field source is not valid.'),
            'field_error'               => $app->trans('Field %s contains errors.'),
            'fields_save'               => $app->trans('Your configuration has been successfuly saved.'),
        ]);
    }

    public function displayApp(Application $app, Request $request, $sbas_id)
    {
        $languages = [];

        foreach ($app['locales.available'] as $code => $language) {
            $data = explode('_', $code);
            $languages[$data[0]] = $language;
        }

        return $app['twig']->render('/admin/fields/index.html.twig', [
            'sbas_id'   => $sbas_id,
            'languages' => $languages,
        ]);
    }

    public function listDcFields(Application $app, Request $request)
    {
        $data = $app['serializer']->serialize(array_values(\databox::get_available_dcfields()), 'json');

        return new Response($data, 200, ['content-type' => 'application/json']);
    }

    public function listVocabularies(Application $app, Request $request)
    {
        $vocabularies = VocabularyController::getAvailable($app);

        return $app->json(array_map(function ($vocabulary) {
            return [
                'type' => $vocabulary->getType(),
                'name' => $vocabulary->getName(),
            ];
        }, $vocabularies));
    }

    public function getVocabulary(Application $app, Request $request, $type)
    {
        $vocabulary = VocabularyController::get($app, $type);

        return $app->json([
            'type' => $vocabulary->getType(),
            'name' => $vocabulary->getName(),
        ]);
    }

    public function searchTag(Application $app, Request $request)
    {
        $term = trim(strtolower($request->query->get('term')));
        $res = [];

        if ($term) {
            $provider = new TagProvider();

            foreach ($provider->getLookupTable() as $namespace => $tags) {
                $ns = strpos($namespace, $term);

                foreach ($tags as $tagname => $datas) {
                    if ($ns === false && strpos($tagname, $term) === false) {
                        continue;
                    }

                    $res[] = [
                        'id'    => $namespace . '/' . $tagname,
                        /** @Ignore */
                        'label' => $datas['namespace'] . ' / ' . $datas['tagname'],
                        'value' => $datas['namespace'] . ':' . $datas['tagname'],
                    ];
                }
            }
        }

        return $app->json($res);
    }

    public function getTag(Application $app, Request $request, $tagname)
    {
        $tag = \databox_field::loadClassFromTagName($tagname);
        $json = $app['serializer']->serialize($tag, 'json');

        return new Response($json, 200, ['Content-Type' => 'application/json']);
    }

    public function createField(Application $app, Request $request, $sbas_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $data = $this->getFieldJsonFromRequest($app, $request);

        $metaStructure = $databox->get_meta_structure();
        $this->validateNameField($metaStructure, $data);
        $this->validateTagField($data);

        try {
            $field = \databox_field::create($app, $databox, $data['name'], $data['multi']);
            $this->updateFieldWithData($app, $field, $data);
            $field->save();
        } catch (\Exception $e) {
            $app->abort(500, $app->trans('Field %name% could not be created, please try again or contact an admin.', ['%name%' => $data['name']]));
        }

        return $app->json($field->toArray(), 201, [
            'location' => $app->path('admin_fields_show_field', [
                'sbas_id' => $sbas_id,
                'id' => $field->get_id()
        ])]);
    }

    public function listFields(Application $app, $sbas_id)
    {
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

        $this->validateTagField($data);

        if ($field->get_name() !== $data['name']) {
            $metaStructure = $databox->get_meta_structure();
            $this->validateNameField($metaStructure, $data);
        }

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
        $data = $this->requestBodyToJson($request);
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
        $data = $this->requestBodyToJson($request);
        $required = $this->getMandatoryFieldProperties();

        foreach ($data as $field) {
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
            ->set_aggregable($data['aggregable'])
            ->set_indexable($data['indexable'])
            ->set_required($data['required'])
            ->set_separator($data['separator'])
            ->set_readonly($data['readonly'])
            ->set_type($data['type'])
            ->set_tbranch($data['tbranch'])
            ->set_report($data['report'])
            ->setVocabularyControl(null)
            ->setVocabularyRestricted(false);

        foreach ($data['labels'] as $code => $label) {
            $field->set_label($code, $label);
        }

        if (isset($data['sorter'])) {
            $field->set_position($data['sorter']);
        }

        try {
            $vocabulary = VocabularyController::get($app, $data['vocabulary-type']);
            $field->setVocabularyControl($vocabulary);
            $field->setVocabularyRestricted($data['vocabulary-restricted']);
        } catch (\InvalidArgumentException $e) {

        }

        if ('' !== $dcesElement = (string) $data['dces-element']) {
            $class = sprintf('\databox_Field_DCES_%s', $dcesElement);

            if (!class_exists($class)) {
                throw new BadRequestHttpException(sprintf('DCES element %s does not exist.', $dcesElement));
            }

            $field->set_dces_element(new $class());
        }
    }

    private function getMandatoryFieldProperties()
    {
        return [
            'name', 'multi', 'thumbtitle', 'tag', 'business', 'indexable', 'aggregable',
            'required', 'separator', 'readonly', 'type', 'tbranch', 'report',
            'vocabulary-type', 'vocabulary-restricted', 'dces-element', 'labels'
        ];
    }

    private function validateNameField(\databox_descriptionStructure $metaStructure, array $field)
    {
        if (null !== $metaStructure->get_element_by_name($field['name'])) {
            throw new BadRequestHttpException(sprintf('Field %s already exists.', $field['name']));
        }
    }

    private function validateTagField(array $field)
    {
        try {
            \databox_field::loadClassFromTagName($field['tag'], true);
        } catch (\Exception_Databox_metadataDescriptionNotFound $e) {
            throw new BadRequestHttpException(sprintf('Provided tag %s is unknown.', $field['tag']));
        }
    }

    private function requestBodyToJson(Request $request)
    {
        $body = $request->getContent();
        $data = @json_decode($body, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException('Body must contain a valid JSON payload.');
        }

        return $data;
    }
}
