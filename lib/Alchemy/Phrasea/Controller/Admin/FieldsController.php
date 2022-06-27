<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Metadata\TagProvider;
use Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface;
use Assert\Assertion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FieldsController extends Controller
{
    public function updateFields(Request $request, $sbas_id)
    {
        $fields = [];
        $databox = $this->findDataboxById((int) $sbas_id);
        $metaStructure = $databox->get_meta_structure();
        $connection = $databox->get_connection();
        $data = $this->getFieldsJsonFromRequest($request);

        $connection->beginTransaction();

        foreach ($data as $jsonField) {
            try {
                $field = $metaStructure->get_element($jsonField['id']);

                if ($field->get_name() !== $jsonField['name']) {
                    $this->validateNameField($metaStructure, $jsonField);
                }

                $this->validateTagField($jsonField);

                $this->updateFieldWithData($field, $jsonField);
                $field->save();
                $fields[] = $field->toArray();
            } catch (\Exception $e) {
                $connection->rollback();
                $this->app->abort(500, $this->app->trans('Field %name% could not be saved, please try again or contact an admin.', ['%name%' => $jsonField['name']]));
                break;
            }
        }

        $connection->commit();

        return $this->app->json($fields);
    }

    public function getLanguage()
    {
        return $this->app->json([
            'something_wrong'           => $this->app->trans('Something wrong happened, please try again or contact an admin.'),
            'created_success'           => $this->app->trans('%s field has been created with success.'),
            'deleted_success'           => $this->app->trans('%s field has been deleted with success.'),
            'are_you_sure_delete'       => $this->app->trans('Do you really want to delete the field %s ?'),
            'validation_blank'          => $this->app->trans('Field can not be blank.'),
            'validation_name_exists'    => $this->app->trans('Field name already exists.'),
            'validation_name_invalid'   => $this->app->trans('Field name is not valid.'),
            'validation_tag_invalid'    => $this->app->trans('Field source is not valid.'),
            'field_error'               => $this->app->trans('Field %s contains errors.'),
            'fields_save'               => $this->app->trans('Your configuration has been successfuly saved.'),
        ]);
    }

    public function displayApp($sbas_id)
    {
        $languages = [];

        foreach ($this->app['locales.available'] as $code => $language) {
            $data = explode('_', $code);
            $languages[$data[0]] = $language;
        }

        return $this->render('/admin/fields/index.html.twig', [
            'sbas_id'   => $sbas_id,
            'languages' => $languages,
        ]);
    }

    public function listDcFields()
    {
        $data = $this->app['serializer']->serialize(array_values(\databox::get_available_dcfields()), 'json');

        return new Response($data, 200, ['content-type' => 'application/json']);
    }

    public function listVocabularies()
    {
        return $this->app->json(array_map(
            [$this, 'getVocabularyAsArray'],
            $this->fetchVocabularies()
        ));
    }

    /**
     * @return ControlProviderInterface[]
     */
    private function fetchVocabularies()
    {
        $vocabularies = $this->getVocabularies();

        $instances = array_map(
            function ($type) use ($vocabularies) {
                return $vocabularies[$type];
            },
            $vocabularies->keys()
        );

        Assertion::allIsInstanceOf($instances, ControlProviderInterface::class);

        return $instances;
    }

    /**
     * @param string $type
     * @return ControlProviderInterface
     */
    private function fetchVocabulary($type)
    {
        $vocabularies = $this->getVocabularies();

        $vocabulary = $vocabularies[strtolower($type)];

        Assertion::isInstanceOf($vocabulary, ControlProviderInterface::class);

        return $vocabulary;
    }

    private function getVocabularyAsArray(ControlProviderInterface $vocabulary)
    {
        return [
            'type' => $vocabulary->getType(),
            'name' => $vocabulary->getName(),
        ];
    }

    public function getVocabulary($type)
    {
        return $this->app->json($this->getVocabularyAsArray($this->fetchVocabulary($type)));
    }

    public function searchTag(Request $request)
    {
        $term = str_replace(['/', ':', '.'], ' ', strtolower($request->query->get('term')));
        $res = [];

        $term = explode(' ', $term, 2);
        if(($term[0] = trim($term[0])) != '') {
            if( ($nparts = count($term)) == 2) {
                $term[1] = trim($term[1]);
            }
            $provider = new TagProvider();

            foreach ($provider->getLookupTable() as $namespace => $tags) {
                $match_ns = (strpos($namespace, $term[0]) !== false);
                if($nparts == 2 && !$match_ns) {
                    // with "abc:xyz", "abc" MUST match the namespace
                    continue;
                }
                foreach ($tags as $tagname => $datas) {
                    if($nparts == 1) {
                        // "abc" can match the namespace OR the tagname
                        $match = $match_ns || (strpos($tagname, $term[0]) !== false);
                    }
                    else {
                        // match "abc:xyz" against namespace (already true) AND tagname
                        $match = ($term[1] == '' || strpos($tagname, $term[1]) !== false);
                    }

                    if($match) {
                        $res[] = [
                            'id' => $namespace . '/' . $tagname,
                            /** @Ignore */
                            'label' => $datas['namespace'] . ' / ' . $datas['tagname'],
                            'value' => $datas['namespace'] . ':' . $datas['tagname'],
                        ];
                    }
                }
            }
        }

        return $this->app->json($res);
    }

    public function getTag($tagname)
    {
        $tag = \databox_field::loadClassFromTagName($tagname);
        $json = $this->app['serializer']->serialize($tag, 'json');

        return new Response($json, 200, ['Content-Type' => 'application/json']);
    }

    public function createField(Request $request, $sbas_id)
    {
        $databox = $this->findDataboxById((int) $sbas_id);
        $data = $this->getFieldJsonFromRequest($request);

        $metaStructure = $databox->get_meta_structure();
        $this->validateNameField($metaStructure, $data);
        $this->validateTagField($data);

        try {
            $field = \databox_field::create($this->app, $databox, $data['name']);
            $this->updateFieldWithData($field, $data);
            $field->save();
        } catch (\Exception $e) {
            throw new HttpException(500, $this->app->trans(
                'Field %name% could not be created, please try again or contact an admin.',
                ['%name%' => $data['name']]
            ));
        }

        return $this->app->json($field->toArray(), 201, [
            'location' => $this->app->path('admin_fields_show_field', [
                'sbas_id' => $sbas_id,
                'id' => $field->get_id(),
            ])]);
    }

    public function listFields($sbas_id)
    {
        $databox = $this->findDataboxById((int) $sbas_id);

        return $this->app->json($databox->get_meta_structure()->toArray());
    }

    public function getField($sbas_id, $id)
    {
        $databox = $this->findDataboxById((int) $sbas_id);
        $field = $databox->get_meta_structure()->get_element($id);

        return $this->app->json($field->toArray());
    }

    public function updateField(Request $request, $sbas_id, $id)
    {
        $databox = $this->findDataboxById((int) $sbas_id);
        $field = $databox->get_meta_structure()->get_element($id);
        $data = $this->getFieldJsonFromRequest($request);

        $this->validateTagField($data);

        if ($field->get_name() !== $data['name']) {
            $metaStructure = $databox->get_meta_structure();
            $this->validateNameField($metaStructure, $data);
        }

        $this->updateFieldWithData($field, $data);
        $field->save();

        return $this->app->json($field->toArray());
    }

    public function deleteField($sbas_id, $id)
    {
        $databox = $this->findDataboxById((int) $sbas_id);
        $databox->get_meta_structure()->get_element($id)->delete();

        return new Response('', 204);
    }

    private function getFieldJsonFromRequest(Request $request)
    {
        $data = $this->requestBodyToJson($request);
        $required = $this->getMandatoryFieldProperties();

        foreach ($required as $key) {
            if (false === array_key_exists($key, $data)) {
                $this->app->abort(400, sprintf('The entity must contain a key `%s`', $key));
            }
        }

        return $data;
    }

    private function getFieldsJsonFromRequest(Request $request)
    {
        $data = $this->requestBodyToJson($request);
        $required = $this->getMandatoryFieldProperties();

        foreach ($data as $field) {
            foreach ($required as $key) {
                if (false === array_key_exists($key, $field)) {
                    $this->app->abort(400, sprintf('The entity must contain a key `%s`', $key));
                }
            }
        }

        return $data;
    }

    private function updateFieldWithData(\databox_field $field, array $data)
    {
        $field
            ->set_name($data['name'])
            ->set_thumbtitle($data['thumbtitle'])
            ->set_tag(\databox_field::loadClassFromTagName($data['tag']))
            ->set_business($data['business'])
            ->set_aggregable($data['aggregable'])
            ->set_indexable($data['indexable'])
            ->set_multi($data['multi'])
            ->set_required($data['required'])
            ->set_separator($data['separator'])
            ->set_readonly($data['readonly'])
            ->set_type($data['type'])
            ->set_tbranch($data['tbranch'])
            ->set_generate_cterms($data['generate_cterms'])
            ->set_gui_editable($data['gui_editable'])
            ->set_gui_visible($data['gui_visible'])
            ->set_printable($data['printable'])
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
            $vocabulary = $this->fetchVocabulary($data['vocabulary-type']);
            $field->setVocabularyControl($vocabulary);
            $field->setVocabularyRestricted($data['vocabulary-restricted']);
        } catch (\InvalidArgumentException $e) {
            // Invalid vocabulary requested
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
            'required', 'separator', 'readonly', 'gui_editable', 'gui_visible' , 'printable', 'type', 'tbranch', 'generate_cterms', 'report',
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

    /**
     * @return ControlProviderInterface[]|\Pimple
     */
    private function getVocabularies()
    {
        return $this->app['vocabularies'];
    }
}
