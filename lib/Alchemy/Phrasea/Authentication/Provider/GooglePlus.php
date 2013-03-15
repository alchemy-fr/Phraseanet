<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Provider\Token\Token;
use Alchemy\Phrasea\Authentication\Provider\Token\Identity;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GooglePlus extends AbstractProvider
{
    private $client;
    private $guzzle;
    private $plus;

    public function __construct(UrlGenerator $generator, SessionInterface $session, \Google_Client $google, ClientInterface $guzzle)
    {
        $this->generator = $generator;
        $this->session = $session;
        $this->client = $google;
        $this->guzzle = $guzzle;

        $this->plus = new \Google_PlusService($this->client);

        $this->client->setScopes(array(
            'https://www.googleapis.com/auth/plus.me',
            'https://www.googleapis.com/auth/userinfo.email',
        ));

        $this->client->setRedirectUri(
            $this->generator->generate(
                'login_authentication_provider_callback', array(
                    'providerId' => $this->getId(),
                ), UrlGenerator::ABSOLUTE_URL
            )
        );

        $this->client->setApprovalPrompt("auto");

        if ($this->session->has('google-plus.provider.token')) {
            $this->client->setAccessToken($this->session->get('google-plus.provider.token'));
        }
    }

    /**
     * @param ClientInterface $client
     *
     * @return Github
     */
    public function setGuzzleClient(ClientInterface $client)
    {
        $this->guzzle = $client;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getGuzzleClient()
    {
        return $this->guzzle;
    }

    /**
     * @param \Google_Client $client
     *
     * @return GooglePlus
     */
    public function setGoogleClient(\Google_Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return \Google_Client
     */
    public function getGoogleClient()
    {
        return $this->client;
    }

    /**
     * @param \Google_PlusService $plus
     *
     * @return GooglePlus
     */
    public function setGooglePlusService(\Google_PlusService $plus)
    {
        $this->plus = $plus;

        return $this;
    }

    /**
     * @return \Google_PlusService
     */
    public function getGooglePlusService()
    {
        return $this->plus;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 'google-plus';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Google +';
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $state = $this->createState();

        $this->session->set('google-plus.provider.state', $state);
        $this->client->setState($state);

        return new RedirectResponse($this->client->createAuthUrl());
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        if (!$this->session->has('google-plus.provider.state')) {
            throw new RuntimeException('Invalid state value ; CSRF try ?');
        }

        if ($request->query->get('state') !== $this->session->remove('google-plus.provider.state')) {
            throw new RuntimeException('Invalid state value ; CSRF try ?');
        }

        $this->client->authenticate($request->query->get('code'));

        $token = json_decode($this->client->getAccessToken(), true);
        $ticket = $this->client->verifyIdToken($token['id_token']);

        $this->session->set('google-plus.provider.token', json_encode($token));
        $this->session->set('google-plus.provider.id', $ticket->getUserId());
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        if (!ctype_digit($this->session->get('google-plus.provider.id'))) {
            throw new RuntimeException('Google + has not authenticated');
        }

        return new Token($this, $this->session->get('google-plus.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        $identity = new Identity();

        $token = json_decode($this->session->get('google-plus.provider.token'), true);
        $request = $this->guzzle->get(sprintf(
            'https://www.googleapis.com/oauth2/v1/tokeninfo?%s',
            http_build_query(array('access_token' => $token['access_token']), '', '&')
        ));
        $response = $request->send();

        if (200 !== $response->getStatusCode()) {
            throw new RuntimeException('Error while retrieving user info');
        }

        try{
            $plusData = $this->plus->people->get('me');
        } catch (\Google_Exception $e) {
            throw new RuntimeException('Error while retrieving user info', $e->getCode(), $e);
        }

        $data = json_decode($response->getBody(true), true);

        $identity->set(Identity::PROPERTY_EMAIL, $data['email']);

        $identity->set(Identity::PROPERTY_FIRSTNAME, $plusData['name']['givenName']);
        $identity->set(Identity::PROPERTY_ID, $plusData['id']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $plusData['image']['url']);
        $identity->set(Identity::PROPERTY_LASTNAME, $plusData['name']['familyName']);

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconURI()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADEAAAAwCAYAAAC4w'
        . 'JK5AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2hpVFh0WE1MO'
        . 'mNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ'
        . '2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6b'
        . 'WV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgM'
        . 'jAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJod'
        . 'HRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZ'
        . 'XNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZ'
        . 'S5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL'
        . '3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZ'
        . 'G9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZ'
        . 'DowMjgwMTE3NDA3MjA2ODExOEMxNEY3NEJCM0UzNjU4QyIgeG1wTU06RG9jdW1lbnRJR'
        . 'D0ieG1wLmRpZDo2N0M5MkQ3OTcxRUUxMUUyQjc5NzlGRUJDNjcwRkVDMyIgeG1wTU06S'
        . 'W5zdGFuY2VJRD0ieG1wLmlpZDo2N0M5MkQ3ODcxRUUxMUUyQjc5NzlGRUJDNjcwRkVDM'
        . 'yIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpI'
        . 'j4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDI4M'
        . 'DExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
        . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6RGVzY'
        . '3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiP'
        . 'z6uF8PaAAAL1UlEQVR42txae4wdVRn/zdzXPu/d1227W5aWPizQFrolXZAFS3hUpICAC'
        . 'oLVNlU0NcVoiTEm/mEqGMVUVBqEolFEBUQbiokIAo1Q6COlrZTutrRLu9vd7pbtvu7uf'
        . 'c7j+J2ZM/eemZ177xITX5N8e86dM3PO9/ve58wqjDH8r18q/g+uIP+jKIrVCOLAAlL/v'
        . '+ligkwiQ7Q2CIfhvr6+9ubm5l+rqvqRk0MZzHvkPaA5RJAEvjxWC3nhzWMZbLq2AVtun'
        . 'O27cm73biSuvNJ61OHCkLgxpX6pe9TqOvBKG/A5+pkh0oh0hfsEaYJLPKzr+ruBQGA+X'
        . '/jp3YO4Z9txYG4Vve1hPN9XCvLpz+DAd5ag7fxqXyDDK1Ygt3+/1TeLkOHz2wua00eBh'
        . 'dSMEyWJsqrkG2EHAL8GEprdsWamV3kAyIuImDeVgpg4IHruyX3DRe0gfPPNtu597MNpF'
        . 'YkcJTPpGQcYXc1EMaIKbvoOCO4DEZcJGMyzimIDsfpMIjF7Uxg/PTSK4UndH0RHBxTPP'
        . 'UViVJGW8j7j048T1fJpuQJUaTwkvxwJ0pAhMeoiRawoWv5MBclhMIsDvZP+EWTBAktSx'
        . 'aTPPGNKEY8W96NCC5xnRTangPxSvCZUmN10TMrbd0ysAOaNbn8QTPInVWLSy7RXI8yjN'
        . 'WGSEQHAmipYLGe0NtBzXBucUc6A4kifSStJrPDfoQD29qZ9QSiG4csYfJiWmPXVkldRw'
        . 'WKOePF5FJXqyeRSNF2l4jFOaXlFmjekYChlWNaleOzBOHXKYkzxMQ+HaW+fTcPMSmbse'
        . 'E0Yay6mAPBBpqAFeUbmMSnB0UjOgGZMddHczp0uiSvCfsPCQ+tEuIlKHhsoYV7T0gS/1'
        . 'qyYgd++NGA7eF63ir9MFGY9F6EmGJg6l/6n7agRTDpXlmiU6GxdBNnKAGUtkikzEUgai'
        . 'CayaJAY5EY6JhLDhwLx8aX1WHppPQ53Ul5pqRKGKlSiSGCcTE6md15tCKrXlh77Heq6O'
        . 'q3s9GrHbHTOacTp+gqciVWgl+h4LIw0BUqdXlNJw1W6ifmJHFrGMoiPZzFrNI25p0dw0'
        . 'e5+tJgfEgS/fnBLK1bvOQc0UPwPBwvJzXFsp88Nc1zDJ5fE3BOcHMSrP1qD7fctx74FM'
        . '7B/NumjKmRrl5uiRlxlDSkHARM019nGSvKxeppXeEpaw8V3TGB53wTw8G6PEdhlRxV3A'
        . '+qf8gOy6dmTePhX3cAiMoaQ6o5MHATPmafTqG6pxMADS1FbYdtTwsjiU4/eiVf0E/TuU'
        . 'mCSqoCUBlVjNm8M7ujmm86Z7Xb8heqQLYA7nttAA51EPURDLk0kciai4am+/uO7LsBkU'
        . 'scTf+gljVDojYftFMMBpEhDAxmoTRV4ZePCPICzmQRu3b4B+3KHgRmXINifcE/qmIV3P'
        . '8PcAKzoQ43Knx+jtci09FLR6YatnTh5LuNrVtvWL8TW+y9C+zwq8IZyVPCRq51Joy6i4'
        . 't5PtODoQ5fiirl28bd36Dgu+81nsK9nFwKxxQiks+SvrECG0zfd9wx53PQ8S0SAmY/KX'
        . 'OaEL+w5VRnK4edrFmDtNc1F/eTt7gm8N5hGtCKIyxdG0RQtKPT1gSNY+dQdloQDDQvI9'
        . 'nWfbMWKx01W3ryMr79W3JwwrxLp99JYt+Vd/OL1Qdy6rBHXUoRqm1NDVVYh4lw2v9Yi7'
        . '9U91oeVz6yzTCVQTwWxrk0FUM58GMqaV+noxCNFjByH6qZd/xjBrt0UlRojmN9ajZnRM'
        . 'KojASR6Urhr9Wx8Y9VUTf3k4LMkly4EWjtormxp6X4o7bDimW4KCB72dPFkIxWJTSR9i'
        . 'iTdJyfRnaP7WQKZMbH5ywt9J+ubHCIvq6Q6SaN1zfIMytKd7jgrV3aYcmUqQPGtKSUwr'
        . 'hHe1rU1YNXimC+I2+ZdTfE8AT0zKUoSmoQXfoYp+qa7bxpSXyImxpjhuSfasiDy9ZBPb'
        . 'USA0mM5DDm7Ps+1dvFqbLjp+7Sv6IKRHBYllmmTKbVW35D60n1OhhgzzEIr+haQaWvCt'
        . 'V8QoMgnsqeS+GvXeNHI9eh19+OhOx+ltHsO5nCPvcQU6Rtu6bs0ZfhozITCyRozyoEwB'
        . 'cNe9TqaoX6Fggef7ytZqnyz/fN44YvPobKiAeaZLnpdEXMZPkybBbMrAk7J91mhGC2tC'
        . 'anU5k4+odvleD/Vjz1EGR3HXujFmm0nUOrs8Jb5V+HI117G4jntYKffscwh72cOOUzJD'
        . 'JqF+4ppk1crZQpAxk8IKLVnrSjE9821rTWYNyOG1sYwmusqMCteiUUNlQhWRnw3KPJ1Q'
        . 'W0c79z3F1zzxD144+CfgeZF9I7iCZnFQ6vriLVEdHKDOEqSDmloa2vEygvrcfmFdVh1S'
        . 'RwN1f/aOenr9/4eVzz+Wew99CIUAmJHnhKhtRjTjJUHse6uZtoIxXEdZWn3NQ7t9IvIj'
        . 'XVBT/XDTA+CZSn6ZMl5NR3h+ApEr3ycduz1RcHs+cozaHnwagz0H4PS1GqbBjy73FJ5o'
        . 'YQmSpbiyRNPItn9NLSh/dASw9bJlXVmELAPe1QhApYiBTbFUfexbQjPvq0okH2Dx3D5A'
        . 'yupnK4jwBF/AL7acJuVueVdV+3ku8c2Uu9jYEcHzuxYh0TnS9AmhxGoiiAQrUKglqimC'
        . 'kpVFViY2gj9bqyBNjaEoRduR/Lgt4qCaJ+1CDd13A2cOyOqAx42Tau1I5Xk9DrLPzPlX'
        . 'rmDAm3sEE4+tQxjXW8hGA0hGCMlhSrJjFUiZhHTbYITNanmUmuqoVBiH975EMZ2FtfG2'
        . 'ktWgz/IqLpVGCtkYeaTmS3fMadm7XIgep+/AZnhCYSbiHEWsBkXqYI5KcNJJwKUST8ML'
        . 'rFgFYL1YSTe3oGRl6/3BdHGHTsap/IkXZCyLktehFR5zPCMlQKR6NqKyd5zCBMjBkmXM'
        . '2eKlG84zFKri9YUcZ0Zjlb4xiVIZXgE4wdexcTb901ZcG6sGfXVFACyual1Uz4fMN+8k'
        . 'R8rBWKi5wmQ8Eniql17SfWXxaCoyeBolhVM2QLsEE0bqFUwtncrjMQx14KhQBBV3Kn5l'
        . 'wZTOpg2i7RyteDcKwVCT49YJ5x5E5KrBMEoj2b5AtMoVAt5wGKbiXAFcrStTh5/zLVgM'
        . 'pfG2GTCDnGmT/b2trIGipQdQXfpZFhhVKcHVee00rTPBJh0/Ep6sk8r7PwLptpnyvlwq'
        . 'YiTFi6YRK9rwSND7yM5SvuOQMjNEJvGZmk6myIlGIOWGYAaEeehrHC4p0qHf873FQVWj'
        . 'rFOIphaOMFRrWeYrSXTvfKbPQeprCGNNzXbkvVjshSAcpui6pabkEvZNYtj305flwtay'
        . 'Sf4KYTpmJgYswOKAoN8N1A907XgL3f90VpWtfg3i/uCWWKsFIjGZd9FOEYbnxG+vVQLF'
        . 'bJpM+vYP4RPyM7NgciOb6ZylCBJMIu+lJ9/+9G/48ibf6N9fKOwc8/HOkPaUZp+YwJIK'
        . 'RBqqBYLP70VGoXwNFWy3DMcodjRT4RZ4V+Oc+cjmXVGRC2pLfmBiWjbagrXl+Wr/PU/2'
        . '2DVKgo5jGtH52oN/90gK5xRlUt2rO7Cr2Lx+i3Wl51Ef5b2y1req/NadkKpZHa8b5A/5'
        . 'NJZJAc01C1ZhFnX78hPvOx7N2L8/RNQGhqFBLzfd6UtcdGx8powR0dHD/NOw5JNWL5pD'
        . '+KXtlGkUjAxqCF1TkNuQoOW1SgRGiRtg8aIcjr5kYYsmWBqIEfaUBDvWIUL1h6l2e0jz'
        . 'at+eDsO73oNSnwW1TWGzYju2RQVC7WGO7xS4h3xfC3OV7H8I17Dxo0b2zdv3vxIfX39e'
        . 'Q6y1NABDB/cionT+6CND0JPUlme0vNfg0IUKcOxKMK0Aao9fzma2r+NcEOb9e6u3oO4e'
        . '8t69HUeAWbOpNSgFiRZpEItta8gsxrBofFncCL5Ft05zk+JiEYcEGHx/YOHkjlEHEQjD'
        . '1jeD5L/wYtLP8W/6/NP/0R823CWV0tBaXed45UHr8/FPf4ZtGo63zD+zSD4UcsHgld+z'
        . 'GjIILJiAOLhIfGp9b/ln1QcQacEnxPit/lPAQYAZxcBzaHB/oIAAAAASUVORK5CYII=';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options)
    {
        $client = new \Google_Client();

        $client->setApplicationName('Phraseanet');
        $client->setClientId($options['client-id']);
        $client->setClientSecret($options['client-secret']);

        return new GooglePlus($generator, $session, $client, new Guzzle());
    }
}
