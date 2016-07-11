<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// $pinned = ($app->getSession()->get("left-panel", 'default') == 'unpinned') ? ' unpinned' : '';
?>
<!-- start: sidebar-header -->
<div class="sidebar-header" style="background-color:#E40050"> <?php // version_atmt ?>
    <!-- brand -->
    <a class="mautic-brand" href="#" style="background-color:#E40050">  <?php // version_atmt ?>
        <!-- logo figure -->
		<img src="<?php echo $view['assets']->getUrl('media/images/automation_picto_mautic.png')?>" style="float:left;" />  <?php // version_atmt ?>
		<img src="<?php echo $view['assets']->getUrl('media/images/automation_logo_mautic.png')?>" style="float:left;"/>  <?php // version_atmt ?>
        <!--/ logo text -->
    </a>
    <!--/ brand -->
</div>
<!--/ end: sidebar-header -->

<!-- start: sidebar-content -->
<div class="sidebar-content">
    <!-- scroll-content -->
    <div class="scroll-content slimscroll">
        <!-- start: navigation -->
        <nav class="nav-sidebar">
            <?php echo $view['knp_menu']->render('main', array("menu" => "main")); ?>

            <!-- start: left nav -->
            <ul class="nav sidebar-left-dark">
                <li class="hidden-xs">
                    <a href="javascript:void(0)" data-toggle="minimize" class="sidebar-minimizer"><span class="direction icon pull-left fa"></span><span class="nav-item-name pull-left text"><?php echo $view['translator']->trans('mautic.core.menu.left.collapse'); ?></span></a>
                </li>
            </ul>
            <!--/ end: left nav -->

        </nav>
        <!--/ end: navigation -->
    </div>
    <!--/ scroll-content -->
</div>
<!--/ end: sidebar-content -->