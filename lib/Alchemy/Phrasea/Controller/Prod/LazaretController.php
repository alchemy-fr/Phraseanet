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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Border;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Media\SubdefSubstituer;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Manipulator\LazaretManipulator;
use Alchemy\Phrasea\Model\Repositories\LazaretFileRepository;
use PHPExiftool\Driver\Metadata\Metadata;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LazaretController extends Controller
{
    use DataboxLoggerAware;
    use DelivererAware;
    use EntityManagerAware;
    use FilesystemAware;
    use SubDefinitionSubstituerAware;

    /**
     * List all elements in lazaret
     *
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function listElement(Request $request)
    {
        $baseIds = array_keys($this->getAclForUser()->get_granted_base([\ACL::CANADDRECORD]));

        $lazaretFiles = null;
        $perPage = 10;
        $page = max(1, $request->query->get('page', 1));
        $offset = ($page - 1) * $perPage;

        if (count($baseIds) > 0) {
            $lazaretFiles = $this->getLazaretFileRepository()->findPerPage($baseIds, $offset, $perPage);
        }

        return $this->render('prod/upload/lazaret.html.twig', [
            'lazaretFiles' => $lazaretFiles,
            'currentPage'  => $page,
            'perPage'      => $perPage,
        ]);
    }

    /**
     * Get one lazaret Element
     *
     * @param int $file_id A lazaret element id
     *
     * @return Response
     */
    public function getElement($file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];
        
        /* @var LazaretFile $lazaretFile */
        $lazaretFile = $this->getLazaretFileRepository()->find($file_id);

        if (null === $lazaretFile) {
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $this->app->json($ret);
        }

        $ret['result'] = [
            'filename' => $lazaretFile->getOriginalName(),
            'base_id'  => $lazaretFile->getBaseId(),
            'created'  => $lazaretFile->getCreated()->format(\DateTime::ATOM),
            'updated'  => $lazaretFile->getUpdated()->format(\DateTime::ATOM),
            'pathname' => $this->app['tmp.lazaret.path'].'/'.$lazaretFile->getFilename(),
            'sha256'   => $lazaretFile->getSha256(),
            'uuid'     => $lazaretFile->getUuid(),
        ];
        $ret['success'] = true;

        return $this->app->json($ret);
    }

    /**
     * Add an element to phraseanet
     *
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * parameters   : 'bas_id'            int     (mandatory) : The id of the destination collection
     *                'keep_attributes'   boolean (optional)  : Keep all attributes attached to the lazaret element
     *                'attributes'        array   (optional)  : Attributes id's to attach to the lazaret element
     *
     * @return Response
     */
    public function addElement(Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        //Mandatory parameter
        if (null === $request->request->get('bas_id')) {
            $ret['message'] = $this->app->trans('You must give a destination collection');

            return $this->app->json($ret);
        }

        //Optional parameter
        $keepAttributes = !!$request->request->get('keep_attributes', false);
        $attributesToKeep = $request->request->get('attributes', []);

        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->add($file_id, $keepAttributes, $attributesToKeep);

        return $this->app->json($ret);
    }

    /**
     * Delete a lazaret element
     *
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function denyElement($file_id)
    {
        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->deny($file_id);

        return $this->app->json($ret);
    }

    /**
     * Empty lazaret
     *
     * @param Request     $request
     *
     * @return Response
     */
    public function emptyLazaret(Request $request)
    {
        $maxTodo = -1;  // all
        if($request->get('max') !== null) {
            $maxTodo = (int)($request->get('max'));
        }
        if( $maxTodo <= 0) {
            $maxTodo = -1;      // all
        }

        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->clear($maxTodo);

        return $this->app->json($ret);
    }

    /**
     * Substitute a record element by a lazaret element
     *
     * @param Request     $request The current request
     * @param int         $file_id A lazaret element id
     *
     * @return Response
     */
    public function acceptElement(Request $request, $file_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        //Mandatory parameter
        if (null === $recordId = $request->request->get('record_id')) {
            $ret['message'] = $this->app->trans('You must give a destination record');

            return $this->app->json($ret);
        }

        /** @var LazaretFile $lazaretFile */
        $lazaretFile = $this->getLazaretFileRepository()->find($file_id);

        if (null === $lazaretFile) {
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $this->app->json($ret);
        }

        $found = false;

        //Check if the chosen record is eligible to the substitution
        foreach ($lazaretFile->getRecordsToSubstitute($this->app) as $record) {
            if ($record->getRecordId() !== (int) $recordId) {
                continue;
            }

            $found = true;
            break;
        }

        if (!$found) {
            $ret['message'] = $this->app->trans('The destination record provided is not allowed');

            return $this->app->json($ret);
        }

        $path = $this->app['tmp.lazaret.path'] . '/';
        $lazaretFileName = $path .$lazaretFile->getFilename();
        $lazaretThumbFileName = $path .$lazaretFile->getThumbFilename();

        try {
            $media = $this->app->getMediaFromUri($lazaretFileName);

            $record = $lazaretFile->getCollection($this->app)->get_databox()->get_record($recordId);
            $this->getSubDefinitionSubstituer()
                ->substitute($record, 'document', $media);
            $this->getDataboxLogger($record->getDatabox())->log(
                $record,
                \Session_Logger::EVENT_SUBSTITUTE,
                'HD',
                ''
            );

            //Delete lazaret file
            $manager = $this->getEntityManager();
            $manager->remove($lazaretFile);
            $manager->flush();

            $ret['success'] = true;
        } catch (\Exception $e) {
            $ret['message'] = $this->app->trans('An error occured');
        }

        try {
            $this->getFilesystem()->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {

        }

        return $this->app->json($ret);
    }

    /**
     * Get the associated lazaret element thumbnail
     *
     * @param int $file_id A lazaret element id
     *
     * @return Response
     */
    public function thumbnailElement($file_id)
    {
        /** @var LazaretFile $lazaretFile */
        $lazaretFile = $this->getLazaretFileRepository()->find($file_id);

        if (null === $lazaretFile) {
            return new Response(null, 404);
        }

        $lazaretThumbFileName = $this->app['tmp.lazaret.path'].'/'.$lazaretFile->getThumbFilename();

        return $this->deliverFile(
            $lazaretThumbFileName,
            $lazaretFile->getOriginalName(),
            DeliverDataInterface::DISPOSITION_INLINE,
            'image/jpeg'
        );
    }

    /**
     * @return LazaretFileRepository
     */
    private function getLazaretFileRepository()
    {
        return $this->app['repo.lazaret-files'];
    }

    /**
     * @return Border\Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
    }
}
