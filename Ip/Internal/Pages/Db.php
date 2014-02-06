<?php
/**
 * @package ImpressPages
 *
 *
 */
namespace Ip\Internal\Pages;





class Db {






    public static function pageInfo($pageId){
        //check when root page id given
        $sql = "
        SELECT
            mte.*
        FROM
            ".ipTable('zone_to_page', 'mte')."
        WHERE
            mte.element_id = :pageId
        ";

        $params = array(
            'pageId' => $pageId
        );
        $answer = ipDb()->fetchRow($sql, $params);
        if ($answer) {
            return $answer;
        }

        //non root page id given
        $voidZone = new \Ip\Internal\Content\Zone(array());
        $breadcrumb = $voidZone->getBreadcrumb($pageId);
        $pageId = $breadcrumb[0]->getId();

        $sql = "
        SELECT
            mte.*
        FROM
            ".ipTable('zone_to_page', 'mte').",
            ".ipTable('page', 'page')."
        WHERE
            page.id = :pageId
            AND
            page.parent = mte.element_id
        ";

        $params = array(
            'pageId' => $pageId
        );
        return ipDb()->fetchRow($sql, $params);
    }


    public static function getZoneName($zoneId){
        $sql = "
        SELECT
            `name`
        FROM
            ".ipTable('zone')."
        WHERE
            id = :id";

        $params = array(
            'id' => (int)$zoneId
        );

        return ipDb()->fetchValue($sql, $params);
    }


    /**
     * @param $zoneId
     * @param $languageId
     * @return mixed
     * @throws \Ip\Exception
     */
    public static function rootId($zoneId, $languageId)
    {
        $sql = '
            SELECT
                mte.element_id
            FROM ' . ipTable('zone_to_page', 'mte') . ', ' . ipTable('language', 'l') . '
            WHERE l.id = :languageId AND  mte.language_id = l.id AND zone_id = :zoneId';

        $where = array(
            'languageId' => $languageId,
            'zoneId' => $zoneId
        );

        $pageId = ipDb()->fetchValue($sql, $where);
        if (!$pageId) {
            $pageId = self::createRootZoneElement($zoneId, $languageId);
        }

        if (!$pageId) {
            throw new \Ip\Exception("Failed to create root zone element. Zone: ". $zoneId . ', ' . $languageId);
        }

        return $pageId;
    }

    /**
     * @param $zoneId
     * @param $languageId
     * @throws \Ip\Exception
     */
    protected static function createRootZoneElement($zoneId, $languageId)
    {
        $pageId = ipDb()->insert('page', array('visible' => 1));

        ipDb()->insert('zone_to_page', array(
                'language_id' => $languageId,
                'zone_id' => $zoneId,
                'element_id' => $pageId,
            ));
        return $pageId;
    }


    public static function deleteRootZoneElements($languageId)
    {
        return ipDb()->delete('zone_to_page', array('language_id' => $languageId));
    }

    public static function isChild($pageId, $parentId)
    {
        $pageParentId = ipDb()->selectValue('parentId', 'navigation', array('id' => $pageId));

        if ($pageParentId == $parentId) {
            return true;
        }

        if ($pageParentId) {
            return self::isChild($pageParentId, $parentId);
        }

        return FALSE;
    }


    /**
     * Get page children
     * @param int $elementId
     * @return array
     */
    public static function pageChildren($parentId)
    {
        return ipDb()->selectAll('*', 'navigation', array('parentId' => $parentId), 'ORDER BY `pageOrder`');
    }

    /**
     *
     * Get page
     * @param int $id
     * @return array
     */
    private static function getPage($id)
    {
        return ipDb()->selectRow('*', 'page', array('id' => $id));
    }


    /**
     * @param int $language_id
     * @return array all website zones with meta tags for specified language
     */
    public static function getZones($languageId)
    {
        return ipDb()->selectAll('*, `navigationTitle` AS `title`', 'navigation', array('languageId' => $languageId, 'parentId' => 0), 'ORDER BY `pageOrder`');

        $sql = 'SELECT m.*, p.url, p.description, p.keywords, p.title
                FROM ' . ipTable('zone', 'm') . ', ' . ipTable('zone_to_language', 'p') . '
                WHERE
                    p.zone_id = m.id
                    AND p.language_id = ?
                ORDER BY m.row_number';

        return ipDb()->fetchAll($sql, array($languageId));
    }

    public static function updateNavigationEntry($navigationId, $data)
    {

    }

