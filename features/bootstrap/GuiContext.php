<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Alchemy\Phrasea\Application;
use Behat\Behat\Exception\PendingException;
use Behat\MinkExtension\Context\MinkContext;

class GuiContext extends MinkContext
{
    /** @var Application */
    protected $app;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->app = new Application('test');
    }

    /**
     * @Given /^I am not authenticated$/
     */
    public function iAmNotAuthenticated()
    {
        $this->app['session']->clear();
        $this->app['authentication']->reinitUser();
    }

    /**
     * @Given /^locale is "([^"]*)"$/
     */
    public function localeIs($localeI18n)
    {
        $this->getSession()->setCookie('locale', $localeI18n);
    }

    /**
     * @When /^I click "([^"]*)"$/
     */
    public function iClick($css)
    {
        $page = $this->getSession()->getPage();

        $el = $page->find('css', $css);

        $el->click();
    }

    /**
     * @Given /^a user "([^"]*)" does not exist$/
     */
    public function aUserDoesNotExist($login)
    {
        if (false !== $userId = \User_Adapter::get_usr_id_from_login($this->app, $login)) {
            $user = \User_Adapter::getInstance($userId, $this->app);

            $user->ACL()->revoke_access_from_bases(array_keys(
                $this->app['authentication']->getUser()->ACL()->get_granted_base(array('canadmin'))
            ));

            $user->delete();
        }
    }

    /**
     * @Given /^a user "([^"]*)" exists with "([^"]*)" as password$/
     */
    public function aUserExistsWithAsPassword($login, $password)
    {
        if (false === \User_Adapter::get_usr_id_from_login($this->app, $login)) {
            \User_Adapter::create(
                $this->app,
                $login,
                $password,
                $login,
                false
            );
        }
    }

    /**
     * @Given /^a user "([^"]*)" exists$/
     */
    public function aUserExists($login)
    {
        $this->aUserExistsWithAsPassword($login, uniqid());
    }

    /**
     * @Given /^captcha system is enable$/
     */
    public function captchaSystemIsEnable()
    {
        $this->app['phraseanet.registry']->set('GV_captchas', true, \registry::TYPE_BOOLEAN);
    }

    /**
     * @Given /^captcha system is disable/
     */
    public function captchaSystemIsDisable()
    {
        $this->app['phraseanet.registry']->set('GV_captchas', false, \registry::TYPE_BOOLEAN);
    }

    /**
     * @Given /^user registration is enable$/
     */
    public function userRegistrationIsEnable()
    {
        $databox = current($this->app['phraseanet.appbox']->get_databoxes());

        $xml = $databox->get_sxml_structure();

        if (!$xml) {
            throw new \Exception('Invalid databox xml structure');
        }

        if (!isset($xml->caninscript)) {

            $xml->addChild('caninscript', '1');

            $dom = new \DOMDocument();
            $dom->loadXML($xml->asXML());

            $databox->saveStructure($dom);
        }
    }

    /**
     * @Given /^user registration is disable/
     */
    public function userRegistrationIsDisable()
    {
        $databox = current($this->app['phraseanet.appbox']->get_databoxes());

        $xml = $databox->get_sxml_structure();

        if (!$xml) {
            throw new \Exception('Invalid databox xml structure');
        }

        if (isset($xml->caninscript)) {
            unset($xml->caninscript);

            $dom = new \DOMDocument();
            $dom->loadXML($xml->asXML());

            $databox->saveStructure($dom);
        }
    }

    /**
     * @Given /^user guest access is enable$/
     */
    public function userGuestAccessIsEnable()
    {
        if (false === $usrId = \User_Adapter::get_usr_id_from_login($this->app, 'invite')) {
            $user = \User_Adapter::create(
                $this->app,
                'invite',
                '',
                null,
                false,
                true
            );
        } else {
            $user = \User_Adapter::getInstance($usrId, $this->app);
        }

        $user->ACL()->give_access_to_sbas(array_keys($this->app['phraseanet.appbox']->get_databoxes()));

        foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $user->ACL()->give_access_to_base(array($collection->get_base_id()));
            }
        }
    }

    /**
     * @Given /^user guest access is disable/
     */
    public function userGuestAccessIsDisable()
    {
        if (false !== $usrId = \User_Adapter::get_usr_id_from_login($this->app, 'invite')) {
            $user = \User_Adapter::getInstance($usrId, $this->app);

            foreach ($this->app['phraseanet.appbox']->get_databoxes() as $databox) {
                foreach ($databox->get_collections() as $collection) {
                    $user->ACL()->revoke_access_from_bases(array($collection->get_base_id()));
                }
            }
        }
    }

    /**
     * @Given /^a user "([^"]*)" exists with a valid password token "([^"]*)"$/
     */
    public function aUserExistsWithAValidPasswordToken($login, $token)
    {
        throw new PendingException();
    }

    /**
     * @Given /^"([^"]*)" is not authenticated$/
     */
    public function isNotAuthenticated($login)
    {
        $this->iAmNotAuthenticated();
    }

    /**
     * @Given /^"([^"]*)" is authenticated$/
     */
    public function isAuthenticated($login)
    {
        if (false == $usrId = \User_Adapter::get_usr_id_from_login($this->app, $login)) {
            throw new \Exception(sprintf('User %s does not exists, use the following definition to create it : a user "%s" exists', $login, $login));
        }

        $user = \User_Adapter::getInstance($usrId, $this->app);

        $this->app['authentication']->openAccount($user);

        throw new PendingException();
    }
}
