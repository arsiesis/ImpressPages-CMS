<?php
/**
 * @package ImpressPages
 *
 *
 */

namespace Ip\Module\Pages;

class LanguageModel{
    //TODOX review

    public function addLanguage($code, $url, $d_long = '', $d_short = '', $visible = true, $text_direction='ltr'){

        if (($code!='') && ($url!='')){

            $data = Array();
            $data['code'] = $code;
            $data['url'] = $url;
            $data['d_long'] = $d_long;
            $data['d_short'] = $d_short;
            $data['visible'] = $visible;
            $data['text_direction'] = $text_direction;
            $id = IpDb()->insert(DB_PREF . 'language', $data);

//
//            $data = Array();
//            description
//            keywords
//            title
//            url
//            zone_id
//            language_id
//            translation
//              $id = IpDb()->insert(DB_PREF . 'zone_parameter', $data);

            $this->afterInsert($id);

            return true;
        }else{
            trigger_error("Can't create language. Missing URL or language code.");
        }
    }

    public function updateLanguage($languageId, $data) {

        $condition = array(
            'id' => $languageId
        );

        $language = $this->getLanguageByUrl($data['url']);
        if ($language && $language['id'] != $languageId) {
            throw new DuplicateUrlException($data['url']);
        }

        $originalLanguage = self::getLanguageById($languageId);
        $originalUrl = ipFileUrl($originalLanguage['url']) . '/';

        ipDb()->update(DB_PREF . 'language', $data, $condition);

        $newUrl = ipFileUrl($data['url']) . '/';

        if ($originalUrl != $newUrl){
            ipDispatcher()->notify('site.urlChanged', array('oldUrl' => $originalUrl, 'newUrl' => $newUrl));
        }
    }

    private function afterInsert($id) {
        $this->createRootZoneElement($id);
    }

    private function afterDelete($id) {
        self::deleteRootZoneElement($id);
    }


    private function beforeUpdate($id) {
        $tmpLanguage = self::getLanguageById($id);
        $this->urlBeforeUpdate = $tmpLanguage['url'];
    }


    private function afterUpdate($id) {

        $tmpLanguage = self::getLanguageById($id);
        if($tmpLanguage['url'] != $this->urlBeforeUpdate && ipGetOption('Config.multilingual')) {
            $oldUrl = BASE_URL.$this->urlBeforeUpdate.'/';
            $newUrl = BASE_URL.$tmpLanguage['url'].'/';
            ipDispatcher()->notify('site.urlChanged', array('oldUrl' => $oldUrl, 'newUrl' => $newUrl));
        }
    }

    private function allowDelete($id) {
        $dbMenuManagement = new Db();

        $answer = true;


        $zones = self::getZones();
        foreach($zones as $key => $zone) {
            $rootElement = $dbMenuManagement->rootContentElement($zone['id'], $id);
            $elements = $dbMenuManagement->pageChildren($rootElement);
            if(sizeof($elements) > 0) {
                $answer = false;
                $this->errors['delete'] = __('Can\'t delete language with existing content in it', 'ipAdmin');
            }
        }

        if(sizeof(self::getLanguages()) ==1) {
            $answer = false;
            $this->errors['delete'] = __('Can\'t delete last language', 'ipAdmin');
        }


        return $answer;
    }



    private function getLanguages() {
        $answer = array();
        $sql = "select * from `".DB_PREF."language` where 1 order by row_number";
        $rs = mysql_query($sql);
        if($rs) {
            while($lock = mysql_fetch_assoc($rs))
                $answer[] = $lock;
        }else {
            trigger_error($sql." ".mysql_error());
        }
        return $answer;
    }

    private function getLanguageById($id) {
        $sql = "
            SELECT
                *
            FROM
                `".DB_PREF."language`
            WHERE
                `id` = :id ";
        $params = array (
            'id' => $id
        );
        $result = ipDb()->fetchRow($sql, $params);
        return $result;
    }

    public static function getLanguageByUrl($url) {
        $sql = "
            SELECT
                *
            FROM
                `".DB_PREF."language`
            WHERE
                `url` = :url ";
        $params = array (
            'url' => $url
        );
        $result = ipDb()->fetchRow($sql, $params);
        return $result;
    }

    private function getZones() {
        $sql = "
            SELECT
                *
            FROM
                `".DB_PREF."zone`
            ORDER BY
                `row_number`";
        return ipDb()->fetchAll($sql);
    }

    private function deleteRootZoneElement($language) {
        $zones = self::getZones();
        foreach($zones as $key => $zone) {

            $sql = "delete `".DB_PREF."content_element`.*, `".DB_PREF."zone_to_content`.* from `".DB_PREF."content_element`, `".DB_PREF."zone_to_content` where
      `".DB_PREF."zone_to_content`.zone_id = ".$zone['id']." and `".DB_PREF."zone_to_content`.element_id = `".DB_PREF."content_element`.id and `".DB_PREF."zone_to_content`.language_id = '".mysql_real_escape_string($language)."'";
            $rs = mysql_query($sql);
            if(!$rs) {
                trigger_error($sql." ".mysql_error());
            }

            $sql2 = "delete from `".DB_PREF."zone_parameter` where language_id = '".mysql_real_escape_string($language)."'";
            $rs2 = mysql_query($sql2);
            if(!$rs2)
                trigger_error($sql2." ".mysql_error());

        }


    }

    private function createRootZoneElement($language) {
        $firstLanguage = \Ip\Internal\ContentDb::getFirstLanguage();
        $zones = \Ip\Internal\ContentDb::getZones($firstLanguage['id']);



        foreach($zones as $key => $zone) {
                $sql2 = "insert into `".DB_PREF."zone_parameter` set
            `language_id` = :language_id,
            `zone_id` = :zone_id,
            `title` = :title,
            `url` = :url";

            $params = array(
                'language_id' => $language,
                'zone_id' => $zone['id'],
                'title' => $this->newUrl($language, $zone['title']),
                'url' => $this->newUrl($language, $zone['url'])
            );


            ipDb()->execute($sql2, $params);
        }
    }


    private function newUrl($language, $url = 'zone') {
        $sql = "
            SELECT
                `url`
            FROM
                `".DB_PREF."zone_parameter`
            WHERE
                `language_id` = :languageId
        ";
        $params = array(
            'languageId' => $language
        );
        $results = ipDb()->fetchAll($sql, $params);

        foreach ($results as $lock) {
            $urls[$lock['url']] = 1;
        }

        if (isset($urls[$url])) {
            $i = 1;
            while(isset($urls[$url.$i])) {
                $i++;
            }
            return $url.$i;
        } else {
            return $url;
        }
    }




}

