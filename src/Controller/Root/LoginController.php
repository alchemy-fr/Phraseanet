<?php

namespace App\Controller\Root;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;


class LoginController extends Controller
{

    /**
     * @param Request $request
     * @return array
     */
    public function getDefaultTemplateVariables(Request $request)
    {
        $items = [];

        foreach ($this->getFeedItemRepository()->loadLatest($this->app, 20) as $item) {
            $record = $item->getRecord($this->app);
            $preview = $record->get_subdef('preview');
            $permalink = $preview->get_permalink();

            $items[] = [
                'record' => $record,
                'preview' => $preview,
                'permalink' => $permalink
            ];
        }

        $conf = $this->getConf();
        $browser = $this->getBrowser();

        return [
            'last_publication_items' => $items,
            'instance_title' => $conf->get(['registry', 'general', 'title']),
            'has_terms_of_use' => $this->app->hasTermsOfUse(),
            'meta_description' =>  $conf->get(['registry', 'general', 'description']),
            'meta_keywords' => $conf->get(['registry', 'general', 'keywords']),
            'browser_name' => $browser->getBrowser(),
            'browser_version' => $browser->getVersion(),
            'available_language' => $this->app['locales.available'],
            'locale' => $this->app['locale'],
            'current_url' => $request->getUri(),
            'flash_types' => $this->app->getAvailableFlashTypes(),
            'recaptcha_display' => $this->app->isCaptchaRequired(),
            'unlock_usr_id' => $this->app->getUnlockAccountData(),
            'guest_allowed' => $this->app->isGuestAllowed(),
            'register_enable' => $this->getRegistrationManager()->isRegistrationEnabled(),
            'display_layout' => $conf->get(['registry', 'general', 'home-presentation-mode']),
            'authentication_providers' => $this->app['authentication.providers'],
            'registration_fields' => $this->getRegistrationFields(),
            'registration_optional_fields' => $this->getOptionalRegistrationFields(),
        ];
    }


    /**
     * Login into Phraseanet
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function login(Request $request, TranslatorInterface $translator)
    {
        try {
            $this->get('appbox')->get_connection();
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans('login::erreur: No available connection - Please contact sys-admin'));
        }

        $feeds = $this->getFeedRepository()->findBy(['public' => true], ['updatedOn' => 'DESC']);

        $form = $this->app->form(new PhraseaAuthenticationForm($this->app));
        $form->setData([
            'redirect' => $request->query->get('redirect')
        ]);

        return $this->render('login/index.html.twig', array_merge(
            $this->getDefaultTemplateVariables($request),
            [
                'feeds' => $feeds,
                'form'  => $form->createView(),
            ]));
    }



}