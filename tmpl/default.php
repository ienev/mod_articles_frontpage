<?php
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-02 nuclear-head
* @copyright	2012 nuclear-head
* @license		GPLv3 Open Source
* @link			http://jvitals.com
* @since		File available since initial release
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

$featured = (int)$params->get('featured');
$divider = $featured ? 2 : 3;
$span = $featured ? 'span6' : 'span4';
$count_items = count($items);
$contentConfig = JComponentHelper::getParams('com_content');
?>

<?php if($count_items): ?>
<div class="row-fluid <?php echo $params->get('cssclass'); ?>">	
	<?php foreach($items as $i => $item): ?>
		<?php
			$images = json_decode($item->images);
			$item->thumb = strlen($images->image_intro) ? modArticlesFrontpageHelper::image($images->image_intro, ($featured ? '150x' : '75x')) : '';
			$item->link = JRoute::_(ContentHelperRoute::getArticleRoute($item->id, $item->catid));
			// $item->link = JRoute::_('index.php?option=com_content&view=article&id=' . $item->id . '&catid=' . $item->catid, false);
			$item->introtext = modArticlesFrontpageHelper::prepareContent($item->introtext, (int)$params->get('truncate', 200));
			$rowend = !(($i+1)%$divider);
			
			// Convert parameter fields to objects.
			$item->params = null;
			$registry = new JRegistry;
			$registry->loadString($item->attribs);
			$item->params = clone $contentConfig;
			$item->params->merge($registry);
			
			// get the author
			$author = '';
			$h4style = '';
			if ($item->params->get('show_author')) {
				$author = $item->created_by_alias;
			}
			if ($author) $h4style = 'style="margin: 2px 0;"';
		?>
		<div class="<?php echo $span; ?> item-article <?php if(!$rowend): ?>dotted<?php endif; ?>">
			<h4 <?php echo $h4style; ?>><a href="<?php echo $item->link; ?>"><?php echo htmlspecialchars($item->title); ?></a></h4>
			<?php if ($author) :?><p style="font-size: 12px;"><em><?php echo ($author); ?></em></p><?php endif; ?>
			<?php if ($item->thumb) :?><img style="float:left; padding: 0 7px 0 0;" src="<?php echo $item->thumb; ?>" alt="<?php echo $item->title; ?>" /><?php endif; ?>
			<?php if ($item->introtext) :?><p><?php echo ($item->introtext); ?></p><?php endif; ?>
		</div>
		<?php if($rowend && ($i < $count_items-1)): ?>
		</div>
		<div class="row-fluid dotted-separator">&nbsp;</div>
		<div class="row-fluid <?php echo $params->get('cssclass'); ?>">
		<?php endif; ?>
	<?php endforeach; ?>
</div>
<?php if($featured): ?><div class="row-fluid dotted-separator">&nbsp;</div><?php endif; ?>
<?php endif; ?>