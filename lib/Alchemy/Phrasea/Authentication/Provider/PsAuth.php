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


class PsAuth extends AbstractProvider
{
    private $baseurl;
    private $key;
    private $secret;
    private $providerType;
    private $providerName;
    private $iconUri;

    private $client;

    private $id;

    public function __construct(UrlGenerator $generator, SessionInterface $session, array $options, Guzzle $client)
    {
        parent::__construct($generator, $session);

        $this->baseurl      = $options['base-url'];
        $this->key          = $options['client-id'];
        $this->secret       = $options['client-secret'];
        $this->providerType = $options['provider-type'];
        $this->providerName = $options['provider-name'];
        $this->iconUri      = array_key_exists('icon-uri', $options) ? $options['icon-uri'] : null; // if not set, will fallback on default icon

        $this->client  = $client;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options)
    {
        foreach (['client-id', 'client-secret', 'base-url', 'provider-type', 'provider-name'] as $parm) {
            if (!isset($options[$parm]) || (trim($options[$parm]) == '')) {
                throw new InvalidArgumentException(sprintf('Missing Phraseanet "%s" parameter in conf/authentification/providers', $parm));
            }
        }

        return new self($generator, $session, $options, new Guzzle($options['base-url']));
    }

    public function getType(): string
    {
        return 'ps-auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'PS Auth';
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
    public function getGuzzleClient()
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $params = array())
    {
        $params = array_merge(['providerId' => $this->getId()], $params);

        $state = $this->createState();

        $this->session->set($this->getId() . '.provider.state', $state);

        $url = sprintf("%s/%s/%s/auth?%s",
            $this->baseurl,
            urlencode($this->providerType),
            urlencode($this->providerName),
            http_build_query([
                'client_id' => $this->key,
                'state' => $state,
                'redirect_uri' => $this->generator->generate(
                    'login_authentication_provider_callback',
                    $params,
                    UrlGenerator::ABSOLUTE_URL
                ),
            ], '', '&')
        );


        return new RedirectResponse($url);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($newId): self
    {
        $this->id = $newId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        // Phraseanet does not support tokens revocation
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        if (!$this->session->has($this->getId() . '.provider.state')) {
            throw new NotAuthenticatedException('No state value in session ; CSRF try ?');
        }

        if ($request->query->get('state') !== $this->session->remove($this->getId() . '.provider.state')) {
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
        }
        catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while getting access_token');
        }

        $data = @json_decode($response->getBody(true), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Error while retrieving user info, unable to parse JSON.');
        }

        $this->session->remove($this->getId() . '.provider.state');
        $this->session->set($this->getId() . '.provider.access_token', $data['access_token']);

        try {
            $request = $this->client->get($this->getId() . '/user');
            $request->getQuery()->add('access_token', $data['access_token']);
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        }
        catch (GuzzleException $e) {
            throw new NotAuthenticatedException('Guzzle error while authentication', $e->getCode(), $e);
        }

        $data = @json_decode($response->getBody(true), true);

        if (200 !== $response->getStatusCode()) {
            throw new NotAuthenticatedException('Error while retrieving user info, invalid status code.');
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Error while retrieving user info, unable to parse JSON.');
        }

        $this->session->set($this->getId() . '.provider.id', $data['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        if ('' === trim($this->session->get($this->getId() . '.provider.id'))) {
            throw new NotAuthenticatedException($this->getId() . ' has not authenticated');
        }

        return new Token($this, $this->session->get($this->getId() . '.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity()
    {
        $identity = new Identity();

        try {
            $request = $this->client->get($this->getId() . '/user');
            $request->getQuery()->add('access_token', $this->session->get($this->getId() . '.provider.access_token'));
            $request->setHeader('Accept', 'application/json');

            $response = $request->send();
        }
        catch (GuzzleException $e) {
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
    public function getIconURI()
    {
        return $this->iconUri ?: 'data:image/png;base64,'
            . 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAA'
            . 'AJZlWElmTU0AKgAAAAgABQESAAMAAAABAAEAAAEaAAUAAAABAAAASgEbAAUAAAAB'
            . 'AAAAUgExAAIAAAARAAAAWodpAAQAAAABAAAAbAAAAAAAAABIAAAAAQAAAEgAAAAB'
            . 'QWRvYmUgSW1hZ2VSZWFkeQAAAAOgAQADAAAAAQABAACgAgAEAAAAAQAAADCgAwAE'
            . 'AAAAAQAAADAAAAAAXukGzAAAAAlwSFlzAAALEwAACxMBAJqcGAAAActpVFh0WE1M'
            . 'OmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6'
            . 'bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDUuNC4wIj4KICAgPHJkZjpSREYgeG1s'
            . 'bnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgt'
            . 'bnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAg'
            . 'ICAgICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgog'
            . 'ICAgICAgICAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYv'
            . 'MS4wLyI+CiAgICAgICAgIDx4bXA6Q3JlYXRvclRvb2w+QWRvYmUgSW1hZ2VSZWFk'
            . 'eTwveG1wOkNyZWF0b3JUb29sPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4x'
            . 'PC90aWZmOk9yaWVudGF0aW9uPgogICAgICA8L3JkZjpEZXNjcmlwdGlvbj4KICAg'
            . 'PC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KKS7NPQAADE5JREFUaAXtWQuMVcUZ/uY8'
            . '7vvui+WNAYHa2oqKiw/SWnaJ0ipqH4ZHmjTVGMFao00LVm2Dl9YXtVETpVYSX6m2'
            . 'tYhajU210V0KPqi7CALKrggUWeS9uOzufZzH9PvPvXdZ1t27rJCUpM5y7pyZM2fm'
            . '+/7/m3/ODMAX6QsLHJcF1HG9XeJlraEaFsOUJrW3w1MKukTzk+tR4zzYvRHpeli9'
            . '605E+YR2Wp+CVbC2o+9DtF1bd6EyodsTiV+qup1p8QpBG/SGdyLAn7A+dAqGgC92'
            . '2H4vrubVqpcaWi+v0u1/qWjteC7+o+Jz8QbJGMXy8eTHNQfEok3zYU1ZBkdAtN2L'
            . 'WkNhSVkE52VyQE4rB9XlCCe1HU76SDvu24hgQWx6+g1prxsptRq4xzM/uq0mHQ4m'
            . 'ic45sAB3Dt2P8crF3VEbs0kA7Wm4nLIG/2yxs+Nq3+30/Xg5LoDlrc6tMv/o2t5t'
            . 'agp2ypjiEVXHdz5HGrQH6mup8wZGFY67ewHisSrrNm25N5fFYBG4L3h4BdEHZIjh'
            . 'ZTCiZBPyoGzPM8O+ClV7hpfx00rpO40OLBHwBUmpwc6PY9ZhoHOCr2ugywmy7ebk'
            . '1eForCUZMm5TviHgxYLSXx58MWiKiVS+oAw+U9pwO+CYpo4aFbjDT2KzXosrCdwX'
            . '8CIrGYtvHVOS7ksmDq2a5tVQ5015nV87YpoXcu+tLPfOFcyO4Toq7FnKJi9THFBI'
            . 'gln0NFI84AceMEKFPOzBsHyJSa4RosxCgHcYr5kWFqjJWCc9HOv8KElAp1KGSqUC'
            . 'VG1zx41zTf+eaNybY1IOncpxKQlDhVyDOZiLRGj/AokCATUq2S2hQEYkYZKAsrrJ'
            . 'SkjVRpxRLAP4Hh6m+RdxfuwPiGjNsKu6G0tdz9Svq4rg3wSiu6446zdZL95cZlhz'
            . 'utKm39FleBSBxcuAY0E7ZvdFIRzpX26DS/woF/8FubDrTiI5y++E63vUVxl+rFW8'
            . 'Jdd4xo3SQsBrneoXZ4/RujuELrD+aO3aKZWrX3mx7MVbR+5PUxFjznJgdNjKygK0'
            . 'JGVDy8vkpPwLeXfZIEhKSI1OHOWBQEbiAXneOwWVY1wDO2wk+NBYuGlH1Q2zxw4Z'
            . '+34RU+9X+mS2uK4uqP/9jQtOX521R7Ze9XgmcuF8rbeut732PQwzFYyNR6wuHvhM'
            . 'ueAJCURi9fzF4QsTujcQqJHwfV8ZLsFH6vw269HM3/wZX1u88tUp0nZxQ0OfWPus'
            . 'REND0H+6eUv65YUL8MhjK+y1Y85U6rqlCI/+JvytG+HniEyXUTr8SiuQERI9iUCz'
            . 'jVha/BxcQiRPKBgg+BkCX1cD7iew7CgyQ36Hf2RvMua8q+3vNjbhvcO7u6RZw8qV'
            . 'R17pcdc3gdraoIkqLzOGTr4A5oZm/eoNP8ELqzag9fzvIzT3bsbDELzWZmivnCEk'
            . 'XiCSnw9wC/OCYsh3JCR6EAkqE/TkGE7aA7CM/TAqb8Fa+ync1DwCl6zbhFzmE315'
            . 'PI6kbfeNMd/zQPHWh9vRBb8qqSomfx2fPvES/nnNXXhrZxpdM3+FyPSFUAdb4B3c'
            . 'zvBRDe2GSMQIPFL0Rn7iitXF9Jw3BqWnBHgHTL0TVvlV2Jb4E+7cWYOaxq1o3P8R'
            . 'ZnJJt1RYpX0GnwE++wb8lJBgTXGSSBrhSWMRN018suRhtJ9xNk676jKMnf0oQh+8'
            . 'DmfD01BVxJicSAIHKC9a3OYk5ut56wv4EZRLK2yPXxCJ6Thgz8WLe4bgF9tbEXYP'
            . '4tJwhN8mUXQGSwRDk7w7QBqQgLwvWCSiaMeF52pEzpkEK5PBfxb8Fu3f+RZOmVmH'
            . 'IRPPBdY9A6f1LRjDRwgTzgcSURJxyikXmtIl+MRQZBIL8fqB8bi7ZR9Wd6zDxYkw'
            . '14Y40lrCfWAyGfaYUkl9BStMj24CIiz7/NTUlEL4vDPhrWvBtnkPYPPbu9BxzrUI'
            . 'X7QYhrsb/r4dbDiMchkOn+WItQ/msFvwjv8g5q2pxMxVa5HrbMXFsRhysoYF4HsM'
            . 'doy3x+YBdiZOkNQz97uylE0C9sgKpP/wGpoffxtDbrkEo+qeQGRXI5yWh2DTqKFh'
            . '1+BDcxqe3ODhzub3MDySRV1ZElnKsYvytAyDKpM5kh9jML+lCdAF2hLI0rM+CnxQ'
            . 'psA1AeiuHIwpwxmZNA4sfhIHp07C6DnTMGr6Q2gbpfD8riSuf+dDTsj9mFpVDicU'
            . 'x6eeRogh1mT3RaMMBnixbUkJFRvJAMVBjs5ZEl3JTHU8rg0Edf4ERLv2YuNPF+Hl'
            . 'lgTu2jgS1z/9Ciabh3B2sgxtXOAckj7K2MVOiwMOIi/tgV4d5X1wtCdk7Py0I3iq'
            . 'OZMJ4f3E6Wg44zyM35eFjnTwa2oiDnQoSqULUUtmFu1W4F7sTfo5ilSvsfsrDkgg'
            . 'b5ziMEc8EQAQCXFYWzM6cfStoTFYExqJXaEo/M4OxLkGHabOpW0mV4bOQ2Wo1mmU'
            . 'l3XRaR6r5Tvu88A+QmdAAtI0T6KQsyDzTURgMjRavNtjVaMpPAbvW0lEKakK7eEQ'
            . '32I4p7EJMhSGHbbY3kRbV4IS8lBV0U6CJCL9ayHy+dKABIrgi93LtknsblMuh1U5'
            . '1tLq79pVjPUWyn1uhbnt4naFLfKLmBIP2CFuYLhz4TPLkpU4gkOHy+H5WZSV7UfY'
            . '4qcue8xv5nqPWBy577wkAVGr2F3A5JcYDkO5uASw0RyB9+yh6DAiqCJwiUCOMrne'
            . '5YVR8BcUtw0IhWBaNmyGS4tlk5cQcbwY2g9XgSdHiMV3wzA4Z7QQCfwSjD7QT0kC'
            . '8nJgD5rcDERj4GN+L2w2hmK3iqOS0YTRnNtKERLtJzGNN6TBm7y2xTPiAYtX3gN5'
            . 'AiY9Y/ESMo4zGumuEYhEdpPoTr4rpgt398GbflNJAlxeGPR4oqB91YY4tmEItusk'
            . 'EqxNqiyxUkjEabIsH2vSWj45fFlVC8t24IGAgM3N2hHQliVE8gQsMjfMMNeUr9AA'
            . 'p1COW9nHTpbZTYntpLAqSYCvR3IMjs1uzNmCpEGLqXKdpX19ZBUlUbAyTAHP3sTw'
            . 'dJnPWc7NifRPYDKJ6QHOAd0tISFCAkeREDIMtWY164dzA7bXUZGPTTe7MxJ0NC34'
            . '/cyPDPnZ1NAQjH5I2f+u74x+sM6NRGzfUSE/62R9VzvUvOdxLvgOL94Hl1fIpezz'
            . 'YpikCXt6wBYZ2SQe5LyXeUFiIcmDujClplzbtlTFsHMiMVywNxadtFEA1tbenrdI'
            . 'L7R9EkjRiLyMZ7aub9m4682vluvMz3Ke86lLzbiegHbcAHxAgkSCOgEuJLgiS+5K'
            . 'nO9BIABbBNqTSKHOCnkk4cUrhlkk5bpdB5fErAnj//69metSPB1J9SOlfiVUJCH5'
            . 'ptY37p844sKnckjfYZuheaZvWYTNnbySI4+8EeRzopA8TnlXCNADBqUik1gsbHJN'
            . 'OKJ7kRClxAMiw7K8SDxpyzOd63rWsIxbl3/7lC3SXYoHDP2Bl+d9ekAeSEoRPDPF'
            . 'vZK9ZfeqfZtb/zXfcbwpWS9Tz7MOHk15BiXk0BtaJNVTSq7j5CVEnUcI3gqkc8QD'
            . 'VsjWlmU7oWjMSFQOpXCcd7WXu+jZS8fOEvDzHmm02YEqBV4w9usBeVhIugnBqZxR'
            . 'U1NjNjXVN7F++lnjZszxffOeiG2NE9nwkE1O7oL/2DB9A27GDaKReKBCPCCTOIg6'
            . 'gRdcy7StaEW17XV17PHSnYtWXH7qMhmvtr7eGrpvn142e4qD+VJTOpX0QK9X/aam'
            . 'JmcWZjGspIz12199Jhfb8eWcm77d9XI8KOL88Hk25bvctPHIMSsEuCaTQDyQEK1P'
            . 'qeR1PlR07ut0572VVYkvrbhsLMFrJeAb6urc5bNn59fQXgD6Kg6GQPD+cixn5ym/'
            . 'trbW2rRpU65p68u/9nx9WtZN/5maN/j9Y9IjdIdLleXnQNQO+ZSQE0lWmNFEuamz'
            . 'mecYhU9fcfm4mx/7xtDDgVy4pRHwfYEsVTdoAsXOGhoaZDBFWdlNW1/asebDF37g'
            . 'ec60nJt5h9X8aHYNHz4PIC0nFosZcerc8Jz12vdmPH/FqVc+P3NCS01B58vmUy7/'
            . '25QyxCNFDDOm/vCaCbh096Kf36d/u+aAnrq8ee/cl7ZdV3w+66/alOhSLJ80eYFE'
            . 'MZ7G70gtffCBlduXYslmHlHkk+i8eH/S5jU184Jo1BNgIBdqq2fdSX6fMmbNmmWK'
            . 'XMCV9CQH+wW8/18L/BeSV1YkHS6B9wAAAABJRU5ErkJggg==';
    }

}
