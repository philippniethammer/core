<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\AdminModule\Controller;

use DataUtil;
use ModUtil;
use StringUtil;
use System;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Zikula\Core\Controller\AbstractController;
use Zikula\ExtensionsModule\Api\VariableApi;

/**
 * @Route("/admininterface")
 */
class AdminInterfaceController extends AbstractController
{
    /**
     * @Route("/header")
     *
     * Open the admin container
     *
     * @return Response symfony response object
     */
    public function headerAction()
    {
        return $this->render('@ZikulaAdminModule/AdminInterface/header.html.twig', [
            'caller' => $this->get('request_stack')->getMasterRequest()->attributes->all()
        ]);
    }

    /**
     * @Route("/footer")
     *
     * Close the admin container
     *
     * @return Response symfony response object
     */
    public function footerAction()
    {
        $caller = $this->get('request_stack')->getMasterRequest()->attributes->all();
        $caller['info'] = $this->get('zikula_extensions_module.extension_repository')->get($caller['_zkModule']);

        return $this->render('@ZikulaAdminModule/AdminInterface/footer.html.twig', [
            'caller' => $caller,
            'symfonyVersion' => Kernel::VERSION,
            'phpVersion' => phpversion()
        ]);
    }

    /**
     * @Route("/breadcrumbs")
     * @Method("GET")
     *
     * Admin breadcrumbs
     *
     * @return Response symfony response object
     */
    public function breadcrumbsAction()
    {
        if (!$this->hasPermission('ZikulaAdminModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $caller = $masterRequest->attributes->all();
        $caller['info'] = $this->get('zikula_extensions_module.extension_repository')->get($caller['_zkModule']);

        $cid = null;
        if ($caller['_zkModule'] == 'ZikulaAdminModule') {
            $requested_cid = $masterRequest->attributes->get('acid');
            $cid = empty($requested_cid) ? $this->getVar('startcategory') : $requested_cid;
        } else {
            $cid = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getmodcategory', [
                'mid' => $caller['info']['id']
            ]);
        }
        $caller['category'] = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getCategory', [
            'cid' => $cid
        ]);

        return $this->render('@ZikulaAdminModule/AdminInterface/breadCrumbs.html.twig', [
            'caller' => $caller
        ]);
    }

    /**
     * @Route("/developernotices")
     *
     * Add developer notices
     *
     * @return Response symfony response object
     */
    public function developernoticesAction()
    {
        if (!$this->hasPermission('ZikulaAdminModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $modvars = $this->get('zikula_extensions_module.api.variable')->getAll('ZikulaThemeModule');
        $data = [];
        $data['mode'] = $this->get('kernel')->getEnvironment();
        if ($data['mode'] != 'prod') {
            $data['debug'] = $this->get('kernel')->isDebug() ? $this->__('Yes') : $this->__('No');
            $data['legacy'] = [
                'status' => true,
                'cssjscombine' => $modvars['cssjscombine'],
                'render' => [
                    'compile_check' => [
                        'state' => $modvars['render_compile_check'],
                        'title' => $this->__('Compile check')
                    ],
                    'force_compile' => [
                        'state' => $modvars['render_force_compile'],
                        'title' => $this->__('Force compile')
                    ],
                    'cache' => [
                        'state' => $modvars['render_cache'],
                        'title' => $this->__('Caching')
                    ]
                ],
                'theme' => [
                    'compile_check' => [
                        'state' => $modvars['compile_check'],
                        'title' => $this->__('Compile check')
                    ],
                    'force_compile' => [
                        'state' => $modvars['force_compile'],
                        'title' => $this->__('Force compile')
                    ],
                    'cache' => [
                        'state' => $modvars['enablecache'],
                        'title' => $this->__('Caching')
                    ]
                ]
            ];
        }

        return $this->render('@ZikulaAdminModule/AdminInterface/developerNotices.html.twig', [
            'developer' => $data
        ]);
    }

    /**
     * @Route("/securityanalyzer")
     *
     * Add security analyzer
     *
     * @return Response symfony response object
     */
    public function securityanalyzerAction()
    {
        if (!$this->hasPermission('ZikulaAdminModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // check for .htaccess in app directory
        $app_htaccess = false;
        $appDir = $this->get('kernel')->getRootDir();
        if ($appDir) {
            // check if we have an absolute path which is possibly not within the document root
            $docRoot = System::serverGetVar('DOCUMENT_ROOT');
            if (StringUtil::left($appDir, 1) == '/' && false === strpos($appDir, $docRoot)) {
                // temp dir is outside the webroot, no .htaccess file needed
                $app_htaccess = true;
            } else {
                if (false === strpos($appDir, $docRoot)) {
                    $ldir = dirname(__FILE__);
                    $p = strpos($ldir, DIRECTORY_SEPARATOR . 'system'); // we are in system/AdminModule
                    $b = substr($ldir, 0, $p);
                    $filePath = $b.'/'.$appDir.'/.htaccess';
                } else {
                    $filePath = $appDir.'/.htaccess';
                }
                $app_htaccess = file_exists($filePath);
            }
        } else {
            // already customised, admin should know about what he's doing...
            $app_htaccess = true;
        }

        $variableApi = $this->get('zikula_extensions_module.api.variable');
        $hasSecurityCenter = ModUtil::available('ZikulaSecurityCenterModule');

        return $this->render('@ZikulaAdminModule/AdminInterface/securityAnalyzer.html.twig', [
            'security' => [
                'config_php' => is_writable('config/config.php'),
                'magic_quotes_gpc' => DataUtil::getBooleanIniValue('magic_quotes_gpc'),
                'register_globals' => DataUtil::getBooleanIniValue('register_globals'),
                'app_htaccess' => $app_htaccess,
                'updatecheck' => $variableApi->get(VariableApi::CONFIG, 'updatecheck'),
                'scactive' => $hasSecurityCenter,
                // check for outputfilter
                'useids' => $hasSecurityCenter && $variableApi->get(VariableApi::CONFIG, 'useids') == 1,
                'idssoftblock' => $variableApi->get(VariableApi::CONFIG, 'idssoftblock')
            ]
        ]);
    }

    /**
     * @Route("/updatecheck")
     *
     * Add update check
     *
     * @return Response symfony response object
     */
    public function updatecheckAction()
    {
        if (!$this->hasPermission('ZikulaAdminModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $masterRequest = $this->get('request_stack')->getMasterRequest();

        return $this->render('@ZikulaAdminModule/AdminInterface/updateCheck.html.twig', [
            'caller' => [
                '_route' => $masterRequest->attributes->get('_route'),
                '_route_params' => $masterRequest->attributes->get('_route_params')
            ],
            'updateCheckHelper' => $this->get('zikulaadminmodule.update_check_helper')
        ]);
    }

    /**
     * @Route("/menu")
     *
     * Admin menu.
     *
     * @return Response symfony response object
     */
    public function menuAction()
    {
        if (!$this->hasPermission('ZikulaAdminModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $currentRequest = $this->get('request_stack')->getCurrentRequest();
        // get caller info
        $caller = [];
        $caller['_zkModule'] = $masterRequest->attributes->get('_zkModule');
        $caller['_zkType'] = $masterRequest->attributes->get('_zkType');
        $caller['_zkFunc'] = $masterRequest->attributes->get('_zkFunc');
        $caller['path'] = $masterRequest->getPathInfo();
        $caller['info'] = !empty($caller['_zkModule']) ? $this->get('zikula_extensions_module.extension_repository')->get($caller['_zkModule']) : [];
        // category we are in
        $requestedCid = $masterRequest->attributes->get('acid');
        if ($caller['_zkModule'] == 'ZikulaAdminModule' || empty($caller['_zkModule'])) {
            $cid = empty($requestedCid) ? $this->getVar('startcategory') : $requestedCid;
        } else {
            $cid = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getmodcategory', [
                'mid' => $caller['info']['id']
            ]);
        }
        $caller['category'] = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getCategory', [
            'cid' => $cid
        ]);
        // mode requested
        $mode = $currentRequest->attributes->has('mode') ? $currentRequest->attributes->get('mode') : 'categories';
        $mode = in_array($mode, ['categories', 'modules']) ? $mode : 'categories';
        // template requested
        $template = $currentRequest->attributes->has('template') ? $currentRequest->attributes->get('template') : 'tabs';
        $template = in_array($template, ['tabs', 'panel']) ? $template : 'tabs';
        // get admin capable modules
        $adminModules = $this->get('zikula_extensions_module.api.capability')->getExtensionsCapableOf('admin');
        // sort modules by displayname
        $moduleNames = [];
        foreach ($adminModules as $key => $module) {
            $moduleNames[$key] = $module['displayname'];
        }
        array_multisort($moduleNames, SORT_ASC, $adminModules);
        $moduleCategories = $this->getDoctrine()->getManager()->getRepository('ZikulaAdminModule:AdminCategoryEntity')->getIndexedCollection('cid');
        $menuModules = [];
        $menuCategories = [];
        foreach ($adminModules as $adminModule) {
            if (!$this->hasPermission("$adminModule[name]::", '::', ACCESS_EDIT)) {
                continue;
            }

            // cat
            $catid = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getmodcategory', [
                'mid' => $adminModule['id']
            ]);
            // order
            $order = ModUtil::apiFunc('ZikulaAdminModule', 'admin', 'getSortOrder', [
                'mid' => ModUtil::getIdFromName($adminModule['name'])
            ]);
            // url
            $menuTextUrl = isset($adminModule['capabilities']['admin']['route']) ? $this->get('router')->generate($adminModule['capabilities']['admin']['route']) : $adminModule['capabilities']['admin']['url'];

            $linkCollection = $this->get('zikula.link_container_collector')->getLinks($adminModule['name'], 'admin');
            $links = (false == $linkCollection)
                ? (array) ModUtil::apiFunc($adminModule['name'], 'admin', 'getLinks')
                : $linkCollection
                ;

            $module = [
                'menutexturl' => $menuTextUrl,
                'menutext' => $adminModule['displayname'],
                'menutexttitle' => $adminModule['description'],
                'modname' => $adminModule['name'],
                'order' => $order,
                'id' => $adminModule['id'],
                'links' => $links,
                'icon' => $masterRequest->getBasePath() . '/' . ModUtil::getModuleImagePath($adminModule['name'])
            ];

            $menuModules[$adminModule['name']] = $module;
            // category menu
            $menuCategories[$moduleCategories[$catid]['sortorder']]['title'] = $moduleCategories[$catid]['name'];
            $menuCategories[$moduleCategories[$catid]['sortorder']]['url'] = $this->get('router')->generate('zikulaadminmodule_admin_adminpanel', [
                'acid' => $moduleCategories[$catid]['cid']
            ]);
            $menuCategories[$moduleCategories[$catid]['sortorder']]['description'] = $moduleCategories[$catid]['description'];
            $menuCategories[$moduleCategories[$catid]['sortorder']]['cid'] = $moduleCategories[$catid]['cid'];
            $menuCategories[$moduleCategories[$catid]['sortorder']]['modules'][$adminModule['name']] = $module;
        }

        // add empty categories
        /** @var \Zikula\AdminModule\Entity\AdminCategoryEntity[] $moduleCategories */
        foreach ($moduleCategories as $moduleCategory) {
            if (array_key_exists($moduleCategory->getSortorder(), $menuCategories)) {
                continue;
            }

            $menuCategories[$moduleCategory->getSortOrder()] = [
                'title' => $moduleCategory->getName(),
                'url' => $this->get('router')->generate('zikulaadminmodule_admin_adminpanel', [
                    'acid' => $moduleCategory->getCid()
                ]),
                'description' => $moduleCategory->getDescription(),
                'cid' => $moduleCategory->getCid(),
                'modules' => []
            ];
        }
        ksort($menuCategories);
        $fullTemplateName = $mode . '.' . $template;

        return $this->render("@ZikulaAdminModule/AdminInterface/$fullTemplateName.html.twig", [
            'adminMenu' => ('categories' == $mode) ? $menuCategories : $menuModules,
            'mode' => $mode,
            'caller' => $caller
        ]);
    }
}
