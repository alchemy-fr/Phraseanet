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
use Alchemy\Phrasea\Exception\RuntimeException;
use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client as Guzzle;
use Guzzle\Http\ClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Viadeo extends AbstractProvider
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
     * @return self
     */
    public function setGuzzleClient(ClientInterface $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getGuzzleClient(): ClientInterface
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

        $this->session->set('viadeo.provider.state', $state);

        return new RedirectResponse('https://secure.viadeo.com/oauth-provider/authorize2?' . http_build_query([
            'client_id' => $this->key,
            'state' => $state,
            'response_type' => 'code',
            'redirect_uri' => $this->generator->generate(
                'login_authentication_provider_callback',
                $params,
                UrlGenerator::ABSOLUTE_URL
            ),
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        try {
            $request = $this->client->get('https://secure.viadeo.com/oauth-provider/revoke_access_token2');
            $request
                ->getQuery()
                ->add('access_token', $this->session->get('viadeo.provider.access_token'))
                ->add('client_id', $this->key)
                ->add('client_secret', $this->secret);

            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new RuntimeException('Unable to revoke token from Viadeo', $e->getCode(), $e);
        }

        if (302 !== $response->getStatusCode()) {
            throw new RuntimeException('Error while revoking access token');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        if (!$this->session->has('viadeo.provider.state')) {
            throw new NotAuthenticatedException('No state value ; CSRF try ?');
        }

        if ($request->query->get('state') !== $this->session->remove('viadeo.provider.state')) {
            throw new NotAuthenticatedException('Invalid state value ; CSRF try ?');
        }

        try {
            $guzzleRequest = $this->client->post('https://secure.viadeo.com/oauth-provider/access_token2');

            $guzzleRequest->addPostFields([
                'grant_type'    => 'authorization_code',
                'code'          => $request->query->get('code'),
                'redirect_uri'  => $this->generator->generate('login_authentication_provider_callback', ['providerId' => $this->getId()], UrlGenerator::ABSOLUTE_URL),
                'client_id'     => $this->key,
                'client_secret' => $this->secret,
            ]);
            $guzzleRequest->setHeader('Accept', 'application/json');
            $response = $guzzleRequest->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to retrieve viadeo access token', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while getting access_token');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Viadeo access_token response.');
        }

        $this->session->remove('viadeo.provider.state');
        $this->session->set('viadeo.provider.access_token', $data['access_token']);

        try {
            $request = $this->client->get('https://api.viadeo.com/me?secure=true');
            $request->getQuery()->add('access_token', $data['access_token']);
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to retrieve viadeo user informations', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Viadeo user informations response.');
        }

        $this->session->set('viadeo.provider.id', $data['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        if ('' === trim($this->session->get('viadeo.provider.id'))) {
            throw new NotAuthenticatedException('Viadeo has not authenticated');
        }

        return new Token($this, $this->session->get('viadeo.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $identity = new Identity();

        try {
            $request = $this->client->get('https://api.viadeo.com/me?secure=true');
            $request->getQuery()
                ->add('access_token', $this->session->get('viadeo.provider.access_token'));
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to retrieve Viadeo identity');
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Viadeo identity.');
        }

        $identity->set(Identity::PROPERTY_FIRSTNAME, $data['first_name']);
        $identity->set(Identity::PROPERTY_ID, $data['id']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $data['picture_large']);
        $identity->set(Identity::PROPERTY_LASTNAME, $data['last_name']);
        $identity->set(Identity::PROPERTY_USERNAME, $data['nickname']);

        try {
            $request = $this->client->get('https://api.viadeo.com/me/career?secure=true');
            $request->getQuery()->add('access_token', $this->session->get('viadeo.provider.access_token'));
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to retrieve Viadeo career information.');
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving company info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Viadeo career informations.');
        }

        if (0 < count($data['data'])) {
            $job = array_shift($data['data']);
            $identity->set(Identity::PROPERTY_COMPANY, $job['company_name']);
        }

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
        . 'D0ieG1wLmRpZDo2NzRFNkQwMTg0MjQxMUUyQURGN0Q4NzM4NzM5NzBDOSIgeG1wTU06S'
        . 'W5zdGFuY2VJRD0ieG1wLmlpZDo2NzRFNkQwMDg0MjQxMUUyQURGN0Q4NzM4NzM5NzBDO'
        . 'SIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpI'
        . 'j4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDU4M'
        . 'DExNzQwNzIwNjgxMTgwODNFMEVCQjVCN0VBNTciIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
        . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6RGVzY'
        . '3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiP'
        . 'z74vieoAAAGGklEQVR42uxabUxTZxR+ent7bylFC0jLkAkLhsQJuqAMtpmZbIkx6g8yY'
        . 'xZ/8MOvLMv+LEu2HyT8IbpkyTQYs+j+qIlZsj9mxiXMEXVf8TsOBmMkKAhDAaFUy0db+'
        . 'nX3nrf3dqVgW9qq12UnOentve/tPc97nvOec95bg6IoeNFF1A4MBgP/UFVgaow51pOE4'
        . '1QRYy5yg0+dOvW61Wo97ff7KwOBAPTmqVAo1Hn16tXGkydPDrGvfqbBeBAms9l8xul0V'
        . 'kxMTMDj8egOBGPMayUlJa3scA/TaabeWBBEG2lubq5ifHwcDx8+BPOGHkFAluXN7NCuO'
        . 'ScWBMWATIbPzs7C5/PpMohpUtlEiyoIb7wnOJ0Y54h30POqRfYxsTGdIJvj6WQk4zXVu'
        . 'ViIObTCinEXBEIZDoe5vgDpgUJAEBdzlQYkG1Jm9cEoKBiYykluFRsXDBtSjvFYCs3PJ'
        . 'Mz4WEplqoPTMja/5EK+5E84bpkpgC82DuBliy+l30VcHCzwhEanbGnbUD7erxh74vWVO'
        . 'T58Vv0Ay6Uw3rBPpfSbi5YdT4tOJPenTZgLGVCa68Xf0zI/ZzaGUe+YxcYiD6oKvFFur'
        . 'Cvw4Js7+enVTot5Ipvy/T0b3il144x7BWqKvPjgVRcsYjie3nDkBJf87GcGYnhK5Fwuk'
        . 'AP4qHoSkjCf15M+I6cTBXfGIILBYNbppEnXhIy3imcWAHg8JyAUjqxObr/wfD2Rk5ODu'
        . 'ro6VFVVwW63Q5IkXkSOjo7i2rVr+L3nT6zJn54fLzMiN77YwrMw7jwyZQfEUj1hNBrR0'
        . 'NCAXbt2wWKxLDqGCsquri7MBiJeGHCLHMC6FX7Y5H+f9dOw/Ow9kZubi6amJqxfvz56b'
        . 'nBwEH19fdwLeXl53DM3b97kv5lvDuCTn21oWO3F26Xzi8zroxKuj5jUXucZgRBZ1dLc3'
        . 'MyNJOnt7cXx48dx9+7dJ96z2ubHpFeB02NAgD3CxDJVmDnnl2EJX/9hSYvGC0BQN6cFd'
        . 'zJpbGyMArh06RKOHDnC700kJiHM8oUfp7slnOmR2DKr8BziD2HJHngiiFSr2IKCAuzcu'
        . 'ZMfd3d34/Dhw0kBkCyXFeRJVOqLCDLDZxQh45UwbU9s3bqVrz4E9ujRo9SoJH2YbGQ1E'
        . 'gtikyhgy5Yt2L59O86fP4/29vbsgkg1Jmpra6Ne6O/vT+lh9rzIxEx5gvh0zx4UFhZib'
        . 'GwMFy5cyAhE2gXgqlWr+PjOzs6UC8Gy5REQQ4/Bl1uS8vLytIrKrOQJWjpJnE5nypxeW'
        . 'xTEI68BD9yszJicjCbIrMdEbD+RSCgGZFnmmmorW1caxG9DRrakKjxBkmRjW0hItDolU'
        . 'uIySUVFRUrj1xSF4LAq+K43UgjSfVomT6fZSloAphLYt27dQllZGTZt2gSTyZR0dXpvT'
        . 'QA/9Blxz6XA4bBH88uNGzcyppOQKCYSqbYsWq1W7N69O+FY8sJau4Kvrkdywv79+yEIA'
        . 'n/O5cuXMw7stOlEq9KVK1f4Pfv27UNNTc3ivTNLbh+/GUJTu4Apn4Jt27Zhx44d/L6zZ'
        . '89yWmZKp4x67EOHDsHlcvEaqrW1lVey2uJAas8No+XdEL781YD+RyL27t3Lay1exQ4M4'
        . 'NixY2n37fO2PTRUBoOBaugiNkuDZJjX602Jj5WVlThx4gRsNlu0gm1ra4Pg6kVtsRc/D'
        . 'lrheKWaz77D4Yj0EPfv48CBAzyo05WOjo4P2cdflHYWgGClwJJAkJSUlKClpQUbNmxIO'
        . 'vbixYs4ePAg3G53RsHM6BwFkZXOjmaWqFJfX89nvLq6GsXFxTCbzXxzmnh/+/ZtnDt3D'
        . 'j09PU/vTVFsstMS3lKFWlDS57GfmVYprrdN2bTKDl2DeIF2xf/jniDj0w3s/z2RxQKQl'
        . 'frKsPpiXrfC4tZFH+r2SDjWE3QiNDIy0swq088pEesxuAkA6ya/pX4KkZfxodiyg14cL'
        . 'KPddWp9ma5kWojICz6jXjCoxlNv+4BKNeqrxLgB1NnQju+4em5GpyDcqo1k61w8nfzqB'
        . 'aiDCYAE/fxJRbPRo9pJ6o+lk/aPGpNquKweG3UGgrwRUFlDgAL/CDAAr77qQMIdLsoAA'
        . 'AAASUVORK5CYII=';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): self
    {
        foreach (['client-id', 'client-secret'] as $parm) {
            if (!isset($options[$parm])) {
                throw new InvalidArgumentException(sprintf('Missing Viadeo "%s" parameter in conf/authentication/providers', $parm));
            }
        }

        return new static($generator, $session, $options, new Guzzle());
    }
}
