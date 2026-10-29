<?php
/**
 * Ultimate Profile - hook handlers
 *
 * @package Ultimate Profile
 * @author Rumpa
 * @copyright 2026 Rumpa
 * @license BSD 3-Clause
 * @version 1.0
 */

if (!defined('SMF'))
	die('No direct access...');

class UltimateProfile
{
	const MAX_BUDDIES = 200;

	/**
	 * Social networks and the hostnames each link must point to.
	 */
	public static function socials()
	{
		return array(
			'twitter' => array('label' => 'up_twitter', 'domains' => array('twitter.com', 'x.com')),
			'facebook' => array('label' => 'up_facebook', 'domains' => array('facebook.com', 'fb.com', 'fb.me')),
			'instagram' => array('label' => 'up_instagram', 'domains' => array('instagram.com')),
			'youtube' => array('label' => 'up_youtube', 'domains' => array('youtube.com', 'youtu.be')),
			'github' => array('label' => 'up_github', 'domains' => array('github.com')),
		);
	}

	/**
	 * Single-line text fields and their maximum lengths.
	 */
	public static function textFields()
	{
		return array('tagline' => 100, 'mood' => 50, 'discord' => 64);
	}

	/**
	 * Available card styles.
	 */
	public static function styles()
	{
		return array('default', 'dark', 'gradient', 'minimal');
	}

	/**
	 * integrate_load_theme: add the profile card layer on the summary page.
	 */
	public static function load_theme()
	{
		global $context, $modSettings, $user_info, $smcFunc, $scripturl, $txt;

		if (empty($modSettings['up_enabled']))
			return;

		if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'profile')
			return;

		if (!empty($_REQUEST['area']) && $_REQUEST['area'] !== 'summary')
			return;

		$memID = (isset($_REQUEST['u']) && is_numeric($_REQUEST['u'])) ? (int) $_REQUEST['u'] : (int) $user_info['id'];
		if ($memID < 1 || !allowedTo('profile_view'))
			return;

		// The member has to exist.
		$request = $smcFunc['db_query']('', '
			SELECT id_member, posts, date_registered, id_group, buddy_list
			FROM {db_prefix}members
			WHERE id_member = {int:member}',
			array('member' => $memID)
		);
		$mem = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		if (empty($mem))
			return;

		loadLanguage('UltimateProfile');

		$is_own = $memID === (int) $user_info['id'];
		$can_private = $is_own || allowedTo('profile_extra_any');

