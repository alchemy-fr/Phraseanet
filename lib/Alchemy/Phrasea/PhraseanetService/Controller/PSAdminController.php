<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;

class PSAdminController extends Controller
{
    public function indexAction(PhraseaApplication $app)
    {
        return $this->render('admin/phraseanet-service/index.html.twig');
    }

}
