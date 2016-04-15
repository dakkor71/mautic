<div>

	<h3>
		<?php echo $view['translator']->trans('plugin.gotowebinar.page.syncreport') ?>
	</h3>

	<?php if (!empty($tagsAddedOrRemovedByLead)) { ?>			
		<ul class='mt-10'>
			<?php foreach($tagsAddedOrRemovedByLead as $email => $tagsAddedOrRemoved) { ?>			
				<li class='mt-10'>
					<strong><?php echo $email ?></strong>
					
					<?php if ( !empty($tagsAddedOrRemoved['toRemove'])) { ?>
						<?php foreach($tagsAddedOrRemoved['toRemove'] as $tag) { ?>
							<span class='label label-warning'>
								<?php echo $tag ?>
							</span>
						<?php } ?>
					<?php } ?>
					
					<?php if ( !empty($tagsAddedOrRemoved['toAdd'])) { ?>
						<?php foreach($tagsAddedOrRemoved['toAdd'] as $tag) { ?>
							<span class='label label-success'>
								<?php echo $tag ?>
							</span>
						<?php } ?>
					<?php } ?>
				</li>	
			<?php } ?>
		</ul>
	<?php } else { ?>
		<p class='mt-10'>
			<strong>
				<?php echo $view['translator']->trans('plugin.gotowebinar.page.uptodate') ?>
			</strong>
		</p>
	<?php } ?>
		
</div>