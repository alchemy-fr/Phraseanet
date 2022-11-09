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
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Github extends AbstractProvider
{
    private $client;

    private $key;
    private $secret;


    public function __construct(UrlGenerator $generator, SessionInterface $session, array $options, ClientInterface $client)
    {
        parent::__construct($generator, $session);

        $this->client = $client;
        $this->key = $options['client-id'];
        $this->secret = $options['client-secret'];
    }

    /**
     * @param ClientInterface $client
     *
     * @return Github
     */
    public function setGuzzleClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getGuzzleClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $params = array()): RedirectResponse
    {
        $params = array_merge(['providerId' => $this->getId()], $params);

        $state = $this->createState();

        $this->session->set('github.provider.state', $state);

        return new RedirectResponse('https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id' => $this->key,
            'scope' => 'user,user:email',
            'state' => $state,
            'redirect_uri' => $this->generator->generate(
                'login_authentication_provider_callback',
                $params,
                UrlGenerator::ABSOLUTE_URL
            ),
        ], '', '&'));
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        // GitHub does not support tokens revocation
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        if (!$this->session->has('github.provider.state')) {
            throw new NotAuthenticatedException('No state value in session ; CSRF try ?');
        }

        if ($request->query->get('state') !== $this->session->remove('github.provider.state')) {
            throw new NotAuthenticatedException('Invalid state value ; CSRF try ?');
        }

        try {
            $guzzleRequest = $this->client->post('access_token');

            $guzzleRequest->addPostFields([
                'code' => $request->query->get('code'),
                'redirect_uri' => $this->generator->generate(
                    'login_authentication_provider_callback',
                    ['providerId' => $this->getId()],
                    UrlGenerator::ABSOLUTE_URL
                ),
                'client_id' => $this->key,
                'client_secret' => $this->secret,
            ]);
            $guzzleRequest->setHeader('Accept', 'application/json');
            $response = $guzzleRequest->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while getting access_token');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Error while retrieving user info, unable to parse JSON.');
        }

        $this->session->remove('github.provider.state');
        $this->session->set('github.provider.access_token', $data['access_token']);

        try {
            $request = $this->client->get('https://api.github.com/user');
            $request->getQuery()->add('access_token', $data['access_token']);
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        $data = @json_decode($response->getBody(true), true);

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info, invalid status code.');
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Error while retrieving user info, unable to parse JSON.');
        }

        $this->session->set('github.provider.id', $data['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        if ('' === trim($this->session->get('github.provider.id'))) {
            throw new NotAuthenticatedException('Github has not authenticated');
        }

        return new Token($this, $this->session->get('github.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $identity = new Identity();

        try {
            $request = $this->client->get('https://api.github.com/user');
            $request->getQuery()->add('access_token', $this->session->get('github.provider.access_token'));
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Error while retrieving user info', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Error while parsing json');
        }

        list($firstname, $lastname) = explode(' ', $data['name'], 2);

        $identity->set(Identity::PROPERTY_EMAIL, $data['email']);
        $identity->set(Identity::PROPERTY_FIRSTNAME, $firstname);
        $identity->set(Identity::PROPERTY_ID, $data['id']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $data['avatar_url']);
        $identity->set(Identity::PROPERTY_LASTNAME, $lastname);

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconURI(): string
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
        . 'D0ieG1wLmRpZDowMUNGRURDNTgzRUUxMUUyQjNFMDk5RUI0NTk2RTdBMiIgeG1wTU06S'
        . 'W5zdGFuY2VJRD0ieG1wLmlpZDowMUNGRURDNDgzRUUxMUUyQjNFMDk5RUI0NTk2RTdBM'
        . 'iIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpI'
        . 'j4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6ODUwR'
        . 'DRBRkIxQjIwNjgxMThDMTRGNzRCQjNFMzY1OEMiIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
        . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6RGVzY'
        . '3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiP'
        . 'z4ThndjAAAG5ElEQVR42tRaaUxUVxT+GB+YIqjEiCwiVBoLGgSRRRHUxjSKVutSfzTGu'
        . 'CXUWnFNbGpKXYgLKribaGLin1ZjiwUxbq1xqaICKqKyKJtSwAUdBpBtYKbn3HlDGRiYY'
        . 'cRk5pLDzH3v3vPud89yzzlv7LRaLWy9SfzPzs5OfMikIOrT7rs1NU0HYglo7VgSdjoUi'
        . 'sKnT8NcXV1P0LURGo3G6nZc3dyc/evJkwvXrlv3nLrNRC0MRg+Cd9xBpVQ+blGrfZsaG'
        . '9DSQvetTdVor9+/f3/FP2D0EurVEjUwNkm+LUDQqgWAhvp6aFpbrVL/7SVpEn24yl1eZ'
        . 'IseBNtAX1YhlkCrlQIQTauVZBANMjXpQbBN2LP2MBBr9ljy2gYSveE1sxYp2qlTHxvyq'
        . 'o6sObJ3tZPa3VCwx2KkNnB2SPKmKzqCkL0urM8rGfFTnQ67/zHoUNjaKS51MhyZbBeEX'
        . 'pUskAR7tbuZmfjryhVkP8xBeUUFamtr4ezsDE8PDwQFjsaXU6YgPDSU3IniY0rCMsO+f'
        . 'uMfJO7fj6LiYtF3cHCA19ChGEbU2NSE4pIS5BcU4NTp3+E7fDjWr16NSROjPg4InRDMB'
        . '6FWq7Fj126cTk4Wuzt92jTMmzMbwUFBsLe3Nxh378EDnElJxcXLl7GCQMyfOxcbf9xgM'
        . 'M5iK5djJ/a7g6tevSpVvn2LxoZ6swCsWrceN9PT8ZmvL3bEx8Pv8xEm5+UXPMVPcXEoL'
        . 'CpCZEQEDiQl9hhIwNiQ7+kjl4gDwTcKo2atNU3bExIEgNCxY3H00EF86j3MrHmf+njj2'
        . 'OFDCAsJEfO37Uwwa54B9YY63bh5C3/8mQIfb2/s37Mbu5L24sKlS5gRPQ0/LF8u4q+ys'
        . 'n/R0NgIR8dPMNTTE5Ik4cjRYzh3/jxmz5qFfbt3YcGSpUhOScHkiRMxKSqy91wsTLhY9'
        . 'kJ7Dx4UidT2rVvQz8kJz8vK0NTcjDOpZ5GSdg7GchG2Gf315y9eiHnbaP6CRYuJ3wFET'
        . 'oiw2Gt1VicToswgN8peiN3lKH9/cc118GADkF2B17dBgwaJeaP8/ASf4pJS3M3ItFidF'
        . 'B3VSUP/uqO/r14VY+fN/lr0ldXVyMjK7NHO8UYoVSoxn/lwu3Ltqsln68kMSaDbXXiY8'
        . 'wgO5E2CAgNFPzUtDUpldY9AvKmqwlmax/PHEB/ml/PoUS8atonYqbKyEh50AlOGJcY9z'
        . 's2zSI9z8/LFfDZ45ldeUWlxzKYwlnR0R7V1dehPoYS+z+eFJa2Fskc9jwH9+6OO+Jp6d'
        . 'lees0PsJFI7aLupdDiTV6mhmEg/xtPD3SIQ7kOGtPFQ1dTAyalft881WxKsSqaMys3ND'
        . 'RUU3PEZwP3I8REWPThi3Dgxn/kwPw93j94x7DaRydIwRgGjRqKZVCg7J0f0g4MCEREe3'
        . 'iMAk6OixDyez3yYX8DIkd0+tz2ZdLFaE7vApyu3FPIurAbX6fSO3/QLncIzhZF2W26xl'
        . '/DNnDnYEvdzGz/mwy0qMtJiSXSyCa0Jm+AI1YfipKvXb+Db+fPx4GE2bt+5gw1r1yBm8'
        . 'WJk3r+P/YeP4J1S2TbHe9gwLFu0CCHBYzBwwADdo+gZhXRoXqMwnvnxvV6xiY7xkzHi5'
        . 'HZFTIw4gXfuScTShQtxLzub8okDyHn8hPz9YwMA3F5QWDKcAj/2Qno+bAtbt+8QNS7mZ'
        . '2eGZzTLO+kGaUzuSERYGGZGRyPtwgVs3LwZqyjo25mUhOTU1C5rRYVFxfAhiXBrJAAbN'
        . '28RkviKgkbmp/2A2q/UlXcy1dbErsTrqjeUkmahquotEuK3wsXFBXezspCQmGQ0dmK+B'
        . 'c+eYWdiIsVfJSIcXxsba9bzepBji+DJrF2RKOLctmkT9h06jHMXLyImdhUmRU6Ay0AXo'
        . '+Of5OXh5u10YQO86BlTpxKAlYKP9gMr8AaZXVlJSenLinLUkdfpSbt9NwNHjx8XIbmp5'
        . 'u3lhe+WLcP48DCLF/1F9HSDzK6TOllSKBgXForw0BDcJwO/cSsdufl5qHz5CvX19ZQUO'
        . 'cLdbQhG+vljIuUM7N04F+nN2lanko0pF9tdC6aIlMlERbjXi3OSsaTIpiuAbaVY2HIZs'
        . 'y12smVJ9LB4ZqUFZfnE1mpsWxLQ2sLria5BaBobG8rIh3tZM4pqleoddG9N9S/k26JY7'
        . 'rT+dvJUXJNaXSFR3M9hpdbK/pSq6nfHTpw4RWvlYnGzHow+7OCXeP2JhhD5cOrMNS7oX'
        . 'vBZywvJVnnxb4nKiUqJXhHVSO0GNEH3lv61fK3OSkGo5DXWymtuNfhZhLxoZ5kc5WvW8'
        . 'iMVjaxC9TKAWr1aGfxABbqX27zwvvL3PlYGgqWhliXQLH/X/CfAALHSg9r3u8gEAAAAA'
        . 'ElFTkSuQmCC';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): self
    {
        foreach (['client-id', 'client-secret'] as $parm) {
            if (!isset($options[$parm])) {
                throw new InvalidArgumentException(sprintf('Missing Github "%s" parameter in conf/authentication/providers', $parm));
            }
        }

        return new static(
            $generator,
            $session,
            $options,
            new Guzzle('https://github.com/login/oauth')
        );
    }
}
