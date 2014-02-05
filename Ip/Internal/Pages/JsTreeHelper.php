<?php
/**
 * @package ImpressPages
 *
 */

namespace Ip\Internal\Pages;


class JsTreeHelper
{

    public static function getPageTree($languageId, $menuName)
    {
        $answer = self::getList($languageId, $menuName, null);
        return $answer;
    }

    /**
     * @param $languageId
     * @param $menuName
     * @param \Ip\Page[] $pages
     * @return array
     */
    protected static function getList ($languageId, $menuName, $parentId = null)
    {
        $navigation = ipDb()->selectRow('*', 'navigation', array('name' => $menuName));

        if (!$parentId) {
            $parentId = $navigation['id'];
        }

        $pages = ipDb()->selectAll('*', 'navigation', array('parentId' => $parentId), 'ORDER BY `pageOrder`');

        $answer = array();

        //generate jsTree response array
        foreach ($pages as $page) {

            $pageData = array();

            $pageData['state'] = 'closed';

            $jsTreeId = self::_jsTreeId($languageId, $menuName, $page['id']);
            if (!empty($_SESSION['Pages.nodeOpen'][$jsTreeId])) {
                $pageData['state'] = 'open';
            }

            $children = self::getList($languageId, $menuName, $page['id']);
            if (count($children) === 0) {
                $pageData['children'] = false;
                $pageData['state'] = 'leaf';
            }
            $pageData['children'] = $children;


            if (!$page['isActive']) {
                $icon = '';
            } else {
                $icon = ipFileUrl('Ip/Internal/Pages/assets/img/file_hidden.png');
            }

            $pageData['attr'] = array('id' => $jsTreeId, 'rel' => 'page', 'languageId' => $languageId, 'zoneName' => $menuName, 'pageId' => $page['id']);
            $title = $page['navigationTitle'] ? $page['navigationTitle'] : ipDb()->selectValue('page_title', 'page', array('id' => $page['pageId']));
            $pageData['data'] = array ('title' => $title . '', 'icon' => $icon); //transform null into empty string. Null break JStree into infinite loop
            $answer[] = $pageData;
        }

        return $answer;
    }

    protected static function _jsTreeId($languageId, $menuName, $pageId)
    {
        return 'page_' . $languageId . '_' . $menuName . '_' . $pageId;
    }


}
