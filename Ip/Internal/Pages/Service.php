<?php
/**
 * @package ImpressPages
 *
 *
 */
namespace Ip\Internal\Pages;





class Service
{

    public static function addZone($title, $name, $url, $layout, $metaTitle, $metaKeywords, $metaDescription, $position)
    {
        $zoneName = Model::addZone($title, $name, $url, $layout, $metaTitle, $metaKeywords, $metaDescription, $position);
        return $zoneName;
    }

    public static function updateZone($zoneName, $languageId, $title, $url, $name, $layout, $metaTitle, $metaKeywords, $metaDescription)
    {
        Model::updateZone($zoneName, $languageId, $title, $url, $name, $layout, $metaTitle, $metaKeywords, $metaDescription);
    }

    public static function deleteZone($zoneName)
    {
        Model::deleteZone($zoneName);
    }

    /**
     * @param string $zoneName
     * @param int $pageId
     * @param array $data
     */
    public static function updatePage($zoneName, $pageId, $data)
    {
        Db::updatePage($zoneName, $pageId, $data);
    }


    public static function addPage($languageId, $parentId, $title, $data = array())
    {
        if (!isset($data['navigationTitle'])) {
            $data['navigationTitle'] = $title;
        }
        if (!isset($data['pageTitle'])) {
            $data['pageTitle'] = $title;
        }

        if (!isset($data['url'])) {
            $data['url'] = Db::makeUrl($title);
        }

        if (!isset($data['createdOn'])) {
            $data['createdOn'] = date("Y-m-d");
        }
        if (!isset($data['lastModified'])) {
            $data['lastModified'] = date("Y-m-d");
        }
        if (!isset($data['visible'])) {
            $data['visible'] = !ipGetOption('Pages.hideNewPages');
        }

        $newPageId = Db::addPage($languageId, $parentId, $data);

        return $newPageId;
    }


    /**
     * @param string $menuName
     * @param int $languageId
     * @return int
     */
    public static function getRootId($menuName, $languageId)
    {
        return ipDb()->selectValue('id', 'navigation', array('languageId' => $languageId, 'name' => $menuName));
    }

    public static function copyPage($pageId, $destinationParentId, $destinationPosition)
    {
        $pageInfo = Db::pageInfo($pageId);
        $destinationPageInfo = Db::pageInfo($destinationParentId);
        $zoneName = Db::getZoneName($pageInfo['zone_id']);
        $destinationZone = ipContent()->getZone(Db::getZoneName($destinationPageInfo['zone_id']));
        return Model::copyPage($zoneName, $pageId, $destinationZone->getName(), $destinationParentId, $destinationPosition);
    }


    public static function movePage($pageId, $destinationParentId, $destinationPosition)
    {
        if (Db::isChild($destinationParentId, $pageId) || (int)$pageId === (int)$destinationParentId) {
            throw new \Ip\Exception(__("Can't move page inside itself.", 'ipAdmin', false));
        }

        //report url change
        $oldUrl = ipDb()->selectValue('uri', 'navigation', array('id' => $pageId));
        //report url change

        $newParentChildren = Db::pageChildren($destinationParentId);
        $newIndex = 0; //initial value

        if (count($newParentChildren) > 0) {
            $newIndex = $newParentChildren[0]['pageOrder'] - 1; //set as first page
            if ($destinationPosition > 0) {
                if (isset($newParentChildren[$destinationPosition - 1]) && isset($newParentChildren[$destinationPosition])) { //new position is in the middle of other pages
                    $newIndex = ($newParentChildren[$destinationPosition - 1]['pageOrder'] + $newParentChildren[$destinationPosition]['pageOrder']) / 2; //average
                } else { //new position is at the end
                    $newIndex = $newParentChildren[count($newParentChildren) - 1]['pageOrder'] + 1;
                }
            }
        }

        $data = array (
            'parentId' => $destinationParentId,
            'pageOrder' => $newIndex
        );
        Db::updatePage($pageId, $data);

        //report url change
        $page = $destinationZone->getPage($pageId);
        $newUrl = $page->getLink();

        ipEvent('ipUrlChanged', array('oldUrl' => $oldUrl, 'newUrl' => $newUrl));
        //report url change
    }

    public static function deletePage($pageId)
    {
        Model::deletePage($pageId);
    }



}