    /**
     * @param $pageId
     * @param $data
     * @return bool
     */
    public static function updatePage($pageId, $data)
    {
        $update = array();

        if (isset($data['navigationTitle'])) {
            $update['button_title'] = $data['navigationTitle'];
        }

        if (isset($data['pageTitle'])) {
            $update['page_title'] = $data['pageTitle'];
        }

        if (isset($data['keywords'])) {
            $update['keywords'] = $data['keywords'];
        }

        if (isset($data['description'])) {
            $update['description'] = $data['description'];
        }

        if (isset($data['createdOn']) && strtotime($data['createdOn']) !== false) {
            $update['created_on'] = $data['createdOn'];
        }

        if (isset($data['lastModified']) && strtotime($data['lastModified']) !== false) {
            $update['last_modified'] = $data['lastModified'];
        }

        if (isset($data['type'])) {
            $update['type'] = $data['type'];
        }

        if (isset($data['redirectURL'])) {
            $update['redirect_url'] = $data['redirectURL'];
        }

        if (isset($data['visible'])) {
            $update['visible'] = $data['visible'];
        }

        if (isset($data['parentId'])) {
            $update['parent'] = $data['parentId'];
        }

        if (isset($data['rowNumber'])) {
            $update['row_number'] = $data['rowNumber'];
        }

        if (isset($data['cached_html'])) {
            $update['cached_html'] = $data['cached_html'];
        }

        if (isset($data['cached_text'])) {
            $update['cached_text'] = $data['cached_text'];
        }

        if (count($update) == 0) {
            return true; //nothing to update.
        }

        $dump = array(
            'pageId' => $pageId,
            'update' => $update,
        );

        ipDb()->update('page', $update, array('id' => $pageId));

//        if (isset($data['url']) && $oldPage['uri']) {
//            $newPage = ipDb()->selectRow('*', 'navigation', array('id' => $pageId));
//            ipEvent('ipUrlChanged', array('oldUrl' => $oldPage['uri'], 'newUrl' => $newPage['uri']));
//        }

//        if (!empty($params['layout']) && \Ip\Internal\File\Functions::isFileInDir($params['layout'], ipThemeFile(''))) {
//            $layout = $params['layout'] == $zone->getLayout() ? false : $params['layout']; // if default layout - delete layout
//            self::changePageLayout($zone->getAssociatedModule(), $pageId, $layout);
//        }

        return true;
    }

    /**
     * @param $groupName
     * @param $moduleName
     * @param $pageId
     * @param $newLayout
     * @return bool whether layout was changed or not
     */
    private static function changePageLayout($moduleName, $pageId, $newLayout) {
        $dbh = ipDb()->getConnection();

        $sql = 'SELECT `layout`
                FROM ' . ipTable('page_layout') . '
                WHERE module_name = :moduleName
                    AND `page_id`   = :pageId';
        $q = $dbh->prepare($sql);
        $q->execute(
            array(
                'moduleName' => $moduleName,
                'pageId' => $pageId,
            )
        );
        $oldLayout = $q->fetchColumn(0);

        $wasLayoutChanged = false;

        if (empty($newLayout)) {
            if ($oldLayout) {
                $sql = 'DELETE FROM ' . ipTable('page_layout') . '
                        WHERE `module_name` = :moduleName
                            AND `page_id` = :pageId';
                $q = $dbh->prepare($sql);
                $result = $q->execute(
                    array(
                        'moduleName' => $moduleName,
                        'pageId' => $pageId,
                    )
                );
                $wasLayoutChanged = true;
            }
        } elseif ($newLayout != $oldLayout && file_exists(ipThemeFile($newLayout))) {
            if (!$oldLayout) {
                ipDb()->insert('page_layout', array(
                        'module_name' => $moduleName,
                        'page_id' => $pageId,
                        'layout' => $newLayout
                    ), true);
                $wasLayoutChanged = true;
            } else {
                ipDb()->update('page_layout', array(
                        'layout' => $newLayout,
                    ), array(
                        'module_name' => $moduleName,
                        'page_id' => $pageId,
                    ));
                $wasLayoutChanged = true;
            }
        }

        return $wasLayoutChanged;
    }

