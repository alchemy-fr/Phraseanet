<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use caption_field;
use databox_field;
use Exception;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class V3RecordController extends Controller
{
    use JsonBodyAware;
    use DispatcherAware;


    /**
     * Return detailed information about one story
     *
     * @param  Request $request
     * @param  int     $databox_id
     * @param  int     $record_id
     *
     * @return Response
     */
    public function indexAction_patch(Request $request, $databox_id, $record_id)
    {
        $struct = $this->findDataboxById($databox_id)->get_meta_structure();
        $record = $this->findDataboxById($databox_id)->get_record($record_id);

        //$record->set_metadatas()

        //setRecordStatusAction

        try {
            $b = $this->decodeJsonBody($request);
        }
        catch (Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction($request, 'Bad JSON');
        }

        $debug = [
            'metadatas_ops' => null,
            'sb_ops' => null,
        ];
        try {
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
        catch (Exception $e) {
            return $this->app['controller.api.v1']->getBadRequestAction(
                $request,
                $e->getMessage()
            );
        }

        // @todo Move event dispatch inside record_adapter class (keeps things encapsulated)
        $this->dispatch(PhraseaEvents::RECORD_EDIT, new RecordEdit($record));

        $ret = $this->getResultHelpers()->listRecord($request, $record, $this->getAclForUser());

        return Result::create($request, $ret)->createResponse();
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
     * @param $record
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
}
