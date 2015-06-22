<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
namespace Zikula\Bundle\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zikula\Core\Response\AdminResponse;
use Zikula\Core\Response\PlainResponse;
use Zikula_View_Theme;
use Zikula\Core\Event\GenericEvent;

class ThemeListener implements EventSubscriberInterface
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if (\System::isInstalling()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        if ($response instanceof PlainResponse
            || $response instanceof JsonResponse
            || $request->isXmlHttpRequest()
            || $response instanceof RedirectResponse) {
            return;
        }

        // if theme has already been processed the new way, stop here
        if (!isset($response->legacy) && !$request->attributes->get('_legacy', false)) {
            return;
        }

        $themeName = '';
        $smartyCaching = null;

        /**
         * If Response is an AdminResponse, then change theme to the requested Admin theme (if set)
         */
        if ($response instanceof AdminResponse) {
            $adminTheme = \ModUtil::getVar('ZikulaAdminModule', 'admintheme');
            if (!empty($adminTheme)) {
                $themeInfo = \ThemeUtil::getInfo(\ThemeUtil::getIDFromName($adminTheme));
                if ($themeInfo && $themeInfo['state'] == \ThemeUtil::STATE_ACTIVE && is_dir('themes/' . \DataUtil::formatForOS($themeInfo['directory']))) {
                    $event = new GenericEvent(null, array('type' => 'admin-theme'), $themeInfo['name']);
                    $themeName = \EventUtil::dispatch('user.gettheme', $event)->getData();
                    $smartyCaching = false;
                    $_GET['type'] = 'admin'; // required for smarty and FormUtil::getPassedValue() to use the right pagetype from pageconfigurations.ini
                }
            }
        }
        Zikula_View_Theme::getInstance($themeName, $smartyCaching)->themefooter($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array(array('onKernelResponse')),
        );
    }
}
