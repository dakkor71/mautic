<?php
$fields = $form->children;
?>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">
			<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel1.title'); ?>
		</h3>
	</div>
	<div class="panel-body">

		<div class="row">
			<ol>
				<li>
					<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel1.instruction1'); ?>
					:
					<strong>
						<?php 
						echo $view['router']->generate(
							'plugin.gotowebinar.route.public.oauthredirect',
							array(),
							Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
						);
						?>
					</strong>
				</li>
				<li>
					<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel1.instruction2'); ?>
				</li>
			</ol>
		</div>
	
		<div class="row">
			<div class="col-md-6">
				<?php echo $view['form']->row($fields['gotowebinar_consumer_key']); ?>
			</div>
			<div class="col-md-6 pt-lg">
				<div class='button_container'>
					<?php echo $view['form']->widget($fields['oauth_button']); ?>
					<span class="fa fa-spinner fa-spin hide"></span>
				</div>
			</div>
		</div>
		
	</div>
</div>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">
			<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel2.title'); ?>
		</h3>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6">
				<?php echo $view['form']->row($fields['gotowebinar_access_token']); ?>
			</div>
			<div class="col-md-6">
				<?php echo $view['form']->row($fields['gotowebinar_organizer_key']); ?>
			</div>
		</div>
		
		<div class="row">
			<div class="col-md-12">
				<div class='button_container'>
					<?php echo $view['form']->widget($fields['check_api_button']); ?>
					<span class="fa fa-spinner fa-spin hide"></span>
					
					<strong class='message success has-success hide'>
						<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel2.checkOK'); ?>
					</strong>
					
					<strong class='message failed has-error hide'>
						<?php echo $view['translator']->trans('plugin.gotowebinar.config.panel2.checkNOK'); ?>
					</strong>
				</div>
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
<script src="/plugins/GoToWebinarBundle/Assets/js/helpers.js"></script>
<script src="/plugins/GoToWebinarBundle/Assets/js/config.js"></script>
