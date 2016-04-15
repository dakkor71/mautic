<?php 
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */
 
$view->extend('MauticCoreBundle:Default:content.html.php');

$view['slots']->set('headerTitle', $view['translator']->trans('plugin.gotowebinar.menu.index'));

// Attention : définir cet appel depuis le controlleur également (via passthroughVars), 
// pour que la page fonctionne à la fois en synchrone qu'en ajax
$view['slots']->set('mauticContent', 'gtwPageInit');

echo $view['assets']->includeScript('plugins/GoToWebinarBundle/Assets/js/helpers.js');
echo $view['assets']->includeScript('plugins/GoToWebinarBundle/Assets/js/page.js');

?>

<div id="gotowebinar-page-wrapper" class="panel panel-default mnb-5 bdr-t-wdh-0">
	<div class="panel-body">
		
		<?php if ( !$isApiOk) { ?>
			
			<p>
				<?php echo $view['translator']->trans('plugin.gotowebinar.page.apiunavailable') ?>, 
				<a href="<?php echo $view['router']->generate('mautic_config_action', array('objectAction' => 'edit')) ?>">
					<strong>
						<?php echo $view['translator']->trans('plugin.gotowebinar.page.pleasecheckconfig') ?>.
					</strong>
				</a>
			</p>
			
		<?php } else { ?>
			
		
			<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered" id="leadTable">
					<thead>
						<tr>
							<th><?php echo $view['translator']->trans('plugin.gotowebinar.page.col.subject') ?></th>
							<th><?php echo $view['translator']->trans('plugin.gotowebinar.page.col.times') ?></th>
							<th><?php echo $view['translator']->trans('plugin.gotowebinar.page.col.numberofregistrants') ?></th>
							<th><?php echo $view['translator']->trans('plugin.gotowebinar.page.col.registrationurl') ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($webinars as $webinar) { ?>
						
							<tr data-webinarKey="<?php echo $webinar->webinarKey ?>">
								<td>
									<?php echo $webinar->subject ?>
									#<?php echo $webinar->webinarKey ?>
								</td>
								<td>
									<?php echo $webinar->timesTxt ?>
								</td>
								<td>
									<?php echo $webinar->numberOfRegistrants ?>
								</td>
								<td>
									<a href="<?php echo $webinar->registrationUrl ?>" target="_blank">
										<?php echo $webinar->registrationUrl ?>
									</a>
								</td>
								<td>
									<button class='sync btn btn-primary btn-xs'>Sync</button>
									<span class="fa fa-spinner fa-spin hide"></span>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
				
				<div class='sync-all-wrapper text-right'>
					<button class='btn btn-primary btn'>Sync all</button>
					<span class="fa fa-spinner fa-spin hide"></span>
				</div>
			</div>
			
		<?php } ?>

		<div class='syncreport'></div>
		<div class='syncerror'></div>
	
	</div>
	
</div>