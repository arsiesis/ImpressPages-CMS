<?php


namespace Ip\Internal\Pages;


class Filter
{
    public static function ipPageFormFields($fields, $info)
    {
        $navigation = $info['navigation'];
        $page = $info['page'];

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'navigation[navigationTitle]',
                'label' => __('Navigation title', 'ipAdmin', false),
                'value' => $navigation['navigationTitle'],
            ));

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'page[pageTitle]',
                'label' => __('Page title', 'ipAdmin', false),
                'value' => $page->getPageTitle()
            ));

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'page[keywords]',
                'label' => __('Keywords', 'ipAdmin', false),
                'value' => $page->getKeywords()
            ));

        $fields[] = new \Ip\Form\Field\Textarea(
            array(
                'name' => 'page[description]',
                'label' => __('Description', 'ipAdmin', false),
                'value' => $page->getDescription()
            ));

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'navigation[slug]',
                'label' => __('Url', 'ipAdmin', false),
                'value' => $navigation['slug']
            ));

        $fields[] = new \Ip\Form\Field\Checkbox(
            array(
                'name' => 'visible',
                'label' => __('Visible', 'ipAdmin', false),
                'value' => $page->isVisible()
            ));

        $layouts = \Ip\Internal\Design\Service::getLayouts();
        $options = array();
        foreach($layouts as $layout) {
            $options[] = array ($layout, $layout);
        }

        $curLayout = \Ip\Internal\ContentDb::getPageLayout($page->getId());

//        if (!$curLayout) {
//            $curLayout = $zone->getLayout();
//        }
        $fields[] = new \Ip\Form\Field\Select(
            array(
                'name' => 'layout',
                'label' => __('Layout', 'ipAdmin', false),
                'values' => $options,
//                'value' => $curLayout
            ));

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'page[createdOn]',
                'label' => __('Created on', 'ipAdmin', false),
                'value' => date('Y-m-d', strtotime($page->getCreatedOn()))
            ));

        $fields[] = new \Ip\Form\Field\Text(
            array(
                'name' => 'page[lastModified]',
                'label' => __('Update on', 'ipAdmin', false),
                'value' => date('Y-m-d', strtotime($page->getLastModified()))
            ));

        return $fields;
    }

    public static function ipPageFormValidate($errors, $data)
    {
        if (strtotime($data['page']['createdOn']) === false) {
            $errors[] = array('field' => 'createdOn', 'message' => __('Incorrect date format. Example:', 'ipAdmin', false).date(" Y-m-d"));
        }

        if (strtotime($data['page']['lastModified']) === false) {
            $errors[] = array('field' => 'lastModified', 'message' => __('Incorrect date format. Example:', 'ipAdmin', false).date(" Y-m-d"));
        }

        return $errors;
    }
} 
