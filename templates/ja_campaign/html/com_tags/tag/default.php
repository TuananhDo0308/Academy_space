<?php

/**
T4 Overide
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

// Note that there are certain parts of this layout used only when there is exactly one tag.
$isSingleTag = count($this->item) === 1;
$htag        = $this->params->get('show_page_heading') ? 'h2' : 'h1';
?>

<div class="com-tags-tag tag-category <?php echo $this->pageclass_sfx; ?>">

  <?php if ($this->params->get('show_page_heading')) : ?>
    <h1>
      <?php echo $this->escape($this->params->get('page_heading')); ?>
    </h1>
  <?php endif; ?>

  <?php if ($this->params->get('show_tag_title', 1)) : ?>
    <<?php echo $htag; ?>>
      <?php echo HTMLHelper::_('content.prepare', $this->tags_title, '', 'com_tag.tag'); ?>
    </<?php echo $htag; ?>>
  <?php endif; ?>

  <?php // We only show a tag description if there is a single tag. 
  ?>
  <?php if (count($this->item) === 1 && ($this->params->get('tag_list_show_tag_image', 1) || $this->params->get('tag_list_show_tag_description', 1))) : ?>
    <div class="com-tags-tag__description category-desc">
      <?php $images = json_decode($this->item[0]->images); ?>
      <?php if ($this->params->get('tag_list_show_tag_image', 1) == 1 && !empty($images->image_fulltext)) : ?>
        <img src="<?php echo htmlspecialchars($images->image_fulltext, ENT_COMPAT, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($images->image_fulltext_alt); ?>">
      <?php endif; ?>
      <?php if ($this->params->get('tag_list_show_tag_description') == 1 && $this->item[0]->description) : ?>
        <?php echo HTMLHelper::_('content.prepare', $this->item[0]->description, '', 'com_tags.tag'); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php // If there are multiple tags and a description or image has been supplied use that. 
  ?>
  <?php if ($this->params->get('tag_list_show_tag_description', 1) || $this->params->get('show_description_image', 1)) : ?>
    <?php if ($this->params->get('show_description_image', 1) == 1 && $this->params->get('tag_list_image')) : ?>
      <?php $alt = empty($this->params->get('tag_list_image_alt')) && empty($this->params->get('tag_list_image_alt_empty'))
        ? ''
        : 'alt="' . htmlspecialchars($this->params->get('tag_list_image_alt'), ENT_COMPAT, 'UTF-8') . '"'; ?>
      <img src="<?php echo htmlspecialchars($this->params->get('tag_list_image'), ENT_QUOTES, 'UTF-8'); ?>" <?php echo $alt; ?>>
    <?php endif; ?>
    <?php if ($this->params->get('tag_list_description', '') > '') : ?>
      <?php echo HTMLHelper::_('content.prepare', $this->params->get('tag_list_description'), '', 'com_tags.tag'); ?>
    <?php endif; ?>
  <?php endif; ?>
  <?php echo $this->loadTemplate('items'); ?>

  <?php if (($this->params->def('show_pagination', 1) == 1 || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="com-tags-tag__pagination w-100">
      <?php if ($this->params->def('show_pagination_results', 1)) : ?>
        <p class="counter float-right">
          <?php echo $this->pagination->getPagesCounter(); ?>
        </p>
      <?php endif; ?>
      <?php echo $this->pagination->getPagesLinks(); ?>
    </div>
  <?php endif; ?>
</div>