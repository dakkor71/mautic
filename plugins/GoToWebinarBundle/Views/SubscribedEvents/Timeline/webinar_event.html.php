<?php
/**
 * @package     GoToWebinar
 * @copyright   2016 Webmecanik. All rights reserved.
 * @author      Webmecanik
 * @link        http://www.webmecanik.com/
 */

?>

<li class="wrapper webinar-event">
	<div class="figure"><span class="fa fa-video-camera"></span></div>
	<div class="panel">
	    <div class="panel-body">
	    	<h3>
	    		<?php echo $view->escape($event['eventLabel']); ?>
			</h3>
            <p class="mb-0">
				<?php 
				echo $view->escape($view['translator']->trans('mautic.core.timeline.event.time', array(
					'%date%' => $view['date']->toFullConcat($event['timestamp']), 
					'%event%' => $event['eventLabel']
				))); 
				?>
			</p>
	    </div>
		<div class="panel-footer">
			<p>
				<?php echo $view->escape($event['extra']['webinarSlug']) ?>
			</p>
		</div>
	</div>
</li>
