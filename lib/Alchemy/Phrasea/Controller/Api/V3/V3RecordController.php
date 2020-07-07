<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Checker\Response as CheckerResponse;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\LazaretAttribute;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use caption_field;
use databox_field;
use Exception;
use Guzzle\Http\Client as Guzzle;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use record_adapter;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class V3RecordController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    /**
     * GET record
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function indexAction_GET(Request $request, $databox_id, $record_id)
    {
        try {
            $record = $this->findDataboxById($databox_id)->get_record($record_id);
            $r = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());

            return Result::create($request, $r)->createResponse();
        }
        catch (NotFoundHttpException $e) {
            return Result::createError($request, 404, 'record Not Found')->createResponse();
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, $e->getMessage());
        }
    }

    /**
     * POST record
     *
     * @param  Request $request
     * @param  int     $base_id
     *
     * @return Response
     */
    public function indexAction_POST(Request $request, $base_id)
    {
        $body = $this->decodeJsonBody($request);

        $collection = \collection::getByBaseId($this->app, $base_id);

        if (!$this->getAclForUser()->has_right_on_base($base_id, \ACL::CANADDRECORD)) {
            return Result::createError($request, 403, sprintf(
                'You do not have access to collection %s', $collection->get_label($this->app['locale'])
            ))->createResponse();
        }

        $ret = [];

        $uploadedFilename = $originalName = $newPathname = null;    // will be set if a file is uploaded

        if (count($request->files->get('file')) == 0) {
            if(count($request->get('url')) == 1) {
                // upload by url
                $url = $request->get('url');
                $pi = pathinfo($url);   // filename, extension

                /** @var TemporaryFilesystemInterface $tmpFs */
                $tmpFs = $this->app['temporary-filesystem'];
                $tempfile = $tmpFs->createTemporaryFile('download_', null, $pi['extension']);

                try {
                    $guzzle = new Guzzle($url);
                    $res = $guzzle->get("", [], ['save_to' => $tempfile])->send();
                }
                catch (\Exception $e) {
                    return Result::createBadRequest($request, sprintf('Error "%s" downloading "%s"', $e->getMessage(), $url));
                }

                if($res->getStatusCode() !== 200) {
                    return Result::createBadRequest($request, sprintf('Error %s downloading "%s"', $res->getStatusCode(), $url));
                }

                $originalName = $pi['filename'] . '.' . $pi['extension'];
                $uploadedFilename = $newPathname = $tempfile;
            }
        }
        else {
            // upload by file
            $file = $request->files->get('file');
            if (!$file instanceof UploadedFile) {
                return Result::createBadRequest($request, 'You can upload one file at time');
            }
            if (!$file->isValid()) {
                return Result::createBadRequest($request, 'Data corrupted, please try again');
            }

            $uploadedFilename = $file->getPathname();
            $originalName = $file->getClientOriginalName();
            $newPathname = $file->getPathname() . '.' . $file->getClientOriginalExtension();

            if (false === rename($file->getPathname(), $newPathname)) {
                return Result::createError($request, 403, 'Error while renaming file')->createResponse();
            }
        }

        if($newPathname) {
            // a file is included
            $media = $this->app->getMediaFromUri($newPathname);

            $Package = new File($this->app, $media, $collection, $originalName);

            if ($request->get('status')) {
                $Package->addAttribute(new Status($this->app, $request->get('status')));
            }

            $session = new LazaretSession();
            $session->setUser($this->getAuthenticatedUser());

            $entityManager = $this->app['orm.em'];
            $entityManager->persist($session);
            $entityManager->flush();

            $reasons = $output = null;

            $translator = $this->app['translator'];
            $callback = function ($element, Visa $visa) use ($translator, &$reasons, &$output) {
                if (!$visa->isValid()) {
                    $reasons = array_map(function (CheckerResponse $response) use ($translator) {
                        return $response->getMessage($translator);
                    }, $visa->getResponses());
                }

                $output = $element;
            };

            switch ($request->get('forceBehavior')) {
                case '0' :
                    $behavior = Manager::FORCE_RECORD;
                    break;
                case '1' :
                    $behavior = Manager::FORCE_LAZARET;
                    break;
                case null:
                    $behavior = null;
                    break;
                default:
                    return Result::createBadRequest($request, sprintf(
                        'Invalid forceBehavior value `%s`', $request->get('forceBehavior')
                    ));
            }

            $nosubdef = $request->get('nosubdefs') === '' || \p4field::isyes($request->get('nosubdefs'));
            $this->getBorderManager()->process($session, $Package, $callback, $behavior, $nosubdef);

            // remove $newPathname on temporary directory
            if ($newPathname !== $uploadedFilename) {
                @rename($newPathname, $uploadedFilename);
            }

            $ret = ['entity' => null];

            if ($output instanceof \record_adapter) {
                /** @var record_adapter $output */
                try {
                    $this->apply_body($body, $output);
                }
                catch (Exception $e) {
                    return Result::createBadRequest($request, $e->getMessage());
                }

                $ret['url'] = '/records/' . $output->getDataboxId() . '/' . $output->getRecordId() . '/';
                $this->dispatch(PhraseaEvents::RECORD_UPLOAD, new RecordEdit($output));
            }
            elseif ($output instanceof LazaretFile) {

                // keep the json body as an attribute of lazaret file
                /** @var LazaretFile $output */
                $attribute = new LazaretAttribute();
                $attribute->setName('_patch_')
                    ->setValue(json_encode($body))
                    ->setLazaretFile($output);
                $output->addAttribute($attribute);
                $this->app['orm.em']->persist($attribute);

                $ret['url'] = '/quarantine/item/' . $output->getId() . '/';
            }
        }
        else {
            // no file was included, just create a record
            $output = record_adapter::create($collection, $this->app);
            try {
                $this->apply_body($body, $output);
                $ret['url'] = '/records/' . $output->getDataboxId() . '/' . $output->getRecordId() . '/';
            }
            catch (Exception $e) {
                return Result::createBadRequest($request, $e->getMessage());
            }
        }

        return Result::create($request, $ret)->createResponse();
    }


    /**
     * PATCH record
     *
     * @param Request $request
     * @param int $databox_id
     * @param int $record_id
     *
     * @return Response
     */
    public function indexAction_PATCH(Request $request, $databox_id, $record_id)
    {
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        try {
            $body = $this->decodeJsonBody($request);
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, 'Bad JSON');
        }

        try {
            $this->apply_body($body, $record);
        }
        catch (Exception $e) {
            return Result::createBadRequest($request, $e->getMessage());
        }

        // @todo Move event dispatch inside record_adapter class (keeps things encapsulated)
        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));

        $ret = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());

        return Result::create($request, $ret)->createResponse();
    }

    /**
     * @param stdClass $b
     * @param record_adapter $record
     * @throws Exception
     */
    private function apply_body(stdClass $b, record_adapter $record)
    {
        $struct = $record->getDatabox()->get_meta_structure();
        $debug = [
            'metadatas_ops' => null,
            'sb_ops' => null,
        ];

        // do metadatas ops
        if (is_array($b->metadatas)) {
            $debug['metadatas_ops'] = $this->do_metadatas($struct, $record, $b->metadatas);
        }
        // do sb ops
        if (is_array($b->status)) {
            $debug['sb_ops'] = $this->do_status($record, $b->status);
        }
        if(!is_null($b->base_id)) {
            $debug['coll_ops'] = $this->do_collection($record, $b->base_id);
        }
    }

    /**
     * @param record_adapter $record
     * @param $base_id
     */
    private function do_collection(record_adapter $record, $base_id)
    {
        $record->move_to_collection($this->getApplicationBox()->get_collection($base_id));
    }


    //////////////////////////////////
    /// TODO : keep multi-values uniques !
    /// it should be done in record_adapter
    //////////////////////////////////

    /**
     * @param databox_field[] $struct
     * @param record_adapter $record
     * @param $metadatas
     * @return array
     * @throws Exception
     */
    private function do_metadatas($struct, record_adapter $record, $metadatas)
    {
        $structByKey = [];
        $allStructFields = [];
        foreach ($struct as $f) {
            $allStructFields[$f->get_id()] = $f;
            $structByKey[$f->get_id()]   =  &$allStructFields[$f->get_id()];
            $structByKey[$f->get_name()] = &$allStructFields[$f->get_id()];
        }

        $metadatas_ops = [];
        foreach ($metadatas as $_m) {
            // sanity
            if($_m->meta_struct_id && $_m->field_name) {
                throw new Exception("define meta_struct_id OR field_name, not both.");
            }
            // select fields that match meta_struct_id or field_name (can be arrays)
            $fields_list = null;    // to filter caption_fields from record, default all
            $struct_fields = [];    // struct fields that match meta_struct_id or field_name
            $field_keys = $_m->meta_struct_id ? $_m->meta_struct_id : $_m->field_name;  // can be null if none defined (=match all)
            if($field_keys !== null) {
                if (!is_array($field_keys)) {
                    $field_keys = [$field_keys];
                }
                $fields_list = [];
                foreach ($field_keys as $k) {
                    if(array_key_exists($k, $structByKey)) {
                        $fields_list[] = $structByKey[$k]->get_name();
                        $struct_fields[$structByKey[$k]->get_id()] = $structByKey[$k];
                    }
                    else {
                        throw new Exception(sprintf("unknown field (%s).", $k));
                    }
                }
            }
            else {
                // no meta_struct_id, no field_name --> match all struct fields !
                $struct_fields = $allStructFields;
            }
            $caption_fields = $record->get_caption()->get_fields($fields_list, true);

            $meta_id = is_null($_m->meta_id) ? null : (int)($_m->meta_id);

            if(!($match_method = (string)($_m->match_method))) {
                $match_method = 'ignore_case';
            }
            if(!in_array($match_method, ['strict', 'ignore_case', 'regexp'])) {
                throw new Exception(sprintf("bad match_method (%s).", $match_method));
            }

            $values = [];
            if(is_array($_m->value)) {
                foreach ($_m->value as $v) {
                    if(($v = trim((string)$v)) !== '') {
                        $values[] = $v;
                    }
                }
            }
            else {
                if(($v = trim((string)($_m->value))) !== '') {
                    $values[] = $v;
                }
            }

            if(!($action = (string)($_m->action))) {
                $action = 'set';
            }

            switch ($_m->action) {
                case 'set':
                    $ops = $this->metadata_set($struct_fields, $caption_fields, $meta_id, $values);
                    break;
                case 'add':
                    $ops = $this->metadata_add($struct_fields, $values);
                    break;
                case 'delete':
                    $ops = $this->metadata_replace($caption_fields, $meta_id, $match_method, $values, null);
                    break;
                case 'replace':
                    if (!is_string($_m->replace_with) && !is_null($_m->replace_with)) {
                        throw new Exception("bad \"replace_with\" for action \"replace\".");
                    }
                    $ops = $this->metadata_replace($caption_fields, $meta_id, $match_method, $values, $_m->replace_with);
                    break;
                default:
                    throw new Exception(sprintf("bad action (%s).", $action));
            }

            $metadatas_ops = array_merge($metadatas_ops, $ops);
        }

        $record->set_metadatas($metadatas_ops, true);

        return $metadatas_ops;
    }

    /**
     * @param record_adapter $record
     * @param $statuses
     * @return array
     * @throws Exception
     */
    private function do_status(record_adapter $record, $statuses)
    {
        $datas = strrev($record->getStatus());

        foreach ($statuses as $status) {
            $n = (int)($status->bit);
            $value = (int)($status->state);
            if ($n > 31 || $n < 4) {
                throw new Exception(sprintf("Invalid status bit number (%s).", $n));
            }
            if ($value < 0 || $value > 1) {
                throw new Exception(sprintf("Invalid status bit state (%s) for bit (%s).", $value, $n));
            }

            $datas = substr($datas, 0, ($n)) . $value . substr($datas, ($n + 1));
        }

        $record->setStatus(strrev($datas));

        return ["status" => $this->getResultHelpers()->listRecordStatus($record)];
    }

    private function match($pattern, $method, $value)
    {
        switch ($method) {
            case 'strict':
                return $value === $pattern;
            case 'ignore_case':
                return strtolower($value) === strtolower($pattern);
            case 'regexp':
                return preg_match($pattern, $value) == 1;
        }
        return false;
    }

    /**
     * @param databox_field[] $struct_fields   struct-fields (from struct) matching meta_struct_id or field_name
     * @param caption_field[] $caption_fields caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string[] $values
     *
     * @return array                            ops to execute
     * @throws Exception
     */
    private function metadata_set($struct_fields, $caption_fields, $meta_id, $values)
    {
        $ops = [];

        // if one field was multi-valued and no meta_id was set, we must delete all values
        foreach ($caption_fields as $cf) {
            foreach ($cf->get_values() as $field_value) {
                if (is_null($meta_id) || $field_value->getId() === (int)$meta_id) {
                    $ops[] = [
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => ''
                    ];
                }
            }
        }
        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if($sf->is_multi()) {
                //  add the non-null value(s)
                foreach ($values as $value) {
                    if ($value) {
                        $ops[] = [
                            'meta_struct_id' => $sf->get_id(),
                            'meta_id'        => $meta_id,  // can be null
                            'value'          => $value
                        ];
                    }
                }
            }
            else {
                // mono-valued
                if(count($values) > 1) {
                    throw new Exception(sprintf("setting mono-valued (%s) requires only one value.", $sf->get_name()));
                }
                if( ($value = $values[0]) ) {
                    $ops[] = [
                        'meta_struct_id' => $sf->get_id(),
                        'meta_id'        => $meta_id,  // probably null,
                        'value'          => $value
                    ];
                }
            }
        }

        return $ops;
    }

    /**
     * @param databox_field[] $struct_fields struct-fields (from struct) matching meta_struct_id or field_name
     * @param string[] $values
     *
     * @return array                            ops to execute
     * @throws Exception
     */
    private function metadata_add($struct_fields, $values)
    {
        $ops = [];

        // now set values to matching struct_fields
        foreach ($struct_fields as $sf) {
            if(!$sf->is_multi()) {
                throw new Exception(sprintf("can't \"add\" to mono-valued (%s).", $sf->get_name()));
            }
            foreach ($values as $value) {
                $ops[] = [
                    'meta_struct_id' => $sf->get_id(),
                    'meta_id'        => null,
                    'value'          => $value
                ];
            }
        }

        return $ops;
    }

    /**
     * @param caption_field[] $caption_fields  caption-fields (from record) matching meta_struct_id or field_name (or all if not set)
     * @param int|null $meta_id
     * @param string $match_method              "strict" | "ignore_case" | "regexp"
     * @param string[] $values
     * @param string|null $replace_with
     *
     * @return array                            ops to execute
     */
    private function metadata_replace($caption_fields, $meta_id, $match_method, $values, $replace_with)
    {
        $ops = [];

        $replace_with = trim((string)$replace_with);

        foreach ($caption_fields as $cf) {
            // match all ?
            if(is_null($meta_id) && count($values) == 0) {
                foreach ($cf->get_values() as $field_value) {
                    $ops[] = [
                        'meta_struct_id' => $cf->get_meta_struct_id(),
                        'meta_id'        => $field_value->getId(),
                        'value'          => $replace_with
                    ];
                }
            }
            // match by meta-id ?
            if (!is_null($meta_id)) {
                foreach ($cf->get_values() as $field_value) {
                    if ($field_value->getId() === $meta_id) {
                        $ops[] = [
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $replace_with
                        ];
                    }
                }
            }
            // match by value(s) ?
            foreach ($values as $value) {
                foreach ($cf->get_values() as $field_value) {
                    $rw = $replace_with;
                    if($match_method=='regexp' && $rw != '') {
                        $rw = preg_replace($value, $rw, $field_value->getValue());
                    }
                    if ($this->match($value, $match_method, $field_value->getValue())) {
                        $ops[] = [
                            'meta_struct_id' => $cf->get_meta_struct_id(),
                            'meta_id'        => $field_value->getId(),
                            'value'          => $rw
                        ];
                    }
                }
            }
        }

        return $ops;
    }

    /**
     * @return V3ResultHelpers
     */
    private function getResultHelpers()
    {
        return $this->app['controller.api.v3.resulthelpers'];
    }

    /**
     * @return Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
    }

}
