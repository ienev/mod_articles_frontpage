<?php
/**
* Detailed copyright and licensing information can be found
* in the gpl-3.0.txt file which should be included in the distribution.
* 
* @version		1.0 2012-10-02 Iskar Enev
* @copyright	2016 Iskar Enev
* @license		GPLv3 Open Source
* @since		File available since initial release
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once __DIR__ . '/helper.php';
$items = modArticlesFrontpageHelper::getList($params);
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
require(JModuleHelper::getLayoutPath('mod_articles_frontpage'));