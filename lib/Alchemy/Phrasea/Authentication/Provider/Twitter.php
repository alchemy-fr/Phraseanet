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
use tmhOAuth;

class Twitter extends AbstractProvider
{
    private $twitter;


    public function __construct(UrlGenerator $generator, SessionInterface $session, tmhOAuth $twitter)
    {
        parent::__construct($generator, $session);

        $this->twitter = $twitter;
    }

    /**
     * @param tmhOAuth $twitter
     *
     * @return self
     */
    public function setTwitterClient(tmhOAuth $twitter): self
    {
        $this->twitter = $twitter;

        return $this;
    }

    /**
     * @return tmhOAuth
     */
    public function getTwitterClient(): tmhOAuth
    {
        return $this->twitter;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(array $params = array()): RedirectResponse
    {
        $params = array_merge(['providerId' => $this->getId()], $params);

        $code = $this->twitter->request(
            'POST',
            $this->twitter->url('oauth/request_token', ''),
            ['oauth_callback' => $this->generator->generate(
                'login_authentication_provider_callback',
                $params,
                UrlGenerator::ABSOLUTE_URL
            )]
        );

        if ($code != 200) {
            throw new NotAuthenticatedException('Unable to request twitter token');
        }

        $oauth = $this->twitter->extract_params($this->twitter->response['response']);

        $this->session->set('twitter.provider.oauth', $oauth);

        return new RedirectResponse(sprintf(
                '%s?%s',
                $this->twitter->url("oauth/authenticate", ''),
                http_build_query(['oauth_token' => $oauth['oauth_token']], '', '&')
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        // Twitter does no timplement token revocation
    }

    /**
     * {@inheritdoc}
     */
    public function onCallback(Request $request)
    {
        $oauth = $this->session->get('twitter.provider.oauth');

        $this->twitter->config['user_token'] = $oauth['oauth_token'];
        $this->twitter->config['user_secret'] = $oauth['oauth_token_secret'];

        $code = $this->twitter->request(
            'POST',
            $this->twitter->url('oauth/access_token', ''),
            ['oauth_verifier' => $request->query->get('oauth_verifier')]
        );

        if ($code != 200) {
            throw new NotAuthenticatedException('Unable to get twitter access token');
        }

        $access_token = $this->twitter->extract_params($this->twitter->response['response']);
        $this->session->set('twitter.provider.access_token', $access_token);
        $this->session->remove('twitter.provider.oauth');

        $this->twitter->config['user_token'] = $access_token['oauth_token'];
        $this->twitter->config['user_secret'] = $access_token['oauth_token_secret'];

        $code = $this->twitter->request(
            'GET', $this->twitter->url('1/account/verify_credentials')
        );

        if ($code != 200) {
            throw new NotAuthenticatedException('Unable to get twitter credentials');
        }

        $resp = @json_decode($this->twitter->response['response'], true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Twitter JSON response.');
        }

        $this->session->set('twitter.provider.id', $resp['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): Token
    {
        if (0 >= $this->session->get('twitter.provider.id')) {
            throw new NotAuthenticatedException('Provider has not authenticated');
        }

        return new Token($this, $this->session->get('twitter.provider.id'));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(): Identity
    {
        $access_token = $this->session->get('twitter.provider.access_token');

        $this->twitter->config['user_token'] = $access_token['oauth_token'];
        $this->twitter->config['user_secret'] = $access_token['oauth_token_secret'];

        $code = $this->twitter->request(
            'GET', $this->twitter->url('1/account/verify_credentials')
        );

        if ($code != 200) {
            throw new NotAuthenticatedException('Unable to retrieve twitter identity');
        }

        $resp = @json_decode($this->twitter->response['response'], true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new NotAuthenticatedException('Unable to parse Twitter Identity JSON response.');
        }

        $identity = new Identity();

        $identity->set(Identity::PROPERTY_USERNAME, $resp['screen_name']);
        $identity->set(Identity::PROPERTY_IMAGEURL, $resp['profile_image_url_https']);
        $identity->set(Identity::PROPERTY_ID, $resp['id']);

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
        . 'D0ieG1wLmRpZDpFRjg3OUMwRTcxRUMxMUUyQjc5NzlGRUJDNjcwRkVDMyIgeG1wTU06S'
        . 'W5zdGFuY2VJRD0ieG1wLmlpZDpFRjg3OUMwRDcxRUMxMUUyQjc5NzlGRUJDNjcwRkVDM'
        . 'yIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpI'
        . 'j4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MDI4M'
        . 'DExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiIHN0UmVmOmRvY3VtZW50SUQ9Inhtc'
        . 'C5kaWQ6MDI4MDExNzQwNzIwNjgxMThDMTRGNzRCQjNFMzY1OEMiLz4gPC9yZGY6RGVzY'
        . '3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiP'
        . 'z67Nf6GAAAEqklEQVR42txaS2zcRBj+vXa8u26UV9NQQtsESFW1OVCVnuASDkXqpRISC'
        . 'HGgl9IeUNRDxRFx44B64UaLVFU8hYRUcUC9IFW8GsRDREWKFEAJJTyySTbpZr3rzfoxw'
        . 'z/j8darpM44D8nDSP/uOrY8/zf/983/z0w0Simo3nLwP2gG+9A0jX8JY8D02O8sNYLGq'
        . 'BPEflND3OQOvzY5c7JZ2PtBw4XDWRxx4nszpZ+/u3Dz/Jmf8NJF89if4yA6Cv0DH95b9'
        . 'keqdT+rzHm8MHLiGn6PodloDgNjxLRhUl0foeBDlqWeM/PD+PWQuGSj7UcgmAbyBL0Pk'
        . 'GmUZF7LAyIKzJptdGIg2IxLsj/rdqNZzGfme5xOeih5iiAyj6LI6C8YlDPidCM8ClSFS'
        . 'Bhi4HOtPHF/EqYQcEqplcXbQARCD4FilUgbCD8CQkD1SDBNEIVBCCBK04kIUVOisLA9B'
        . 'OCh/74kBl2Tj9qTvSY8N2TBwT0GVD0C35TW4MZfDu/roGVAB75rdos12zo6MVHLBuL0Y'
        . 'BH68zq8N1tLfO6FQxZcHO3mZUHUnujLwzMPF8HHzgYRxNlvl7acn9pnJ8ryBJrk2473m'
        . 'XD6gAWFHIUrv9c2dOJAUYfxo11tAKI2itFhkfx8rgZn8D3vbzIYknQSQCRBGMKzlw93w'
        . 'TEc2cu/rMJszWt7Zmx/AfSclkjJYz15eGe6LN1v4vKUxOgkY3dW3Puc35uHj8YG4M0Tv'
        . 'fDUPhOd0/gzvfnkxWETQ3Hu9hKUm0S6Xwk6ERwRuTxx488avPhYJxdrNKqnHrG4udjbH'
        . '1UPrI5kEBUUue0GO5vsfLGmkGmXRnvhru21QMSbiRQ60mNu+o7ltUC6P0lhCzpJvvTfu'
        . 'gfPP9qzLQemK+62y5y2WHutjE2l7OOZGiytbY8KtzFfyPYXWbKwWd2EKKikVRoBnL21A'
        . 'FMxgadpLOnd+tuR7i+y5LKDz0w4S6SIb2XNh3nHh6EuAzqNdNtU13+tQn2bot5YE0Fos'
        . 'm2pHqA4SWoA/2CJcX1qNVVfUiAoCaNAU04Xb0wswkrTh1eOdkNHQmJraQ+n3/GvF8Bu7'
        . 'Mz+VtvwUfHBK9kU5iHoyz+U4dnP5mC26iV2yKI9/tUi3Ck1UvcT2aalOHmAeNblAcxsO'
        . 'czKnVh+jmC2PjXUCS9h+VE0HhyJElLo1S9LMDnf2MVFEQnrJiJBJ8PQ4dzxPrgw2sOBJ'
        . 'LU6Dv+7UxW4NrkMdjOAnW7rMjZbEMlEwsYZ6e2JBbjyYxmeHt4DJ/dbWASasM/S+f0yT'
        . 'r/TOPV+P+/AxFwdGrvg/MbCxumVizvFlo3j+vDFb6vcMrM8ZSmCBgovTymvm6jaIAKxS'
        . 'UBV3rLhZ0eqR4JTiWtC4X0nEQq16cQ3z9Q4KUrKE1RQSmVNRAlPYToR6jTmUBeHIMMYS'
        . 'H1lGcJTCCKsVYqzi6D66dXXSb06v9USebctsMv3nJtvfYK+sjLYjXZeNXZT07Q8XnRBe'
        . 'D48jDaI1g/hCaWekSAwh9mRb5lttKDdRVtgS3Uj9kATwlP6xaiChvCU0sgICF9EYFX4a'
        . 'AufAyNGJ1fcAIGYRcGE7PyTSuSjI/y0xTX5T4ABAP5AumSxg/sSAAAAAElFTkSuQmCC';
    }

    /**
     * {@inheritdoc}
     */
    public static function create(UrlGenerator $generator, SessionInterface $session, array $options): self
    {
        foreach (['consumer-key', 'consumer-secret'] as $parm) {
            if (!isset($options[$parm])) {
                throw new InvalidArgumentException(sprintf('Missing Twitter "%s" parameter in conf/authentification/providers', $parm));
            }
        }

        return new static(
            $generator,
            $session,
            new tmhOAuth([
                'consumer_key'    => $options['consumer-key'],
                'consumer_secret' => $options['consumer-secret'],
                'timezone'        => date_default_timezone_get()
            ])
        );
    }
}
