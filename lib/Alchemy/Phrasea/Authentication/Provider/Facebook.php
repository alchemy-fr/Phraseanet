<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Facebook extends AbstractProvider
{
    /** @var \Facebook\Facebook */
    private $facebook;


    public function __construct(UrlGenerator $generator, SessionInterface $session, \Facebook\Facebook $facebook)
    {
        parent::__construct($generator, $session);
        $this->facebook = $facebook;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $params = array()): RedirectResponse
    {
        $params = array_merge(['providerId' => $this->getId()], $params);

        return new RedirectResponse(
            $this->facebook->getRedirectLoginHelper()->getLoginUrl(
                $this->generator->generate(
                    'login_authentication_provider_callback',
                    $params,
                    UrlGenerator::ABSOLUTE_URL
                ),
                ['email']
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        $this->session->remove('fb_access_token');
    }

    /**
     * @param \Facebook\Facebook $facebook
     *
     * @return Facebook
     */
    public function setFacebook(\Facebook\Facebook $facebook)
    {
        $this->facebook = $facebook;

        return $this;
    }

    /**
     * @return \Facebook\Facebook
     */
    public function getFacebook(): \Facebook\Facebook
    {
        return $this->facebook;
    }

    /**
     * @param $dataToRetrieve
     * @return \Facebook\GraphNodes\GraphUser
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    protected function getGraphUser($dataToRetrieve)
    {
        try {
            $response = $this->facebook->get(
                "/me?fields=" . implode(',', $dataToRetrieve),
                $this->session->get('fb_access_token')
            );
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            throw new NotAuthenticatedException('Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            throw new NotAuthenticatedException('Facebook SDK returned an error: ' . $e->getMessage());
        }

        if (!$response) {
            throw new NotAuthenticatedException('Not authenticated');
        }

        return $response->getGraphUser();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $user = $this->getGraphUser(['id', 'name', 'email', 'picture', 'last_name', 'first_name']);
        
        $identity = new Identity();

        $identity->set(Identity::PROPERTY_ID, $user['id']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $user['picture']);
        $identity->set(Identity::PROPERTY_EMAIL, $user['email']);
        $identity->set(Identity::PROPERTY_FIRSTNAME, $user['first_name']);
        $identity->set(Identity::PROPERTY_LASTNAME, $user['last_name']);
        $identity->set(Identity::PROPERTY_USERNAME, $user['first_name']);

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        $helper = $this->facebook->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            throw new NotAuthenticatedException('Graph returned an error: ' . $e->getMessage());
        } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            throw new NotAuthenticatedException('Facebook SDK returned an error: ' . $e->getMessage());
        }

        if (! isset($accessToken)) {
            if ($helper->getError()) {
                $error  = "Error: " . $helper->getError() . "\n";
                $error .= "Error Code: " . $helper->getErrorCode() . "\n";
                $error .= "Error Reason: " . $helper->getErrorReason() . "\n";
                $error .= "Error Description: " . $helper->getErrorDescription() . "\n";

                throw new NotAuthenticatedException($error);

            } else {
                throw new NotAuthenticatedException('Facebook authentication failed');
            }
        }

        $oAuth2Client = $this->facebook->getOAuth2Client();

        if (! $accessToken->isLongLived()) {
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                throw new NotAuthenticatedException('Error getting long-lived access token: ' . $e->getMessage() );
            }
        }
        $this->session->set('fb_access_token', (string) $accessToken);
    }

    /**
     * {@inheritdoc}
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function getToken(): Token
    {
        $user = $this->getGraphUser(['id']);

        return new Token($this, $user['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getIconURI(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADEAAAAwCAYAAAC4w'
            . 'JK5AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2hpVFh0W'
            . 'E1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iV'
            . 'zVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iY'
            . 'WRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExI'
            . 'DY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSR'
            . 'EYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1ze'
            . 'W50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6e'
            . 'G1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0U'
            . 'mVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZ'
            . 'WYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtc'
            . 'E1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDowMjgwMTE3NDA3MjA2ODExO'
            . 'EMxNEY3NEJCM0UzNjU4QyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpFRjg3O'
            . 'UMwQTcxRUMxMUUyQjc5NzlGRUJDNjcwRkVDMyIgeG1wTU06SW5zdGFuY2VJRD0ie'
            . 'G1wLmlpZDpFRjg3OUMwOTcxRUMxMUUyQjc5NzlGRUJDNjcwRkVDMyIgeG1wOkNyZ'
            . 'WF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpIj4gPHhtc'
            . 'E1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDI4MDExN'
            . 'zQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
            . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6R'
            . 'GVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlb'
            . 'mQ9InIiPz59EDiBAAAEEUlEQVR42txaXUgUURQ+szu76pZlYhoJJoYaEgUJRUUUQ'
            . 'hBBL0FPUdBjEUFPPfTaSw9B0GsR0UuPkQ8FQdFDVFBKJGaFS6b4r6Vru87fvZ0zM'
            . '2uz7o6Ozr02duXszs4MM+e733fuOfdeFc45rPcWg/+gqfShKIr95RoBi3uOo9YYG'
            . 'snHyh+r7gXb4TsvP7RX1Wy7r2tmm6GbEEWlMcvsS/e8v3jr0tku/KmjGV4QiQ3Vt'
            . 'Q8nx+dapoZ/QTYzD1GMF1TNrmR5wz087EDLoGVVT2wk9RxrmRj6CRND06DPG1EFA'
            . 'cnyRBMe1rmnzDwIioEyPWdAdjYHWlaHqI5aHP/IP2y1xIKXCVtODP228INFfNh1O'
            . '3gzWor89sopzizEyZht66BVUAiQ36o3ZyAGIP/XionaLSk4sq8B2pq2QnVVCjZWJ'
            . 'G256IYFmmbC5/Qk3H3cvVR6oM6PeUGAhShsOTG5IBJqDM6f2gMdB3ZCPKb43pdB7'
            . 'QfxpQAEsUB6kxnU8XgMrl04DLub65a9NxdwhCxiguJCJhOnO5oDAbD9CagKHzkxa'
            . 'TI6ebQ18P2mxQL5sggE2MhlMdHaWAOVqbKS1wZHZ+DT11HIooQWzo3MrI4JmXKqr'
            . '60seX5sag6u3nwKhrk6BRSCMEmHTJqc1Hjporj/xzRoWHCGKsX/apA7eUISE9znu'
            . 'QQgzDtLyEkeE35JFGuEUO8sBEG1E5dXO/k+loerEgpBGAgAg4vYENHKkyqoarxgi'
            . 'C2ZADFrp8oSxQwhO97RKhAILFmABghLUExcOXcQjh9qXva+Y/ubbFvcevvH4fKNz'
            . 'pWB4IKr2LCyzOH8hq802RkIgrKkJQhE2NDKaUYgXxblCbFDbNhCMpPVVpmxBSa7s'
            . 'J3x2y7FV8iEPbOzKCYsISDedA/AbEZb+N3SVAPtbfVF96UHp+Hdx8Gi8129Q4F8K'
            . 'SoARU6Knr/+Ylu+nTmxtySInm8jcPvBKzF5wmYBkYhiotT8wG8UC/POEjEhbz7h9'
            . '9yw71wUE86LuDQQSzHBBDIhswD0eS4XyoRJPeJfMstlQmApbqGmmKTAXjomBAU2d'
            . '3uE838QE1xgYNsgZMnJpwzhYuXkSEmanKw1kBNNiJxyXNbMbg2Y4BxZQJMWE34gm'
            . 'NCYYHJjYi2GWKpf7JmdJCb85QTimHBWOpi01Q7fjB1yhUWF4jHWMQmt89lb20Q3t'
            . 'WBwMnIDHJQdTqdEd9/OMrKT+EXrntTbLL8QRD+s0b4n1zHAhpRYcqGGipqZ83NTk'
            . '+kXj2gdAZzNeEshPSqKQuvtm8DZG25E245WA87uZDxKJICz7UtMDKN9RxtTPRdpM'
            . 'kw79OP5eTo4O5RqhECYLgMzrp/kr6Z65KS7J8FFSyzQFmuU/kkl72fW9ZVM/yPAA'
            . 'LuRXOCVA9oFAAAAAElFTkSuQmCC';
    }

    /**
     * {@inheritdoc}
     *
     * @return self
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): self
    {
        foreach (['app-id', 'secret', 'default-graph-version'] as $parm) {
            if (!isset($options[$parm])) {
                throw new InvalidArgumentException(sprintf('Missing Facebook "%s" parameter in conf/authentication/providers', $parm));
            }
        }

        return new static(
            $generator,
            $session,
            new \Facebook\Facebook([
                'app_id' => $options['app-id'],
                'app_secret' => $options['secret'],
                'default_graph_version' => $options['default-graph-version']
            ])
        );
    }
}
