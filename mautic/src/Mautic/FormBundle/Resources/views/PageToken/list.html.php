<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($tmpl == 'index') {
    $view->extend('MauticFormBundle:PageToken:index.html.php');
}
?>
<div class="bundle-list" id="form-page-tokens">
    <ul class="draggable scrollable">
        <?php
        if (count($items)):
        foreach ($items as $key => $item):?>
            <li class="bundle-list-item has-click-event" id="form-<?php echo $item->getId(); ?>">
                <div class="padding-sm">
                    <span class="list-item-publish-status">
                        <?php echo $view->render('MauticCoreBundle:Helper:publishstatus.html.php',array(
                            'item'          => $item,
                            'dateFormat'    => (!empty($dateFormat)) ? $dateFormat : 'F j, Y g:i a',
                            'model'         => 'form.form',
                            'disableToggle' => true
                        )); ?>

                    </span>
                    <span class="list-item-primary">
                        <?php echo $item->getName(); ?>
                    </span>
                    <span class="list-item-secondary list-item-indent" data-toggle="tooltip" data-placement="left"
                          title="<?php echo $item->getDescription(); ?>">
                        <?php echo $item->getDescription(true, 30); ?>
                    </span>
                    <input type="hidden" class="page-token" value="{form=<?php echo $item->getId(); ?>}" />
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php echo $view->render('MauticCoreBundle:Helper:pagination.html.php', array(
        "items"           => $items,
        "page"            => $page,
        "limit"           => $limit,
        "fixedLimit"      => true,
        "totalItems"      => $totalCount,
        "baseUrl"         => $view['router']->generate('mautic_formtoken_index'),
        "paginationClass" => "xs",
        'sessionVar'      => 'formtoken',
        'ignoreFormExit'  => true,
        'queryString'     => 'tmpl=list'
    )); ?>
    <?php endif; ?>

    <div class="footer-margin"></div>
</div>