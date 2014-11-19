<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$header = ($activeAsset->getId()) ?
    $view['translator']->trans('mautic.asset.asset.menu.edit',
        array('%name%' => $activeAsset->getTitle())) :
    $view['translator']->trans('mautic.asset.asset.menu.new');
$view['slots']->set("headerTitle", $header);
$view['slots']->set('mauticContent', 'asset');
?>
<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="pa-md">
	        <div class="row">
		        <div class="col-md-6">
				    <?php echo $view['form']->row($form['file']); ?>
		    	</div>
		    	<div class="col-md-6">
		    		<div class="row">
				    	<div class="form-group col-xs-12">
				    		<label class="control-label required" for="asset_file"><?php echo $view['translator']->trans('mautic.asset.asset.preview'); ?></label>
				    		<div class="text-center thumbnail-preview">
					    		<?php if ($activeAsset->isImage()) : ?>
					    			<img src="<?php echo $assetUrl; ?>" alt="<?php echo $activeAsset->getTitle(); ?>" class="img-thumbnail" />
					    		<?php elseif (strtolower($activeAsset->getFileType()) == 'pdf') : ?>
					    			<iframe src="<?php echo $assetUrl; ?>#view=FitH"></iframe>
					    		<?php else : ?>
					    			<i class="<?php echo $activeAsset->getIconClass(); ?> fa-5x"></i>
					    		<?php endif; ?>
				    		</div>
			    		</div>
		    		</div>
		    	</div>
		    </div>
		    <div class="row">
				<div class="col-md-6">
					<?php echo $view['form']->row($form['title']); ?>
				</div>
				<div class="col-md-6">
					<?php echo $view['form']->row($form['alias']); ?>
				</div>
			</div>
		</div>
	</div>
 	<div class="col-md-3 bg-white height-auto">
		<div class="pr-lg pl-lg pt-md pb-md">
			<?php
				echo $view['form']->row($form['category']);
				echo $view['form']->row($form['language']);
				echo $view['form']->row($form['isPublished']);
				echo $view['form']->row($form['publishUp']);
				echo $view['form']->row($form['publishDown']);
			?>
		</div>
	</div>
</div>
<?php echo $view['form']->end($form); ?>