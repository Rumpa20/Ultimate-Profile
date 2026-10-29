<?php
/**
 * Ultimate Profile - database install
 *
 * @package Ultimate Profile
 * @author Rumpa
 * @copyright 2026 Rumpa
 * @license BSD 3-Clause
 */
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if ((SMF == 'SSI') && !$user_info['is_admin'])
	die('Admin privileges required.');

global $smcFunc, $modSettings;
db_extend('packages');

$columns = array(
	array('name' => 'id_member', 'type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'null' => false),
	array('name' => 'tagline', 'type' => 'varchar', 'size' => 100, 'default' => '', 'null' => false),
	array('name' => 'mood', 'type' => 'varchar', 'size' => 50, 'default' => '', 'null' => false),
	array('name' => 'about', 'type' => 'text', 'null' => true),
	array('name' => 'cover_url', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'accent', 'type' => 'varchar', 'size' => 7, 'default' => '', 'null' => false),
	array('name' => 'twitter', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'facebook', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'instagram', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'youtube', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'github', 'type' => 'varchar', 'size' => 255, 'default' => '', 'null' => false),
	array('name' => 'discord', 'type' => 'varchar', 'size' => 64, 'default' => '', 'null' => false),
	array('name' => 'views', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0, 'null' => false),
	array('name' => 'card_style', 'type' => 'varchar', 'size' => 16, 'default' => 'default', 'null' => false),
	array('name' => 'buddy_privacy', 'type' => 'tinyint', 'size' => 3, 'unsigned' => true, 'default' => 0, 'null' => false),
	array('name' => 'hide_visits', 'type' => 'tinyint', 'size' => 3, 'unsigned' => true, 'default' => 0, 'null' => false),
);

$indexes = array(
	array('type' => 'primary', 'columns' => array('id_member')),
);

$smcFunc['db_create_table']('{db_prefix}ultimate_profile', $columns, $indexes, array(), 'ignore');

// Tables created by an earlier copy of this package get the newer columns too.
foreach (array('card_style', 'buddy_privacy', 'hide_visits') as $new_col)
	foreach ($columns as $col)
		if ($col['name'] === $new_col)
			$smcFunc['db_add_column']('{db_prefix}ultimate_profile', $col, array(), 'ignore');

$smcFunc['db_create_table']('{db_prefix}ultimate_profile_visitors', array(
	array('name' => 'id_member', 'type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'null' => false),
	array('name' => 'id_visitor', 'type' => 'mediumint', 'size' => 8, 'unsigned' => true, 'null' => false),
	array('name' => 'visit_time', 'type' => 'int', 'size' => 10, 'unsigned' => true, 'default' => 0, 'null' => false),
), array(
	array('type' => 'primary', 'columns' => array('id_member', 'id_visitor')),
), array(), 'ignore');

$defaults = array('up_enabled' => 1, 'up_count_views' => 1, 'up_max_about' => 1000, 'up_show_visitors' => 1, 'up_show_badges' => 1, 'up_show_buddies' => 1);
$new = array();
foreach ($defaults as $k => $v)
	if (!isset($modSettings[$k]))
		$new[$k] = $v;
if (!empty($new))
	updateSettings($new);

if (SMF == 'SSI')
	echo 'Database changes are complete!';
