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

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DownloadController extends Controller
{
    use DispatcherAware;

    /**
     * Download a set of documents
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function checkDownload(Request $request)
    {
        $lst = $request->request->get('lst');
        $ssttid = $request->request->get('ssttid', '');
        $subdefs = $request->request->get('obj', []);

        $download = new \set_export($this->app, $lst, $ssttid);

        if (0 === $download->get_total_download()) {
            $this->app->abort(403);
        }

        $list = $download->prepare_export(
            $this->getAuthenticatedUser(),
            $this->app['filesystem'],
            $subdefs,
            $request->request->get('type') === 'title' ? true : false,
            $request->request->get('businessfields'),
            $request->request->get('stamp_choice') === "NO_STAMP" ? \set_export::NO_STAMP : \set_export::STAMP_SYNC
        );

        $list['export_name'] = sprintf('%s.zip', $download->getExportName());
        $token = $this->getTokenManipulator()->createDownloadToken($this->getAuthenticatedUser(), serialize($list));
        $this->getDispatcher()->dispatch(PhraseaEvents::EXPORT_CREATE, new ExportEvent(
            $this->getAuthenticatedUser(), $ssttid, $lst, $subdefs, $download->getExportName())
        );

        return $this->app->redirectPath('prepare_download', ['token' => $token->getValue()]);
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }
}
