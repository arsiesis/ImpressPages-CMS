<?php
/**
 * @package ImpressPages
 *
 */

namespace Ip\Internal\Pages;





class AdminController extends \Ip\Controller
{


    public function index()
    {
        ipAddJs('Ip/Internal/Ip/assets/js/angular.js');
        ipAddJs('Ip/Internal/Pages/assets/js/pages.js');
        ipAddJs('Ip/Internal/Pages/assets/js/zones.js');
        ipAddJs('Ip/Internal/Pages/assets/js/jquery.pageTree.js');
        ipAddJs('Ip/Internal/Pages/assets/js/jquery.pageProperties.js');
        ipAddJs('Ip/Internal/Pages/assets/jstree/jquery.jstree.js');
        ipAddJs('Ip/Internal/Pages/assets/jstree/jquery.cookie.js');
        ipAddJs('Ip/Internal/Pages/assets/jstree/jquery.hotkeys.js');

        ipAddJsVariable('languageList', Helper::languageList());
        ipAddJsVariable('zoneList', Helper::zoneList());

        $variables = array(
            'addPageForm' => Helper::addPageForm(),
            'addZoneForm' => Helper::addZoneForm(),
            'languagesUrl' => ipConfig()->baseUrl() . '?aa=Languages.index'
        );
        $layout = ipView('view/layout.php', $variables);
        return $layout->render();
    }

