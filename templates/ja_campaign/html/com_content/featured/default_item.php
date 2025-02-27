<?php
/**
T4 Overide
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\CMS\Workflow\Workflow;
use \T4\Helper\J3J4;

// Create a shortcut for params.
$params  = &$this->item->params;
$images  = json_decode($this->item->images);
$canEdit = $this->item->params->get('access-edit');
$info    = $this->item->params->get('info_block_position', 0);

// Check if associations are implemented. If they are, define the parameter.
$assocParam = (Associations::isEnabled() && $params->get('show_associations'));
$timePublishDown = $this->item->publish_down != null ? $this->item->publish_down : '';
$timePublishUp = $this->item->publish_up != null ? $this->item->publish_up : '';

if(version_compare(JVERSION, '4', 'ge')){
	$currentDate       = Factory::getDate()->format('Y-m-d H:i:s');
	$isExpired         = !is_null($this->item->publish_down) && $this->item->publish_down < $currentDate;
	$isNotPublishedYet = $this->item->publish_up > $currentDate;
	$isUnpublished     = $this->item->state == ContentComponent::CONDITION_UNPUBLISHED || $isNotPublishedYet || $isExpired;
}else{
	$isExpired         = (strtotime($this->item->publish_down) < strtotime(Factory::getDate())) && $this->item->publish_down != Factory::getDbo()->getNullDate();
	$isNotPublishedYet = strtotime($this->item->publish_up) > strtotime(Factory::getDate());
	$isUnpublished     = J3J4::checkUnpublishedContent($this->item);
}

?>

<?php if (isset($images->image_intro) && !empty($images->image_intro)) : ?>
	<?php echo LayoutHelper::render('joomla.content.intro_image', $this->item); ?>
<?php endif; ?>

<div class="item-content">
	<?php if ($this->item->state == 0 || strtotime($timePublishUp) > strtotime(Factory::getDate())
	|| ((strtotime($timePublishDown) < strtotime(Factory::getDate())) && !in_array($this->item->publish_down,array('',Factory::getDbo()->getNullDate())) )) : ?>
	<div class="system-unpublished">
	<?php endif; ?>

	<?php if ($params->get('show_title')) : ?>
		<div class="page-header">
			<h2 itemprop="headline">
			<?php if ($params->get('link_titles') && $params->get('access-view')) : ?>
				<a href="<?php echo Route::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language)); ?>" itemprop="url">
					<?php echo $this->escape($this->item->title); ?>
				</a>
			<?php else : ?>
				<?php echo $this->escape($this->item->title); ?>
			<?php endif; ?>
			</h2>
		</div>
	<?php endif; ?>

	<?php if ($isUnpublished) : ?>
		<span class="label label-warning"><?php echo Text::_('JUNPUBLISHED'); ?></span>
	<?php endif; ?>
	<?php if ($isNotPublishedYet) : ?>
		<span class="badge badge-warning"><?php echo Text::_('JNOTPUBLISHEDYET'); ?></span>
	<?php endif; ?>
	<?php if ($isExpired) : ?>
		<span class="badge badge-warning"><?php echo Text::_('JEXPIRED'); ?></span>
	<?php endif; ?>

	<div class="article-meta">

	<?php if ($canEdit || $params->get('show_print_icon') || $params->get('show_email_icon')) : ?>
		<?php echo LayoutHelper::render('joomla.content.icons', array('params' => $params, 'item' => $this->item, 'print' => false)); ?>
	<?php endif; ?>

	<?php // Content is generated by content plugin event "onContentAfterTitle" ?>
	<?php echo $this->item->event->afterDisplayTitle; ?>

	<?php // Todo Not that elegant would be nice to group the params ?>
	<?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
		|| $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author') || $assocParam); ?>
	</div>

	<?php if ($useDefList && ($info == 0 || $info == 2)) : ?>
		<?php echo LayoutHelper::render('joomla.content.info_block', array('item' => $this->item, 'params' => $params, 'position' => 'above')); ?>
		<?php if ($info == 0 && $params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
			<?php echo LayoutHelper::render('joomla.content.tags', $this->item->tags->itemTags); ?>
		<?php endif; ?>
	<?php endif; ?>

	<?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
	<?php echo $this->item->event->beforeDisplayContent; ?>

	<?php echo $this->item->introtext; ?>

	<?php if ($useDefList && ($info == 1 || $info == 2)) : ?>
		<?php echo LayoutHelper::render('joomla.content.info_block', array('item' => $this->item, 'params' => $params, 'position' => 'below')); ?>
		<?php if ($params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
			<?php echo LayoutHelper::render('joomla.content.tags', $this->item->tags->itemTags); ?>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($params->get('show_readmore') && $this->item->readmore) :
		if ($params->get('access-view')) :
			$link = Route::_(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language));
		else :
			$menu = Factory::getApplication()->getMenu();
			$active = $menu->getActive();
			$itemId = $active->id;
			$link = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false));
			$link->setVar('return', base64_encode(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language)));
		endif; ?>

		<?php echo LayoutHelper::render('joomla.content.readmore', array('item' => $this->item, 'params' => $params, 'link' => $link)); ?>

	<?php endif; ?>

  <?php if ($this->item->state == 0 || strtotime($timePublishUp) > strtotime(Factory::getDate())
	|| ((strtotime($timePublishDown) < strtotime(Factory::getDate())) && !in_array($this->item->publish_down,array('',Factory::getDbo()->getNullDate())) )) : ?>
	</div>
	<?php endif; ?>

</div>

<?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
<?php echo $this->item->event->afterDisplayContent; ?>
