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

use Alchemy\Phrasea\Controller\Controller;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\TagFactory;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Reader;

class SubdefsController extends Controller
{
    public function metadataAction($databox_id, $record_id, $subdef_name)
    {
        $record = new \record_adapter($this->app, (int) $databox_id, (int) $record_id);
        $metadataBag = new MetadataBag();

        try {
            $fileEntity = $this->getExifToolReader()
                ->files($record->get_subdef($subdef_name)->getRealPath())
                ->first();
            $metadatas = $fileEntity->getMetadatas();
            foreach($metadatas as $metadata){
                $valuedata = $fileEntity->executeQuery($metadata->getTag()->getTagname()."[not(@rdf:datatype = 'http://www.w3.org/2001/XMLSchema#base64Binary')]");
                if(empty($valuedata)){
                    $valuedata = new Mono($this->app->trans('Binary data'));
                    $tag = TagFactory::getFromRDFTagname($metadata->getTag()->getTagname());
                    $metadataBagElement = new Metadata($tag, $valuedata);
                    $metadataBag->set($metadata->getTag()->getTagname(), $metadataBagElement);
                }else{
                    $metadataBag->set($metadata->getTag()->getTagname(), $metadata);
                }
            }
        } catch (PHPExiftoolException $e) {
            // ignore
        } catch (\Exception_Media_SubdefNotFound $e) {
            // ignore
        }

        return $this->render('prod/actions/Tools/metadata.html.twig', [
            'record'   => $record,
            'metadatas' => $metadataBag,
            'subdef_name' => $subdef_name
        ]);
    }

    /**
     * @return Reader
     */
    private function getExifToolReader()
    {
        return $this->app['exiftool.reader'];
    }
}
