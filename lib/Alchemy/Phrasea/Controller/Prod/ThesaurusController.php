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
use Symfony\Component\HttpFoundation\Request;

class ThesaurusController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use FilesystemAware;

    public function dropRecordsAction(Request $request): string
    {
        $sbas_id = $request->get('sbas_id');
        $tx_term_id = $request->get('tx_term_id');
        $records = RecordsRequest::fromRequest($this->app, $request, RecordsRequest::FLATTEN_YES_PRESERVE_STORIES, [ACL::CANMODIFRECORD]);

        // twig parameters
        $twp = [
            'error'        => null,
            'dlg_level'    => $request->get('dlg_level'),
            //    'fields' => [],    // fields the can receive the value
            //    'fvalue' => 'Europe',
            'lst'          => $records->serializedList(),
            'received_cnt' => $records->received()->count(),
            'rejected_cnt' => $records->rejected()->count(),
            'up_paths'     => [],
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

            $upPaths = [];
            $xpath = new DOMXPath($domth);
            foreach ($dbox->get_meta_structure() as $field) {
                if (!($q = $field->get_tbranch())) {
                    continue;
                }
                //$fieldName = $field->get_name();
                $roots = $xpath->query($q);     // linked nodes for this field
                $q = '(' . $q . ')//sy[@id=\'' . $tx_term_id . '\']';   // can we find the term under the tbranch(es) ?
                // normally we should find only one linked parent, since we search from a unique term
                // ...but... a bad th could link a field onto 2 points of the same branch :
                // root
                //   |---A        <-- "keyword" is linked here...
                //       |---B    <-- ...but also linked here (bad idea btw)
                //           |---terms
                // going up, we decide to stop at the first link (B) (easier)
                if (($droppedSy = $xpath->query($q))->length > 0) {
                    // yes (and since the query targets a unique id, there is only one result)
                    $droppedSy = $droppedSy->item(0);
                    /** @var DOMElement $droppedSy */
                    $droppedlng = $droppedSy->getAttribute('lng');    // the lng of the dropped term is prefered

                    // go from the sy upto a linked branch (possibly multiples if the field is linked to many branches)

                    $ok = true;     // == the term (level up-to top) can populate the current field
                    for ($te = $droppedSy->parentNode; $te->nodeType === XML_ELEMENT_NODE; $te = $te->parentNode) {
                        /** @var DOMElement $te */
                        $teid = $te->getAttribute('id');
                        for ($i = 0; $i < $roots->length; $i++) {
                            if ($te->isSameNode($roots->item($i))) {
                                $ok = false;    // we met the link point, upmost terms are not "values" anymore
                            }
                        }
                        if ($ok) {       // acceptable value for the current field
                            if (!array_key_exists($teid, $upPaths)) {
                                $upPaths[$teid] = [
                                    'synonyms' => [],
                                    'fields'   => []
                                ];
                                // get all the sy so the user can choose which is prefered
                                $preferedId = null;
                                foreach ($te->childNodes as $sy) {
                                    if ($sy->nodeName != 'sy') {
                                        continue;   // skip 'te' children
                                    }
                                    $lng = $sy->getAttribute('lng');
                                    $id = $sy->getAttribute('id');
                                    $s = [
                                        'value'    => $sy->getAttribute('v'),
                                        'lng'      => $lng,
                                        'selected' => false
                                    ];
                                    // this sy is prefered if...
                                    if ($sy->getAttribute('lng') === $droppedlng) {
                                        $preferedId = $id;     // ... it has the same lng as the dropped
                                    }
                                    if ($sy->isSameNode($droppedSy)) {
                                        $preferedId = $id;     // ... better : it was the dropped target
                                    }
                                    $upPaths[$teid]['synonyms'][$id] = $s;
                                }
                                if ($preferedId) {
                                    $upPaths[$teid]['synonyms'][$preferedId]['selected'] = true;
                                }
                            }
                            $upPaths[$teid]['fields'][$field->get_id()] = $field;
                        }
                    }
                    $twp['up_paths'] = array_reverse($upPaths);
                    $twp['fields'][] = $field;
                    // $field->
                }
            }

            if (empty($upPaths)) {
                // no fields (could happen if one drops on a top-level branch, or if the th is not linked, or...)
                throw new Exception("this branch is not linked");
            }

        }
        catch (Exception $e) {
            $twp['error'] = $e->getMessage();
        }

        return $this->render('prod/Thesaurus/droppedrecords.html.twig', $twp);
    }
}
