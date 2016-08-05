<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend(":$template:base.html.php");
$parentVariant = $page->getVariantParent();
$title         = (!empty($parentVariant)) ? $parentVariant->getTitle() : $page->getTitle();
$view['slots']->set('public', (isset($public) && $public === true) ? true : false);
$view['slots']->set('pageTitle', $title);
?>

<?php if ($view['slots']->hasContent(array('top'))): ?>
<div id="header">
    <div class="container">
        <div class="row no_padding">
            <div class="col-xs-12 col-sm-12"><?php $view['slots']->output('top'); ?></div>
        </div>
    </div>
</div>
<?php endif; // end of header check ?>

<?php if ($view['slots']->hasContent(array('slideshow', 'slideshow2', '2tiers1tier1-1', '2tiers1tier1-2', '2tiers1tier2-1', '2tiers1tier2-2', '2tiers1tier3-1', '2tiers1tier3-2', 'fullwidth1', 'fullwidth2', 'fullwidth3', 'gallerie1-title', 'gallerie1-1', 'gallerie1-2', 'gallerie1-3', 'gallerie1-4', 'gallerie1-5', 'gallerie1-6'))): ?>
<div id="content" class="container">

	<?php if ($view['slots']->hasContent(array('fullwidth1'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-12">
				<?php if ($view['slots']->hasContent('fullwidth1')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('fullwidth1'); ?>
					</div>
				</div>
				<?php endif; // end of fullwidth1 ?>
			</div>
		</div>
	<?php endif; ?>
	
	<?php if ($view['slots']->hasContent(array('slideshow', 'slideshow2'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-7">
				<?php if ($view['slots']->hasContent('slideshow')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('slideshow'); ?>
					</div>
				</div>
				<?php endif; // end of slideshow ?>
			</div>
			<div class="col-xs-12 col-sm-5">
				<?php if ($view['slots']->hasContent('slideshow2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('slideshow2'); ?>
					</div>
				</div>
				<?php endif; // end of slideshow2 ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($view['slots']->hasContent(array('2tiers1tier1-1', '2tiers1tier1-2'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-8">
				<?php if ($view['slots']->hasContent('2tiers1tier1-1')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier1-1'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier1-1 ?>
			</div>
			<div class="col-xs-12 col-sm-4 right_bloc">
				<?php if ($view['slots']->hasContent('2tiers1tier1-2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier1-2'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier1-2 ?>
			</div>
		</div>
	<?php endif; ?>
</div>
<div id="content2">
<div class="container">
	
	<?php if ($view['slots']->hasContent(array('fullwidth2'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-12">
				<?php if ($view['slots']->hasContent('fullwidth2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('fullwidth2'); ?>
					</div>
				</div>
				<?php endif; // end of fullwidth2 ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($view['slots']->hasContent(array('2tiers1tier2-1', '2tiers1tier2-2'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-8">
				<?php if ($view['slots']->hasContent('2tiers1tier2-1')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier2-1'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier2-1 ?>
			</div>
			<div class="col-xs-12 col-sm-4 right_bloc">
				<?php if ($view['slots']->hasContent('2tiers1tier2-2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier2-2'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier2-2 ?>
			</div>
		</div>
	<?php endif; ?>
	
</div>
</div>
<div id="content3" class="container">	
	
		<?php if ($view['slots']->hasContent(array('gallerie1-title', 'gallerie1-1', 'gallerie1-2', 'gallerie1-3', 'gallerie1-4', 'gallerie1-5', 'gallerie1-6'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-12">
				<?php if ($view['slots']->hasContent('gallerie1-title')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-title'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-title ?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-1')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-1'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-1 ?>
			</div>
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-2'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-2 ?>
			</div>
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-3')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-3'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-3 ?>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-4')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-4'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-4 ?>
			</div>
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-5')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-5'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-5 ?>
			</div>
			<div class="col-xs-12 col-sm-4">
				<?php if ($view['slots']->hasContent('gallerie1-6')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('gallerie1-6'); ?>
					</div>
				</div>
				<?php endif; // end of gallerie1-6 ?>
			</div>
		</div>
	<?php endif; ?>
	
	<?php if ($view['slots']->hasContent(array('fullwidth3'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-12">
				<?php if ($view['slots']->hasContent('fullwidth3')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('fullwidth3'); ?>
					</div>
				</div>
				<?php endif; // end of fullwidth3 ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($view['slots']->hasContent(array('2tiers1tier3-1', '2tiers1tier3-2'))): ?>
		<div class="row">
			<div class="col-xs-12 col-sm-8">
				<?php if ($view['slots']->hasContent('2tiers1tier3-1')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier3-1'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier3-1 ?>
			</div>
			<div class="col-xs-12 col-sm-4 right_bloc">
				<?php if ($view['slots']->hasContent('2tiers1tier3-2')): ?>
				<div class="row">
					<div class="col-xs-12">
						<?php $view['slots']->output('2tiers1tier3-2'); ?>
					</div>
				</div>
				<?php endif; // end of 2tiers1tier3-2 ?>
			</div>
		</div>
	<?php endif; ?>

</div>
<?php endif; // end of content check ?>

<?php if ($view['slots']->hasContent(array('bottom1', 'bottom2', 'bottom3', 'bottom4'))): ?>
<div id="footer">
    <div class="container">
        <div class="row">
            <?php if ($view['slots']->hasContent('bottom1')): ?>
            <div class="col-xs-12 col-sm-3 padding_top"><?php $view['slots']->output('bottom1'); ?></div>
            <?php endif; // end of bottom1 ?>
            <?php if ($view['slots']->hasContent('bottom2')): ?>
            <div class="col-xs-12 col-sm-3 padding_top"><?php $view['slots']->output('bottom2'); ?></div>
            <?php endif; // end of bottom2 ?>
            <?php if ($view['slots']->hasContent('bottom3')): ?>
            <div class="col-xs-12 col-sm-3 padding_top"><?php $view['slots']->output('bottom3'); ?></div>
            <?php endif; // end of bottom3 ?>
			 <?php if ($view['slots']->hasContent('bottom4')): ?>
            <div class="col-xs-12 col-sm-3 padding_top pull-right"><?php $view['slots']->output('bottom4'); ?></div>
            <?php endif; // end of bottom4 ?>
        </div>
    </div>
</div>
<?php endif; // end of footer check ?>

<?php if ($view['slots']->hasContent('footer')): ?>
<div id="copyright">
    <div class="container">
        <div class="row">
            <div class="col-xs-12"><?php $view['slots']->output('footer'); ?></div>
        </div>
    </div>
</div>
<?php endif; // end of footer ?>

<?php $view['slots']->output('builder'); ?>