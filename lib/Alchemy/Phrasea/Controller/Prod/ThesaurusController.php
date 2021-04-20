<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use ACL;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use DOMElement;
use DOMXPath;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ThesaurusController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use FilesystemAware;

    public function dropRecordsAction(Request $request): JsonResponse
    {
        $sbas_id = $request->get('sbas_id');
        $tx_term_id = $request->get('tx_term_id');

        $records = RecordsRequest::fromRequest($this->app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, [ACL::CANMODIFRECORD]);

        // array of sbid/rid. too bad we cannot array_map on arraycollection
        $recRefs = [];
        foreach($records as $r) {
            $recRefs[] = [
                'sbas_id'=>$r->getDataboxId(),
                'record_id'=>$r->getRecordId()
            ];
        }

        // twig parameters
        $twp = [
            'error'        => null,
            'dlg_level'    => $request->get('dlg_level'),
            'received_cnt' => $records->received()->count(),
            'rejected_cnt' => $records->rejected()->count(),
            'by_fields'    => [],
        ];

        // find which field(s) can be updated, that is what tbranches are linked to a parent of the term

        try {
            if($records->count() === 0) {
                // no record is editable
                throw new Exception("you don't have rights to edit those records");
            }
            $dbox = $this->app->getApplicationBox()->get_databox($sbas_id);

            if (!($domth = $dbox->get_dom_thesaurus())) {
                throw new Exception("error fetching th");
            }

            $xpath = new DOMXPath($domth);

            $fields = [];
            foreach ($dbox->get_meta_structure() as $field) {
                if($field->is_readonly() || !$field->get_gui_editable()) {
                    continue;
                }
                if (!($q = $field->get_tbranch())) {
                    continue;
                }
                $roots = $xpath->query($q);     // linked nodes for this field
                $q = '(' . $q . ')//sy[@id=\'' . $tx_term_id . '\']';   // can we find the term under the tbranch(es) ?
                // normally we should find only one linked parent, since we search from a unique term
                // ...but... a bad th could link a field onto 2 points of the same branch :
                // root
                //   |---A        <-- "keyword" is linked here...
                //       |---B    <-- ...but also linked here (bad idea btw)
                //           |---terms
                // going up, we decide to stop at the first link (B) (easier)
                $droppedSy = $xpath->query($q);
                if ($droppedSy->length > 0) {
                    // yes this field is linked to a branch that contains the term
                    // since the query targets a unique id, there is only one result
                    $droppedSy = $droppedSy->item(0);
                    /** @var DOMElement $droppedSy */
                    $droppedlng = $droppedSy->getAttribute('lng');    // the lng of the dropped term is prefered

                    $values = [];

                    // go from the sy upto a linked branch (possibly multiples if the field is linked to many branches)

                    $depth = 0;     // 0 for dropped-on level, will decrease while going up
                    $selectedValue = null;
                    for ($te = $droppedSy->parentNode; $te->nodeType === XML_ELEMENT_NODE; $te = $te->parentNode, $depth--) {
                        /** @var DOMElement $te */

                        for ($i = 0; $i < $roots->length; $i++) {
                            if ($te->isSameNode($roots->item($i))) {
                                // we hit the link point, upmost terms are not "values" anymore
                                break 2;        // no need to go higher
                            }
                        }

                        // here acceptable value for the current field

                        if($depth === 0) {
                            //
                            // dropped-on level : we accept the exact term (not searching synonyms)
                            //
                            $selectedValue = [
                                'value'    => $droppedSy->getAttribute('v'),
                                'lng'      => $droppedlng,
                                'selected' => true
                            ];
                            $values[$droppedSy->getAttribute('id')] = $selectedValue;
                        }
                        else {
                            //
                            // upper level : get all the sy so the user can choose which is prefered
                            //

                            //   first, see if at least one sy is matching the lng
                            $lngFound = false;
                            foreach ($te->childNodes as $sy) {
                                if ($sy->nodeName == 'sy' && $sy->getAttribute('lng') == $droppedlng) {
                                    $lngFound = true;
                                    break;
                                }
                            }

                            //  then rescan sy to add to the values
                            foreach ($te->childNodes as $sy) {
                                if ($sy->nodeName != 'sy') {
                                    continue;   // skip 'te' children
                                }
                                $lng = $sy->getAttribute('lng');
                                if (!$lngFound || $lng == $droppedlng) {
                                    $values[$sy->getAttribute('id')] = [
                                        'value'    => $sy->getAttribute('v'),
                                        'lng'      => $lng,
                                        'selected' => $sy->isSameNode($droppedSy)
                                    ];
                                }
                            }
                        }
                    }

                    if(!empty($values)) {
                        $fields[$field->get_name()] = [
                            'field'  => $field,
                            'values' => array_reverse($values),
                            'selected_value' => $selectedValue,
                        ];
                    }

                }
            }

            if(empty($fields)) {
                // no fields (could happen if one drops on a top-level branch, or if the th is not linked, or...)
                throw new Exception("this branch is not linked");
            }
            $twp['by_fields'] = $fields;

        }
        catch (Exception $e) {
            $twp['error'] = $e->getMessage();
        }

        $zzz = $this->render('prod/Thesaurus/droppedrecords.html.twig', $twp);

        return $this->app->json([
            'dlg_title'   => $this->app->trans('thesaurus::edit editing %count% record(s)', ['%count%'=> $records->received()->count()]),
            'dlg_content' => $this->render('prod/Thesaurus/droppedrecords.html.twig', $twp),
            'rec_refs'    => $recRefs,
            'commit_url'  => $this->app->url('prod_edit_applyJSAction')
        ]);
    }
}