    public function getPages()
    {
        $data = ipRequest()->getRequest();
        if (empty($data['languageId'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $languageId = (int)$data['languageId'];

        if (empty($data['menuName'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $menuName = $data['menuName'];

        $responseData = array (
            'tree' => JsTreeHelper::getPageTree($languageId, $menuName)
        );

        return new \Ip\Response\Json($responseData);

    }

    public function pagePropertiesForm()
    {
        $navigationId = ipRequest()->getQuery('navigationId');
        if (!$navigationId) {
            throw new \Ip\Exception("Missing required parameters");
        }

        $variables = array(
            'form' => Helper::pagePropertiesForm($navigationId)
        );
        $layout = ipView('view/pageProperties.php', $variables)->render();

        $data = array (
            'html' => $layout
        );
        return new \Ip\Response\Json($data);
    }

    public function updatePage()
    {
        ipRequest()->mustBePost();
        $data = ipRequest()->getPost();

        if (empty($data['pageId'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $pageId = (int)$data['pageId'];

        if (empty($data['zoneName'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $zoneName = $data['zoneName'];

        $answer = array();



        //make url
        if ($data['url'] == '') {
            if ($data['pageTitle'] != '') {
                $data['url'] = Db::makeUrl($data['pageTitle'], $pageId);
            } else {
                if ($data['navigationTitle'] != '') {
                    $data['url'] = Db::makeUrl($data['navigationTitle'], $pageId);
                }
            }
        } else {
            $tmpUrl = str_replace("/", "-", $data['url']);
            $i = 1;
            while (!Db::availableUrl($tmpUrl, $pageId)) {
                $tmpUrl = $data['url'].'-'.$i;
                $i++;
            }
            $data['url'] = $tmpUrl;
        }
        //end make url

        if (strtotime($data['createdOn']) === false) {
            $answer['errors'][] = array('field' => 'createdOn', 'message' => __('Incorrect date format. Example:', 'ipAdmin', false).date(" Y-m-d"));
        }

        if (strtotime($data['lastModified']) === false) {
            $answer['errors'][] = array('field' => 'lastModified', 'message' => __('Incorrect date format. Example:', 'ipAdmin', false).date(" Y-m-d"));
        }

//      TODOXX implement page type in Pages module #138
//        if ($data['type'] == 'redirect' && $data['redirectURL'] == '') {
//            $answer['errors'][] = array('field' => 'redirectURL', 'message' => __('External url can\'t be empty', 'ipAdmin', false));
//        }

        $data['visible'] = !empty($data['visible']);
        if (empty($answer['errors'])) {
            Service::updatePage($zoneName, $pageId, $data);
            $answer['status'] = 'success';
        } else {
            $answer['status'] = 'error';
        }

        return new \Ip\Response\Json($answer);



    }

    public function updateZoneForm()
    {
        $data = ipRequest()->getQuery();
        if (empty($data['zoneName'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $zoneName = $data['zoneName'];


        $form = Helper::zoneForm($zoneName);
        $html = $form->render();

        $data = array (
            'html' => $html
        );
        return new \Ip\Response\Json($data);
    }

    public function updateZone()
    {
        $data = ipRequest()->getPost();

        $requiredData = array('zoneName', 'languageId', 'title', 'url', 'name', 'layout', 'metaTitle', 'metaKeywords', 'metaDescription');

        foreach($requiredData as $required) {
            if (!array_key_exists($required, $data)) {
                throw new \Ip\Exception("Missing required parameters");
            }
        }

        $zoneName = $data['zoneName'];
        $languageId = $data['languageId'];
        $title = $data['title'];
        $url = $data['url'];
        $name = $data['name'];
        $layout = $data['layout'];
        $metaTitle = $data['metaTitle'];
        $metaKeywords = $data['metaKeywords'];
        $metaDescription = $data['metaDescription'];

        Service::updateZone($zoneName, $languageId, $title, $url, $name, $layout, $metaTitle, $metaKeywords, $metaDescription);

        $answer = array(
            'status' => 'success'
        );

        return new \Ip\Response\Json($answer);
    }

    public function deleteZone()
    {
        ipRequest()->mustBePost();
        $data = ipRequest()->getPost();

        if (empty($data['zoneName'])) {
            throw new \Ip\Exception('Missing required parameters');
        }
        $zoneName = $data['zoneName'];

        Service::deleteZone($zoneName);

        $answer = array(
            'status' => 'error'
        );

        return new \Ip\Response\Json($answer);
    }

    public function addZone()
    {
        ipRequest()->mustBePost();
        $data = ipRequest()->getPost();

        if (!empty($data['title'])) {
            $title = $data['title'];
        } else {
            $title = __('Untitled', 'ipAdmin', false);
        }

        $transliterated = \Ip\Internal\Text\Transliteration::transform($title);
        $url = preg_replace('/[^a-z0-9_\-]/i', '', strtolower($transliterated));
        $name = preg_replace('/[^a-z0-9_\-]/i', '', strtolower($transliterated));

        $zoneName = Service::addZone($title, $name, $url, 'main.php', '', '', '', 100000000);
        $zoneId = ipContent()->getZone($zoneName)->getId();


        $answer = array(
            'status' => 'success',
            'zoneId' => $zoneId
        );

        return new \Ip\Response\Json($answer);
    }

    public function addPage()
    {
        $request = ipRequest();
        $request->mustBePost();

        $menuName = $request->getPost('menuName');
        $languageId = $request->getPost('languageId');

        if (empty($menuName) || empty($languageId)) {
            throw new \Ip\Exception("Missing required parameters");
        }

        $rootId = Service::getRootId($menuName, $languageId);

        $title = $request->getPost('title');
        if (empty($title)) {
            $title = __('Untitled', 'ipAdmin', false);
        }

        $data = array();
        $data['visible]'] = $request->getPost('visible', 0);

        $pageId = Service::addPage($languageId, $rootId, $title, $data);


        $answer = array(
            'status' => 'success',
            'pageId' => $pageId
        );

        return new \Ip\Response\Json($answer);

    }

    public function deletePage()
    {
        ipRequest()->mustBePost();
        $pageId = ipRequest()->getPost('pageId');

        if (!$pageId) {
            throw new \Ip\Exception("Page id is not set");
        }

        Service::deletePage($pageId);

        $answer = array ();
        $answer['status'] = 'success';

        return new \Ip\Response\Json($answer);
    }

    public function movePage()
    {
        ipRequest()->mustBePost();
        $data = ipRequest()->getPost();

        if (!isset($data['pageId'])) {
            throw new \Ip\Exception("Page id is not set");
        }
        $pageId = (int)$data['pageId'];


        if (!empty($data['destinationParentId'])) {
            $destinationParentId = $data['destinationParentId'];
        } else {
            if (!isset($data['menuName'])) {
                throw new \Ip\Exception("Missing required parameters");
            }
            if (!isset($data['languageId'])) {
                throw new \Ip\Exception("Missing required parameters");
            }
            $destinationParentId = Db::rootId($data['menuName'], $data['languageId']);
        }


        if (!isset($data['destinationPosition'])) {
            throw new \Ip\Exception("Destination position is not set");
        }
        $destinationPosition = $data['destinationPosition'];


        try {
            Service::movePage($pageId, $destinationParentId, $destinationPosition);
        } catch (\Ip\Exception $e) {
            $answer = array (
                'status' => 'error',
                'error' => $e->getMessage()
            );
            return new \Ip\Response\Json($answer);
        }


        $answer = array (
            'status' => 'success'
        );

        return new \Ip\Response\Json($answer);



    }


    public function copyPage()
    {
            ipRequest()->mustBePost();
            $data = ipRequest()->getPost();


            if (!isset($data['pageId'])) {
                throw new \Ip\Exception("Page id is not set");
            }
            $pageId = (int)$data['pageId'];


            if (!empty($data['destinationParentId'])) {
                $destinationParentId = $data['destinationParentId'];
            } else {
                if (!isset($data['zoneName'])) {
                    throw new \Ip\Exception("Missing required parameters");
                }
                if (!isset($data['languageId'])) {
                    throw new \Ip\Exception("Missing required parameters");
                }
                $zone = ipContent()->getZone($data['zoneName']);
                $destinationParentId = Db::rootId($zone->getId(), $data['languageId']);
            }


            if (!isset($data['destinationPosition'])) {
                throw new \Ip\Exception("Destination position is not set");
            }
            $destinationPosition = $data['destinationPosition'];


            try {
                Service::copyPage($pageId, $destinationParentId, $destinationPosition);
            } catch (\Ip\Exception $e) {
                $answer = array (
                    'status' => 'error',
                    'error' => $e->getMessage()
                );
                return new \Ip\Response\Json($answer);
            }


            $answer = array (
                'status' => 'success'
            );

            return new \Ip\Response\Json($answer);


    }

    public function getPageUrl()
    {
        $data = ipRequest()->getQuery();


        if (!isset($data['pageId'])) {
            throw new \Ip\Exception("Page id is not set");
        }
        $pageId = (int)$data['pageId'];

        $pageInfo = Db::pageInfo($pageId);

        $zoneName = Db::getZoneName($pageInfo['zone_id']);
        $zone = IpContent()->getZone($zoneName);

        $page = $zone->getPage($pageId);
        $answer = array (
            'pageUrl' => $page->getLink()
        );

        return new \Ip\Response\Json($answer);
    }

    public function sortZone()
    {
        ipRequest()->mustBePost();
        $data = ipRequest()->getPost();


        if (empty($data['zoneName']) || !isset($data['newIndex'])) {
            throw new \Ip\Exception("Missing required parameters");
        }
        $zoneName = $data['zoneName'];
        $newIndex = $data['newIndex'];

        Model::sortZone($zoneName, $newIndex);

        return new \Ip\Response\Json(array(
            'error' => 0
        ));
    }


}