		// Count a view once per visitor session.
		if (!empty($modSettings['up_count_views']) && !$is_own && empty($_SESSION['up_viewed'][$memID]))
		{
			self::ensureRow($memID);
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}ultimate_profile
				SET views = views + 1
				WHERE id_member = {int:member}',
				array('member' => $memID)
			);
			$_SESSION['up_viewed'][$memID] = true;
		}

		// Record the visitor once per visitor session, unless they opted out.
		if (!empty($modSettings['up_show_visitors']) && !$is_own && !$user_info['is_guest'] && empty($_SESSION['up_logged'][$memID]))
		{
			$mine = self::getRow((int) $user_info['id'], false);
			if (empty($mine['hide_visits']))
				$smcFunc['db_insert']('replace',
					'{db_prefix}ultimate_profile_visitors',
					array('id_member' => 'int', 'id_visitor' => 'int', 'visit_time' => 'int'),
					array($memID, (int) $user_info['id'], time()),
					array('id_member', 'id_visitor')
				);
			$_SESSION['up_logged'][$memID] = true;
		}

		$row = self::getRow($memID, false);

		$socials = array();
		foreach (self::socials() as $key => $info)
			if ($row[$key] !== '' && self::isHttpUrl($row[$key]))
				$socials[] = array('url' => $row[$key], 'label' => $txt[$info['label']]);

		$has_content = $row['tagline'] !== '' || $row['mood'] !== '' || $row['about'] !== '' || $row['cover_url'] !== '' || $row['discord'] !== '' || !empty($socials);

		if (!$has_content && !$is_own)
			return;

		// Automatic badges.
		$badges = array();
		if (!empty($modSettings['up_show_badges']))
			$badges = self::badges($mem, (int) $row['views']);

		// Recent visitors: owner and staff only.
		$visitors = array();
		if (!empty($modSettings['up_show_visitors']) && $can_private)
		{
			$request = $smcFunc['db_query']('', '
				SELECT v.id_visitor, v.visit_time, mem.real_name
				FROM {db_prefix}ultimate_profile_visitors AS v
					INNER JOIN {db_prefix}members AS mem ON (mem.id_member = v.id_visitor)
				WHERE v.id_member = {int:member}
				ORDER BY v.visit_time DESC
				LIMIT 10',
				array('member' => $memID)
			);
			while ($v = $smcFunc['db_fetch_assoc']($request))
				$visitors[] = array(
					'href' => $scripturl . '?action=profile;u=' . $v['id_visitor'],
					'name' => $v['real_name'],
					'time' => timeformat($v['visit_time']),
				);
			$smcFunc['db_free_result']($request);
		}

		// Buddies, honoring the owner's privacy choice.
		$buddies = array();
		$buddy_total = 0;
		$buddy_ids = self::parseIds($mem['buddy_list']);
		if (!empty($modSettings['enable_buddylist']) && !empty($modSettings['up_show_buddies']) && !empty($buddy_ids))
		{
			$priv = (int) $row['buddy_privacy'];
			$viewer_ok = $can_private || $priv === 0 || ($priv === 1 && !$user_info['is_guest'] && in_array((int) $user_info['id'], $buddy_ids));
			if ($viewer_ok)
			{
				$buddy_total = count($buddy_ids);
				foreach (self::buddyDetails(array_slice($buddy_ids, 0, 12), $memID) as $b)
					$buddies[] = array('href' => $scripturl . '?action=profile;u=' . $b['id'], 'name' => $b['name']);
			}
		}

		$can_toggle = !$is_own && !$user_info['is_guest'] && !empty($modSettings['enable_buddylist']) && allowedTo('profile_extra_own');
		$is_buddy = $can_toggle && !empty($user_info['buddies']) && in_array($memID, $user_info['buddies']);

		$context['up_card'] = array(
			'style' => in_array($row['card_style'], self::styles()) ? $row['card_style'] : 'default',
			'tagline' => $row['tagline'],
			'mood' => $row['mood'],
			'about_html' => $row['about'] !== '' ? parse_bbc($row['about']) : '',
			'cover' => ($row['cover_url'] !== '' && self::isHttpUrl($row['cover_url'])) ? $row['cover_url'] : '',
			'accent' => preg_match('~^#[0-9a-fA-F]{6}$~', $row['accent']) ? $row['accent'] : '',
			'socials' => $socials,
			'discord' => $row['discord'],
			'views' => (int) $row['views'],
			'show_views' => !empty($modSettings['up_count_views']),
			'has_content' => $has_content,
			'badges' => $badges,
			'visitors' => $visitors,
			'buddies' => $buddies,
			'buddy_total' => $buddy_total,
			'buddy_toggle' => $can_toggle ? array(
				'href' => $scripturl . '?action=buddy;u=' . $memID . ';' . $context['session_var'] . '=' . $context['session_id'],
				'label' => $is_buddy ? $txt['up_buddy_remove'] : $txt['up_buddy_add'],
			) : null,
			'can_edit' => ($is_own && allowedTo('profile_extra_own')) || allowedTo('profile_extra_any'),
			'edit_href' => $scripturl . '?action=profile;area=ultimateprofile;u=' . $memID,
		);

		loadTemplate('UltimateProfile');
		loadCSSFile('ultimateprofile.css', array('default_theme' => true, 'minimize' => true), 'up_css');
		$context['template_layers'][] = 'ultimate_profile';
	}

	/**
	 * integrate_profile_areas: add the edit page to Modify Profile.
	 */
	public static function profile_areas(&$profile_areas)
	{
		global $modSettings, $txt;

		if (empty($modSettings['up_enabled']))
			return;

		loadLanguage('UltimateProfile');

		$profile_areas['edit_profile']['areas']['ultimateprofile'] = array(
			'label' => $txt['up_menu'],
			'file' => 'UltimateProfile.php',
			'function' => 'UltimateProfile_Edit',
			'icon' => 'profile_hd.png',
			'permission' => array(
				'own' => array('profile_extra_own'),
				'any' => array('profile_extra_any'),
			),
		);
	}

	/**
	 * integrate_general_mod_settings: admin settings.
	 */
	public static function general_mod_settings(&$config_vars)
	{
		global $txt;

		loadLanguage('UltimateProfile');

		$config_vars[] = array('title', 'up_title');
		$config_vars[] = array('check', 'up_enabled');
		$config_vars[] = array('check', 'up_count_views');
		$config_vars[] = array('check', 'up_show_visitors');
		$config_vars[] = array('check', 'up_show_badges');
		$config_vars[] = array('check', 'up_show_buddies');
		$config_vars[] = array('int', 'up_max_about', 'min' => 100, 'max' => 5000, 'subtext' => $txt['up_max_about_sub']);
	}

	/**
	 * integrate_delete_members: remove data of deleted members.
	 */
	public static function delete_members(&$users)
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}ultimate_profile
			WHERE id_member IN ({array_int:users})',
			array('users' => $users)
		);
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}ultimate_profile_visitors
			WHERE id_member IN ({array_int:users})
				OR id_visitor IN ({array_int:users})',
			array('users' => $users)
		);
	}

	/**
	 * The edit page: shows and saves the form.
	 */
	public static function edit($memID)
	{
		global $context, $txt, $modSettings, $smcFunc;

		$memID = (int) $memID;

		loadLanguage('UltimateProfile');
		loadTemplate('UltimateProfile');
		loadCSSFile('ultimateprofile.css', array('default_theme' => true, 'minimize' => true), 'up_css');

		$max_about = !empty($modSettings['up_max_about']) ? max(100, min(5000, (int) $modSettings['up_max_about'])) : 1000;
		$buddies_on = !empty($modSettings['enable_buddylist']);

		$context['sub_template'] = 'ultimate_profile_edit';
		$context['page_title'] = $txt['up_edit_title'];
		$context['up_memid'] = $memID;
		$context['up_max_about'] = $max_about;
		$context['up_socials'] = self::socials();
		$context['up_styles'] = self::styles();
		$context['up_buddies_on'] = $buddies_on;
		$context['up_errors'] = array();
		$context['up_saved'] = isset($_GET['updated']);

		self::ensureRow($memID);
		$row = self::getRow($memID, false);
		$buddy_ids = $buddies_on ? self::getBuddyIds($memID) : array();

		if (isset($_POST['up_save']))
		{
			checkSession();
			validateToken('profile-ultimate', 'post');

			$new = array();
			$errors = array();

			foreach (self::textFields() as $key => $max)
				$new[$key] = self::cleanText($key, $max, false);

			$new['about'] = self::cleanText('about', $max_about, true);
			$new['cover_url'] = self::cleanUrl('cover_url', 'up_cover', array(), $errors);

			foreach (self::socials() as $key => $info)
				$new[$key] = self::cleanUrl($key, $info['label'], $info['domains'], $errors);

			$accent = isset($_POST['accent']) ? (string) $_POST['accent'] : '';
			$new['accent'] = (!empty($_POST['up_reset_accent']) || !preg_match('~^#[0-9a-fA-F]{6}$~', $accent)) ? '' : strtolower($accent);

			$style = isset($_POST['card_style']) ? (string) $_POST['card_style'] : 'default';
			$new['card_style'] = in_array($style, self::styles()) ? $style : 'default';

			$new['hide_visits'] = !empty($_POST['hide_visits']) ? 1 : 0;
			$new['buddy_privacy'] = isset($_POST['buddy_privacy']) ? max(0, min(2, (int) $_POST['buddy_privacy'])) : 0;

			// Buddy list control.
			$new_buddies = $buddy_ids;
			if ($buddies_on)
			{
				if (!empty($_POST['up_remove_all']))
					$new_buddies = array();
				elseif (!empty($_POST['remove_buddy']) && is_array($_POST['remove_buddy']))
					$new_buddies = array_values(array_diff($new_buddies, array_map('intval', $_POST['remove_buddy'])));

				$add = isset($_POST['add_buddy']) ? trim((string) $_POST['add_buddy']) : '';
				if ($add !== '')
				{
					$request = $smcFunc['db_query']('', '
						SELECT id_member
						FROM {db_prefix}members
						WHERE real_name = {string:name} OR member_name = {string:name}
						LIMIT 1',
						array('name' => $add)
					);
					$found = $smcFunc['db_fetch_assoc']($request);
					$smcFunc['db_free_result']($request);

					if (empty($found))
						$errors[] = sprintf($txt['up_err_buddy_notfound'], $add);
					elseif ((int) $found['id_member'] === $memID)
						$errors[] = $txt['up_err_buddy_self'];
					elseif (!in_array((int) $found['id_member'], $new_buddies))
					{
						if (count($new_buddies) >= self::MAX_BUDDIES)
							$errors[] = sprintf($txt['up_err_buddy_max'], self::MAX_BUDDIES);
						else
							$new_buddies[] = (int) $found['id_member'];
					}
				}
			}

			if (empty($errors))
			{
				$int_fields = array('hide_visits', 'buddy_privacy');
				$set = array();
				$params = array('id_member' => $memID);
				foreach ($new as $key => $value)
				{
					$set[] = $key . ' = {' . (in_array($key, $int_fields) ? 'int' : 'string') . ':' . $key . '}';
					$params[$key] = $value;
				}

				$smcFunc['db_query']('', '
					UPDATE {db_prefix}ultimate_profile
					SET ' . implode(', ', $set) . '
					WHERE id_member = {int:id_member}',
					$params
				);

				if ($buddies_on && $new_buddies !== $buddy_ids)
					updateMemberData($memID, array('buddy_list' => implode(',', $new_buddies)));

				redirectexit('action=profile;area=ultimateprofile;u=' . $memID . ';updated');
			}

			$context['up_errors'] = $errors;
			$context['up_saved'] = false;
			$row = array_merge($row, $new);
		}

		$context['up_buddies'] = $buddies_on ? self::buddyDetails(array_slice($buddy_ids, 0, self::MAX_BUDDIES), $memID) : array();

		createToken('profile-ultimate', 'post');
		$context['up_form'] = $row;
	}

	/**
	 * Automatic badges from member statistics.
	 */
	protected static function badges($mem, $views)
	{
		global $txt, $modSettings;

		$badges = array();

		if ((int) $mem['id_group'] === 1)
			$badges[] = $txt['up_badge_admin'];
		elseif ((int) $mem['id_group'] === 2)
			$badges[] = $txt['up_badge_mod'];

		$age = time() - (int) $mem['date_registered'];
		$year = 365 * 86400;
		if ($age >= 5 * $year)
			$badges[] = $txt['up_badge_5y'];
		elseif ($age >= 3 * $year)
			$badges[] = $txt['up_badge_3y'];
		elseif ($age >= $year)
			$badges[] = $txt['up_badge_1y'];
		elseif ($age < 30 * 86400)
			$badges[] = $txt['up_badge_new'];

		$posts = (int) $mem['posts'];
		if ($posts >= 5000)
			$badges[] = $txt['up_badge_p5000'];
		elseif ($posts >= 1000)
			$badges[] = $txt['up_badge_p1000'];
		elseif ($posts >= 100)
			$badges[] = $txt['up_badge_p100'];

		if (!empty($modSettings['up_count_views']) && $views >= 100)
			$badges[] = $txt['up_badge_popular'];

		return $badges;
	}

	/**
	 * Turn a comma separated id list into a clean array of ints.
	 */
	protected static function parseIds($list)
	{
		$ids = array();
		foreach (explode(',', (string) $list) as $id)
			if ((int) $id > 0)
				$ids[] = (int) $id;

		return array_values(array_unique($ids));
	}

	protected static function getBuddyIds($memID)
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT buddy_list
			FROM {db_prefix}members
			WHERE id_member = {int:member}',
			array('member' => (int) $memID)
		);
		$data = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);

		return empty($data) ? array() : self::parseIds($data['buddy_list']);
	}

	/**
	 * Names of the given buddies and whether each one also lists $memID (mutual).
	 */
	protected static function buddyDetails($ids, $memID)
	{
		global $smcFunc;

		if (empty($ids))
			return array();

		$details = array();
		$request = $smcFunc['db_query']('', '
			SELECT id_member, real_name, buddy_list
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:ids})
			ORDER BY real_name',
			array('ids' => $ids)
		);
		while ($b = $smcFunc['db_fetch_assoc']($request))
			$details[] = array(
				'id' => (int) $b['id_member'],
				'name' => $b['real_name'],
				'mutual' => in_array((int) $memID, self::parseIds($b['buddy_list'])),
			);
		$smcFunc['db_free_result']($request);

		return $details;
	}

	/**
	 * Make sure a row exists for the member.
	 */
	protected static function ensureRow($memID)
	{
		global $smcFunc;

		$smcFunc['db_insert']('ignore',
			'{db_prefix}ultimate_profile',
			array('id_member' => 'int', 'about' => 'string'),
			array((int) $memID, ''),
			array('id_member')
		);
	}

	/**
	 * Load a member's row, falling back to empty defaults.
	 */
	protected static function getRow($memID, $create)
	{
		global $smcFunc;

		if ($create)
			self::ensureRow($memID);

		$row = array(
			'tagline' => '', 'mood' => '', 'about' => '', 'cover_url' => '', 'accent' => '',
			'twitter' => '', 'facebook' => '', 'instagram' => '', 'youtube' => '', 'github' => '',
			'discord' => '', 'views' => 0, 'card_style' => 'default', 'buddy_privacy' => 0, 'hide_visits' => 0,
		);

		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}ultimate_profile
			WHERE id_member = {int:member}',
			array('member' => (int) $memID)
		);
		if ($data = $smcFunc['db_fetch_assoc']($request))
		{
			foreach ($row as $key => $default)
				if (isset($data[$key]))
					$row[$key] = $data[$key];
		}
		$smcFunc['db_free_result']($request);

		$row['about'] = (string) $row['about'];

		return $row;
	}

	protected static function isHttpUrl($escaped)
	{
		return (bool) preg_match('~^https?://~i', un_htmlspecialchars($escaped));
	}

	/**
	 * Clean a text field from $_POST. Returns an HTML-escaped string.
	 */
	protected static function cleanText($key, $max, $multiline)
	{
		global $smcFunc;

		$value = isset($_POST[$key]) ? un_htmlspecialchars((string) $_POST[$key]) : '';
		$value = preg_replace('~[\x00-\x08\x0B\x0C\x0E-\x1F]~', '', $value);

		if ($multiline)
			$value = str_replace("\r\n", "\n", str_replace("\r", "\n", $value));
		else
			$value = str_replace(array("\r", "\n"), ' ', $value);

		$value = trim($value);

		if ($smcFunc['strlen']($value) > $max)
			$value = $smcFunc['substr']($value, 0, $max);

		return $smcFunc['htmlspecialchars']($value, ENT_QUOTES);
	}

	/**
	 * Validate a URL field. Returns an HTML-escaped string, '' when invalid.
	 */
	protected static function cleanUrl($key, $label, $domains, &$errors)
	{
		global $smcFunc, $txt;

		$url = isset($_POST[$key]) ? trim(un_htmlspecialchars((string) $_POST[$key])) : '';
		if ($url === '')
			return '';

		$name = isset($txt[$label]) ? $txt[$label] : $key;

		if (strlen($url) > 255)
		{
			$errors[] = sprintf($txt['up_err_toolong'], $name);
			return '';
		}

		if (!preg_match('~^https?://~i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false)
		{
			$errors[] = sprintf($txt['up_err_url'], $name);
			return '';
		}

		if (!empty($domains))
		{
			$host = strtolower((string) parse_url($url, PHP_URL_HOST));
			$ok = false;
			foreach ($domains as $d)
				if ($host === $d || substr($host, -(strlen($d) + 1)) === '.' . $d)
					$ok = true;

			if (!$ok)
			{
				$errors[] = sprintf($txt['up_err_domain'], $name);
				return '';
			}
		}

		return $smcFunc['htmlspecialchars']($url, ENT_QUOTES);
	}
}

/**
 * Profile area callback (plain function so SMF can call it with $memID).
 */
function UltimateProfile_Edit($memID)
{
	UltimateProfile::edit($memID);
}
