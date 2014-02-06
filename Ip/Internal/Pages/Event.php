<?php
/**
 * @package ImpressPages
 *
 */
namespace Ip\Internal\Pages;


class Event
{

    public static function ipLanguageAdded($data)
    {
        $languageId = $data['id'];
        Model::createParametersLanguage($languageId);
    }

    public static function ipBeforeLanguageDeleted($data)
    {
        $languageId = $data['id'];
        Model::cleanupLanguage($languageId);
    }

    public static function ipBeforeZoneDeleted($data)
    {
        $zoneId = $data['id'];
        Model::removeZonePages($zoneId);
        ipDb()->delete('zone_to_page', array('zone_id' => $zoneId));
    }

    /**
     * Updates navigation properties
     *
     * @param $data
     */
    public static function ipPagePropertiesUpdate_20($data)
    {
        if (empty($data['navigation'])) {
            return;
        }

        $navigation = $data['navigation'];

        $oldNavigation = ipDb()->selectRow('*', 'navigation', array('id' => $data['pageId']));

        //make url
        if ($navigation['slug'] != $oldNavigation['slug']) {

            if (empty($navigation['slug'])) {

                $title = !empty($navigation['navigationTitle']) ? $navigation['navigationTitle'] : $data['pageTitle'];
                $navigation['slug'] = Db::makeUrl($title, $data['pageId']);
                $navigation['uri'] = $navigation['slug'];
            } else {
                $navigation['slug'] = str_replace("/", "-", $navigation['slug']);

                $parentId = ipDb()->selectValue('parentId', 'navigation', array('id' => $data['pageId']));
                if ($parentId) {
                    $uriPrefix = ipDb()->selectValue('uri', 'navigation', array('id' => $parentId)) . '/';
                } else {
                    $uriPrefix = '';
                }

                if (!Db::availableUrl($uriPrefix . $navigation['slug'], $data['pageId'])) {
                    $i = 1;
                    while (!Db::availableUrl($uriPrefix . $navigation['slug'] . '-' . $i, $data['pageId'])) {
                        $i++;
                    }

                    $navigation['slug'] = $navigation['slug'] . '-' . $i;
                    $navigation['uri'] = $uriPrefix . $navigation['slug'];
                }
            }

            // TODOX url change event (just not here!)
        }

        ipDb()->update('navigation', $navigation, array('id' => $data['pageId']));
        //end make url

//      TODOXX implement page type in Pages module #138
//        if ($data['type'] == 'redirect' && $data['redirectURL'] == '') {
//            $answer['errors'][] = array('field' => 'redirectURL', 'message' => __('External url can\'t be empty', 'ipAdmin', false));
//        }

//        $data['visible'] = !empty($data['visible']);
//        Service::updatePage($data['pageId'], $data);
    }

    /**
     * Updates page properties
     * @param $data
     */
    public static function ipPagePropertiesUpdate_22($data)
    {
        if (empty($data['page'])) {
            return;
        }

        $pageId = ipDb()->selectValue('pageId', 'navigation', array('id' => $data['pageId']));

        Service::updatePage($pageId, $data['page']);
    }
}
