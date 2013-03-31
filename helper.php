<?php
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-02 Iskar Enev
* @copyright	2012 Iskar Enev
* @license		GPLv3 Open Source
* @since		File available since initial release
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE.'/components/com_content/helpers/route.php';

abstract class modArticlesFrontpageHelper {
	public static function getList(&$params) {

		$db = JFactory::getDBO();
		$user = JFactory::getUser();
		$user_id = (int) $user->get('id');

		$catid = $params->get('catid');
		$catid = (int)trim($catid[0]);
		$show_featured	= (int)$params->get('featured', 0);
		$count = (int)$params->get('count', 6);

		$acc_groups = $user->getAuthorisedViewLevels();
		$acc_groups = implode(',', $acc_groups);
		
		$contentConfig = JComponentHelper::getParams('com_content');
		$access = !(int)$contentConfig->get('show_noauth');

		$nullDate = '0000-00-00 00:00:00';
		$now = JHtml::_('date', 'now', 'Y-m-d H:i:s');

		$where = 'a.state = 1 
					AND ( a.publish_up = ' . $db->Quote($nullDate) . ' OR a.publish_up <= ' . $db->Quote($now) . ' )
					AND ( a.publish_down = ' . $db->Quote($nullDate) .' OR a.publish_down >= ' . $db->Quote($now) . ' )';

		// Ordering
		$ordering = $show_featured ? 'f.ordering ASC' : 'a.created DESC';
		$db->setDebug(0);
		// Content Items only
		$db->setQuery('SELECT a.*, 
							CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END as slug, 
							CASE WHEN CHAR_LENGTH(cat.alias) THEN CONCAT_WS(":", cat.id, cat.alias) ELSE cat.id END as catslug
						FROM #__content a
							LEFT JOIN #__content_frontpage AS f ON f.content_id = a.id
							INNER JOIN #__categories cat ON cat.id = a.catid 
						WHERE '. $where .' AND cat.id > 0 ' .
							($access ? ' AND a.access IN (' . $acc_groups . ') AND cat.access IN (' . $acc_groups . ')' : '') .
							($show_featured == '1' ? ' AND f.content_id IS NOT NULL ' : ' AND f.content_id IS NULL AND (cat.id = ' . $catid . ' OR cat.parent_id = ' . $catid . ')') . '
							AND cat.published = 1
						ORDER BY '. $ordering . '
						LIMIT ' . $count);
		$items = $db->loadObjectList();
		return $items;
	}
	
	public static function prepareContent($text, $length = 200) {
		// strips tags won't remove the actual jscript
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", "", $text );
		$text = preg_replace( '/{.+?}/', '', $text);
		// replace line breaking tags with whitespace
		$text = strip_tags(preg_replace( "'<(br[^/>]*?/|hr[^/>]*?/|/(div|h[1-6]|li|p|td))>'si", ' ', $text ));
		// cut off text at word boundary if required
		if (strlen($text) > $length) {
			$text = modArticlesFrontpageHelper::snippet($text, $length);
		} 
		return $text;
	}

	public static function image($name, $dims) {
		return JURI::root() . 'modules/mod_articles_frontpage/getimage.php?f=' . base64_encode($name . ':' . $dims);
	}

	public static function snippet($text, $length = 64, $tail = " [..]") {
		$text = trim($text);
		if (mb_strlen($text) > $length) {
			if (mb_strlen($text) > ($length * 2)) {
				$text = mb_substr($text, 0, ($length * 2));
			}
			for ($i = 0; preg_match('~\s+~', $text[$length + $i]); $i ++) {
				if (!$text[$length + $i]) {
					return $text;
				}
			}
			$text = mb_substr($text, 0, $length + $i) . $tail;
		}

		return $text;
	}
}