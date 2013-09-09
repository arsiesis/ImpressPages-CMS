<?php if (!defined('CMS')) exit; ?>
<?php
/**
 * This comment block is used just to make IDE suggestions to work
 * @var $this \Ip\View
 */
?>
<?php echo $this->subview('_header.php'); ?>
        <div class="side col_3 left">
            <nav>
                <?php
                // generate 2 - 7 levels submenu of top menu.
                // please note that it is possible to generate second level only if first level item is selected
                $pages = \Ip\Menu\Helper::getZoneItems('menu1', 2, 7);
                echo $this->generateMenu('left', $pages);
                ?>
            </nav>
        </div>
        <div class="main col_8 right">
            <?php echo $site->generateBlock('main'); ?>
        </div>
        <div class="side col_3 left">
            <aside>
                <?php echo $this->generateBlock('side', true); //TODOX: rename to side1 or anything by new definition ?>
            </aside>
        </div>
        <div class="clear"></div>
<?php echo $this->subview('_footer.php'); ?>
