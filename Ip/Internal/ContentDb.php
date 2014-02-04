<?php
/**
 * @package ImpressPages
 *
 *
 */
namespace Ip\Internal;

/**
 * Class for common tasks with database
 * @package ImpressPages
 */
class ContentDb {

    /**
     * @param int $language_id
     * @return array all website zones with meta tags for specified language
     */
    public static function getZones($languageId)
    {
        $sql = 'SELECT m.*, p.url, p.description, p.keywords, p.title
                FROM ' . ipTable('zone', 'm') . ', ' . ipTable('zone_to_language', 'p') . '
                WHERE
                    p.zone_id = m.id
                    AND p.language_id = ?
                ORDER BY m.row_number';

        $list = ipDb()->fetchAll($sql, array($languageId));

        $zones = array();
        foreach ($list as $zone) {
            $zones[$zone['name']] = $zone;
        }

        return $zones;
    }

    /**
     * Finds first language of website
     * @return array
     */
    public static function getFirstLanguage() {
        return ipDb()->selectRow('*', 'language', array('visible' => 1), 'ORDER BY `row_number`');
    }


    /**
     * @param bool $includeHidden
     * @return array all visible website's languages
     */
    public static function getLanguages($includeHidden = false) {

        $where = array();

        if (!$includeHidden) {
            $where['visible'] = 1;
        }

        $rs = ipDb()->selectAll('*', 'language', $where, 'ORDER BY `row_number` DESC ');
        $languages = array();
        foreach ($rs as $language) {
            $languages[$language['id']] = $language;
        }

        return $languages;
    }


    /**
     * @param string $pageId
     * @return string|null
     */
    public static function getPageLayout($pageId)
    {
        return ipDb()->selectValue('layout', 'page_layout', array('pageId' => $pageId));
    }

}