    /**
     *
     * Insert new page
     * @param int $parentId navigation parentId
     * @param array $params
     */
    public static function addPage($languageId, $parentId, $params)
    {
        $page = array();

        //TODOXX sync page service naming. #140
        if (isset($params['page_title'])) {
            $params['pageTitle'] = $params['page_title'];
        }

        if (isset($params['pageTitle'])) {
            $page['page_title'] = $params['pageTitle'];
        }

        if (isset($params['keywords'])) {
            $page['keywords'] = $params['keywords'];
        }

        if (isset($params['description'])) {
            $page['description'] = $params['description'];
        }

        if (isset($params['url'])) {
            $page['url'] = $params['url'];
        }

        if (isset($params['createdOn'])) {
            $page['created_on'] = $params['createdOn'];
        } else {
            $page['created_on'] = date('Y-m-d');
        }

        if (isset($params['lastModified'])) {
            $page['last_modified'] = $params['lastModified'];
        } else {
            $page['last_modified'] = date('Y-m-d');
        }

        if (isset($params['type'])) {
            $page['type'] = $params['type'];
        }

        if (isset($params['redirectURL'])) {
            $page['redirect_url'] = $params['redirectURL'];
        }

        if (isset($params['visible'])) {
            $page['visible'] = (int)$params['visible'];
        }

        if (isset($params['cached_html'])) {
            $page['cached_html'] = $params['cached_html'];
        }

        if (isset($params['cached_text'])) {
            $page['cached_text'] = $params['cached_text'];
        }

        $pageId = ipDb()->insert('page', $page);

        $navigationRecord = array(
            'pageId' => $pageId,
            'languageId' => $languageId,
            'parentId' => $parentId,
            'pageOrder' => static::getNextPageOrder($languageId, $parentId),
        );

        if (!empty($page['url'])) {
            $navigationRecord['slug'] = $page['url'];
            $parentUri = ipDb()->selectValue('uri', 'navigation', array('id' => $parentId));
            $navigationRecord['uri'] = $parentUri ? $parentUri . '/' . $page['url'] : $page['url'];
        }

        ipDb()->insert('navigation', $navigationRecord);
    }

    private static function getNextPageOrder($languageId, $parentId)
    {
        return ipDb()->selectValue('MAX(`pageOrder`) + 1', 'navigation', array(
                'languageId' => $languageId,
                'parentId' => $parentId,
            )
        );
    }

    /**
     *
     * Delete menu element record
     * @param int $navigationId
     */
    public static function deletePage($navigationId)
    {
        $pageId = ipDb()->selectValue('pageId', 'navigation', array('id' => $navigationId));
        ipDb()->delete('navigation', array('id' => $navigationId));
        if ($pageId) {
            ipDb()->delete('page', array('id' => $pageId));
            ipEvent('ipPageDeleted', array('pageId' => $pageId));
        }
    }

    public static function copyPage($nodeId, $newParentId, $newIndex)
    {
        $db = ipDb();
        $copy = $db->selectRow('*', 'page', array('id' => $nodeId));
        if (!$copy) {
            trigger_error("Element does not exist");
        }

        unset($copy['id']);
        $copy['parent'] = $newParentId;
        $copy['row_number'] = $newIndex;
        $copy['url'] = self::ensureUniqueUrl($copy['url']);

        return ipDb()->insert('page', $copy);
    }


    /**
     * @param string $uri
     * @param int $allowed_id
     * @returns bool true if url is available ignoring $allowed_id page.
     */
    public static function availableUrl($uri, $allowedId = null)
    {
        $pageId = ipDb()->selectValue('`id`', 'navigation', array('uri' => $uri));

        if (!$pageId) {
            return true;
        }

        if ($allowedId && $pageId == $allowedId) {
            return true;
        }

        return false;
    }

    /**
     *
     * Create unique URL
     * @param string $url
     * @param int $allowed_id
     */
    public static function makeUrl($url, $allowed_id = null)
    {

        if ($url == '') {
            $url = 'page';
        }

        $url = mb_strtolower($url);
        $url = \Ip\Internal\Text\Transliteration::transform($url);

        $replace = array(
            " " => "-",
            "/" => "-",
            "\\" => "-",
            "\"" => "-",
            "\'" => "-",
            "„" => "-",
            "“" => "-",
            "&" => "-",
            "%" => "-",
            "`" => "-",
            "!" => "-",
            "@" => "-",
            "#" => "-",
            "$" => "-",
            "^" => "-",
            "*" => "-",
            "(" => "-",
            ")" => "-",
            "{" => "-",
            "}" => "-",
            "[" => "-",
            "]" => "-",
            "|" => "-",
            "~" => "-",
            "." => "-",
            "'" => "",
            "?" => "",
            ":" => "",
            ";" => "",
        );
        $url = strtr($url, $replace);

        if ($url == ''){
            $url = '-';
        }

        $url = preg_replace('/-+/', '-', $url);

        if (self::availableUrl($url, $allowed_id)) {
            return $url;
        }

        $i = 1;
        while (!self::availableUrl($url.'-'.$i, $allowed_id)) {
            $i++;
        }

        return $url.'-'.$i;
    }



    public static function ensureUniqueUrl($url, $allowedId = null) {
        $url = str_replace("/", "-", $url);

        if(self::availableUrl($url, $allowedId))
          return $url;

        $i = 1;
        while(!self::availableUrl($url.'-'.$i, $allowedId)) {
          $i++;
        }

        return $url.'-'.$i;
    }

}
