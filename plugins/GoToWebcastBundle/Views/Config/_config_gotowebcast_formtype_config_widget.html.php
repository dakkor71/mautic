<?php
$fields = $form->children;
?>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">
			<?php echo $view['translator']->trans('plugin.gotowebcast.config.panel.title'); ?>
		</h3>
	</div>
	<div class="panel-body">

		<div class="row">
			<div class="col-md-12">
				<p>
					<?php echo $view['translator']->trans('plugin.gotowebcast.config.panel.instructions'); ?>
				</p>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6">
				<?php echo $view['form']->row($fields['gotowebcast_api_username']); ?>
			</div>
			<div class="col-md-6">
				<?php echo $view['form']->row($fields['gotowebcast_api_password']); ?>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<p>
					<?php echo $view['translator']->trans('plugin.gotowebcast.config.panel.instructions2'); ?>
				</p>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<div class='button_container'>
					<?php echo $view['form']->widget($fields['check_api_button']); ?>
					<span class="fa fa-spinner fa-spin hide"></span>

					<strong class='message success has-success hide'>
						<?php echo $view['translator']->trans('plugin.gotowebcast.config.checkOK'); ?>
					</strong>

					<strong class='message failed has-error hide'>
						<?php echo $view['translator']->trans('plugin.gotowebcast.config.checkNOK'); ?>
					</strong>
				</div>
			</div>
		</div>

	</div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">
			<?php echo $view['translator']->trans('plugin.gotowebcast.config.panel3.title'); ?>
		</h3>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<?php echo $view['form']->row($fields['gotowebcast_enable_plugin']); ?>
			</div>
		</div>
	</div>
</div>


<?php
/*
 * Inclusion des scripts à la main car includeScript() et $slot->set() ne fonctionnent pas pour la page de config
 * (mais fonctionnent pour les pages classiques)
 */
 ?>
<script src="/plugins/GoToWebcastBundle/Assets/js/helpers.js"></script>
<script src="/plugins/GoToWebcastBundle/Assets/js/config.js"></script>
