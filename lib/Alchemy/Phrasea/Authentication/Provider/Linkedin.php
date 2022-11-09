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

class Linkedin extends AbstractProvider
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
     * @return Linkedin
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

        $this->session->set('linkedin.provider.state', $state);

        return new RedirectResponse('https://www.linkedin.com/uas/oauth2/authorization?' . http_build_query([
             'response_type' => 'code',
             'client_id' => $this->key,
             'scope' => 'r_basicprofile r_emailaddress',
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
        // LinkedIn does not provide Oauth2 token revocation
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        if (!$this->session->has('linkedin.provider.state')) {
            throw new NotAuthenticatedException('No state value ; CSRF try ?');
        }

        if ($request->query->get('state') !== $this->session->remove('linkedin.provider.state')) {
            throw new NotAuthenticatedException('Invalid state value ; CSRF try ?');
        }

        try {
            $guzzleRequest = $this->client->post('https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $request->query->get('code'),
                'redirect_uri'  => $this->generator->generate(
                    'login_authentication_provider_callback',
                    ['providerId' => $this->getId()],
                    UrlGenerator::ABSOLUTE_URL
                ),
                'client_id'     => $this->key,
                'client_secret' => $this->secret,
            ], '', '&'));
            $response = $guzzleRequest->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to query LinkedIn access token', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while getting access_token');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse LinkedIn JSON');
        }

        $this->session->remove('linkedin.provider.state');
        $this->session->set('linkedin.provider.access_token', $data['access_token']);

        try {
            $request = $this->client->get('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,positions,industry,picture-url,email-address)');
            $request->getQuery()
                    ->add('oauth2_access_token', $data['access_token'])
                    ->add('format', 'json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Error while retrieving linkedin user informations.');
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse LinkedIn JSON');
        }

        $this->session->set('linkedin.provider.id', $data['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        if ('' === trim($this->session->get('linkedin.provider.id'))) {
            throw new NotAuthenticatedException('Linkedin has not authenticated');
        }

        return new Token($this, $this->session->get('linkedin.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $identity = new Identity();

        try {
            $request = $this->client->get('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,positions,industry,picture-url;secure=true,email-address)');
            $request->getQuery()
                    ->add('oauth2_access_token', $this->session->get('linkedin.provider.access_token'))
                    ->add('format', 'json');

            $response = $request->send();
        } catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Unable to fetch LinkedIn identity', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Linkedin JSON identity');
        }

        if (0 < $data['positions']['_total']) {
            $position = array_pop($data['positions']['values']);
            $identity->set(Identity::PROPERTY_COMPANY, $position['company']['name']);
        }

        $identity->set(Identity::PROPERTY_EMAIL, $data['emailAddress']);
        $identity->set(Identity::PROPERTY_FIRSTNAME, $data['firstName']);
        $identity->set(Identity::PROPERTY_ID, $data['id']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $data['pictureUrl']);
        $identity->set(Identity::PROPERTY_LASTNAME, $data['lastName']);

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
        . 'D0ieG1wLmRpZDo2N0M5MkQ3RDcxRUUxMUUyQjc5NzlGRUJDNjcwRkVDMyIgeG1wTU06S'
        . 'W5zdGFuY2VJRD0ieG1wLmlpZDo2N0M5MkQ3QzcxRUUxMUUyQjc5NzlGRUJDNjcwRkVDM'
        . 'yIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpI'
        . 'j4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDI4M'
        . 'DExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
        . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6RGVzY'
        . '3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiP'
        . 'z4zg+u0AAAD+0lEQVR42uxaSWgTURj+kkwWS7VirdZaS6Xa2tqCW3FBq4IHl0MPFT14c'
        . 'wE9K+hBD3oTRD1UPAgeBOsCgoinFulBQRGt2orSWqS2VatNapqmS5Z543uTyTKTTDIJI'
        . '32RPvjJvLxl3jffv7z/zVgkSUK+Fyv+gyJELyy0JACzKb8WRXgqTHVIoghJoPqHHiEk7'
        . 'kUgZAdTNd60TSIe/Bm7jj3bWmktQCVsidoEJYI9fSc+DUxieBTw+GizyJ/uML1w2MM4t'
        . 'LuGXtFFYjKRCQbChUAQcHuB32OUKE6NXrCxdZcqtbCgMXKHvHDGgMix1wrJGlLCWNAyw'
        . 'YiK2wHh3vUWUZnHeNGCiLhcGQj3IFzyQ9eAQBwAiQj/Mc6iihMqL8yAkPyJ5ELKWBIFk'
        . 'rcgokad/0wYswmHzYpj66pRt7gIXSNjuNPdTz2zxAMTkmGbaGtpQkttZaxeX7IQp9tfc'
        . '7CLJVF1ImnFSoE211Sohh5YVZ5xnGmSFoQoxW0ijRCR4O0Pt2po10+3obGmSEZ1IsZso'
        . 'uVeB87vWo/VxUV4/9ODS51dsxJfkkGEjXun714/Tj1+zqF3iqlT5id6ZN1qlBcVxurvq'
        . 'Hq1fxmSr8/uXK/qy/7vc4/j6KY12FtdgUXznBj2+fHk0wDufehHmBCTQYjEEIiTm9die'
        . '+WyWP3mq49o7/0mX1/c0winYItvdKg7bmmoQkNpccIMS3GwvgrHG2ux//ZTTAZDJnknE'
        . 'caNS9IPlDOahOrc7o0aAPHStLIMl/dtzdmwU3sno0C0KCRJ155clJWBMR9aX3SjrasvS'
        . 'X1ObK7DfLvdJO8kSil9sW7KnsqzpWj8MupF49X7GJ8JRNzx0AZcad6REP1t2FKxBB29g'
        . '2aok5RFwJFSg5BdtLrp1ssejE9Nx9rvvvmcdOuy+QU5BTsdw5ZyZAK64/yMgYS2Ee9EU'
        . 'p8Cu83YfTN6pxADIUYkWxQs0OmNI1LmOY30MQSCZJGepuqiN87InDmmxSkiNolITkyke'
        . 'ZLEwJyEmMSECFO8k81qSeO50jBhik2EszBsPe9ES6HTMYsgRGJSnMBsghANgzh84wFc9'
        . 'vgUvum4G606c03V1z0xlTSnkT65g4gCyVB+eMZ1276OeDKON9LnnzPB72lHFkzwDSLvm'
        . 'ZA3b1KenwDOqdOcOpmtTvnOROwlC9dMqBJ8ISmbCM4MAZYVXIOY8g0q+215z23VpEMiP'
        . 'jy7wHYUEBzqRIUXmfGPoqfzIYNCJQjNy3gn/Vkgn2gBlVSWU2EHRQWIvOPmobBDVvbal'
        . '226vlNhJ3W/BE06xM5TWAb/O5rfcwiCMeClMqqsNSho1CmoNEDpzAA4wM/XOKKyxilln'
        . 'eyziMBfAQYA4zp6M1LIbMYAAAAASUVORK5CYII=';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): self
    {
        foreach (['client-id', 'client-secret'] as $parm) {
            if (!isset($options[$parm])) {
                throw new InvalidArgumentException(sprintf('Missing Linkedin "%s" parameter in conf/authentication/providers', $parm));
            }
        }

        return new static($generator, $session, $options, new Guzzle());
    }
}
