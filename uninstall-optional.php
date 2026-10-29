<?php
/**
 * Ultimate Profile - remove all data
 *
 * @package Ultimate Profile
 * @author Rumpa
 * @copyright 2026 Rumpa
 * @license BSD 3-Clause
 */
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot uninstall - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if ((SMF == 'SSI') && !$user_info['is_admin'])
	die('Admin privileges required.');

global $smcFunc;
db_extend('packages');

$smcFunc['db_drop_table']('{db_prefix}ultimate_profile');
$smcFunc['db_drop_table']('{db_prefix}ultimate_profile_visitors');
$smcFunc['db_query']('', '
	DELETE FROM {db_prefix}settings
	WHERE variable IN ({array_string:vars})',
	array('vars' => array('up_enabled', 'up_count_views', 'up_max_about', 'up_show_visitors', 'up_show_badges', 'up_show_buddies'))
);
cache_put_data('modSettings', null, 90);

if (SMF == 'SSI')
	echo 'Ultimate Profile data removed.';
