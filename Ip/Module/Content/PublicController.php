<?php
/**
 * @package ImpressPages
 *
 */
namespace Ip\Module\Content;


class PublicController extends \Ip\Controller
{
    public function index()
    {
        //TODOX set page specific layout
        if (
            \Ip\ServiceLocator::getContent()->getLanguageUrl() != ipGetCurrentlanguage()->getUrl() ||
            ipGetCurrentPage()->getType() === 'error404'
        ) {
            return new \Ip\Response\PageNotFound();
        }

        if (in_array(ipGetCurrentPage()->getType(), array('subpage', 'redirect')) && !ipIsManagementState()) {
            return new \Ip\Response\Redirect(ipGetCurrentPage()->getLink());
        }

        ipSetBlockContent('main', ipGetCurrentPage()->generateContent());
        if (\Ip\Module\Admin\Service::isSafeMode()) {
            ipSetLayout(ipConfig()->coreModuleFile('Admin/View/safeModeLayout.php'));
        }

        if (\Ip\ServiceLocator::getContent()->isManagementState()) {
            $this->initManagement();
        }



    }

    private function initManagement()
    {

        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/tinymce/paste_preprocess.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/tinymce/min.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/tinymce/med.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/tinymce/max.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/tinymce/table.js'));


        ipAddJavascriptVariable('ipContentInit', Model::initManagementData());

        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/ipContentManagement.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/jquery.ip.contentManagement.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/jquery.ip.pageOptions.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/jquery.ip.widgetbutton.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/jquery.ip.block.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/jquery.ip.widget.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/exampleContent.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Content/public/drag.js'));


        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/jquery-ui/jquery-ui.js'));
        ipAddCss(ipConfig()->coreModuleUrl('Assets/assets/js/jquery-ui/jquery-ui.css'));

        //TODOX enable ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/jquery-tools/jquery.tools.ui.scrollable.js'));

        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/tiny_mce/jquery.tinymce.min.js'));

        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/plupload/plupload.full.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/plupload/plupload.browserplus.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/plupload/plupload.gears.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Assets/assets/js/plupload/jquery.plupload.queue/jquery.plupload.queue.js'));


        ipAddJavascript(ipConfig()->coreModuleUrl('Upload/assets/jquery.ip.uploadImage.js'));
        ipAddJavascript(ipConfig()->coreModuleUrl('Upload/assets/jquery.ip.uploadFile.js'));

        ipAddCss(ipConfig()->coreModuleUrl('Content/public/widgets.css'));
        ipAddJavascriptVariable('isMobile', \Ip\Browser::isMobile());

    }

}