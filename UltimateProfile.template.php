<?php
/**
 * Ultimate Profile - templates
 *
 * @package Ultimate Profile
 * @author Rumpa
 * @copyright 2026 Rumpa
 * @license BSD 3-Clause
 */

/**
 * Template layer: profile card shown above the profile summary.
 */
function template_ultimate_profile_above()
{
	global $context, $txt;

	if (empty($context['up_card']))
		return;

	$c = $context['up_card'];

	echo '
		<div class="up_card up_style_', $c['style'], '"', $c['accent'] != '' ? ' style="--up-accent: ' . $c['accent'] . ';"' : '', '>';

	if ($c['cover'] != '')
		echo '
			<div class="up_cover"><img src="', $c['cover'], '" alt="" loading="lazy"></div>';

	echo '
			<div class="up_body windowbg">';

	if (!$c['has_content'])
		echo '
				<p>', $txt['up_card_empty'], '</p>';

	if ($c['tagline'] != '')
		echo '
				<p class="up_tagline">', $c['tagline'], '</p>';

	if ($c['mood'] != '')
		echo '
				<p class="up_mood">', $txt['up_card_mood'], ': ', $c['mood'], '</p>';

	if ($c['about_html'] != '')
		echo '
				<div class="up_about">', $c['about_html'], '</div>';

	if (!empty($c['socials']) || $c['discord'] != '')
	{
		echo '
				<ul class="up_links">';

		foreach ($c['socials'] as $s)
			echo '
					<li><a href="', $s['url'], '" target="_blank" rel="nofollow noopener noreferrer ugc">', $s['label'], '</a></li>';

		if ($c['discord'] != '')
			echo '
					<li><span class="up_discord">', $txt['up_discord'], ': ', $c['discord'], '</span></li>';

		echo '
				</ul>';
	}

	if (!empty($c['badges']))
	{
		echo '
				<div class="up_badges"><strong>', $txt['up_card_badges'], '</strong><ul>';
		foreach ($c['badges'] as $badge)
			echo '<li>', $badge, '</li>';
		echo '</ul></div>';
	}

	if (!empty($c['buddies']))
	{
		echo '
				<div class="up_buddies"><strong>', $txt['up_card_buddies'], ' (', comma_format($c['buddy_total']), ')</strong><ul>';
		foreach ($c['buddies'] as $b)
			echo '<li><a href="', $b['href'], '">', $b['name'], '</a></li>';
		echo '</ul></div>';
	}

	if (!empty($c['visitors']))
	{
		echo '
				<div class="up_visitors"><strong>', $txt['up_card_visitors'], '</strong><ul>';
		foreach ($c['visitors'] as $v)
			echo '<li><a href="', $v['href'], '">', $v['name'], '</a> <span class="smalltext">(', $v['time'], ')</span></li>';
		echo '</ul></div>';
	}

	echo '
				<div class="up_meta">
					<span>', $c['show_views'] ? $txt['up_card_views'] . ': ' . comma_format($c['views']) : '', '</span>';

	if (!empty($c['buddy_toggle']))
		echo '
					<a href="', $c['buddy_toggle']['href'], '">', $c['buddy_toggle']['label'], '</a>';

	if ($c['can_edit'])
		echo '
					<a href="', $c['edit_href'], '">', $txt['up_card_edit'], '</a>';

	echo '
				</div>
			</div>
		</div>';
}

/**
 * Template layer: nothing below the content.
 */
function template_ultimate_profile_below()
{
}

/**
 * Edit page under Profile > Modify Profile.
 */
function template_ultimate_profile_edit()
{
	global $context, $txt, $scripturl;

	$f = $context['up_form'];

	echo '
		<div class="cat_bar">
			<h3 class="catbg"><span class="main_icons profile_hd icon"></span> ', $txt['up_edit_title'], '</h3>
		</div>
		<p class="information">', $txt['up_edit_desc'], '</p>';

	if (!empty($context['up_errors']))
	{
		echo '
		<div class="errorbox"><ul>';
		foreach ($context['up_errors'] as $error)
			echo '<li>', $error, '</li>';
		echo '</ul></div>';
	}
	elseif ($context['up_saved'])
		echo '
		<div class="infobox">', $txt['up_saved'], '</div>';

	echo '
		<form action="', $scripturl, '?action=profile;area=ultimateprofile;u=', $context['up_memid'], '" method="post" accept-charset="', $context['character_set'], '" class="up_form">
			<div class="windowbg">
				<dl class="settings">
					<dt><label for="up_tagline">', $txt['up_tagline'], '</label></dt>
					<dd><input type="text" id="up_tagline" name="tagline" value="', $f['tagline'], '" maxlength="100"></dd>

					<dt><label for="up_mood">', $txt['up_mood'], '</label></dt>
					<dd><input type="text" id="up_mood" name="mood" value="', $f['mood'], '" maxlength="50"></dd>

					<dt><label for="up_about">', $txt['up_about'], '</label><br><span class="smalltext">', sprintf($txt['up_about_sub'], $context['up_max_about']), '</span></dt>
					<dd><textarea id="up_about" name="about" rows="6" cols="60">', $f['about'], '</textarea></dd>

					<dt><label for="up_cover">', $txt['up_cover'], '</label><br><span class="smalltext">', $txt['up_cover_sub'], '</span></dt>
					<dd><input type="text" id="up_cover" name="cover_url" value="', $f['cover_url'], '" maxlength="255"></dd>

					<dt><label for="up_accent">', $txt['up_accent'], '</label></dt>
					<dd>
						<input type="color" id="up_accent" name="accent" value="', $f['accent'] != '' ? $f['accent'] : '#5a7fb0', '">
						<label><input type="checkbox" name="up_reset_accent" value="1"', $f['accent'] == '' ? ' checked' : '', '> ', $txt['up_accent_reset'], '</label>
					</dd>

					<dt><label for="up_style">', $txt['up_style'], '</label></dt>
					<dd><select id="up_style" name="card_style">';

	foreach ($context['up_styles'] as $style)
		echo '
						<option value="', $style, '"', $f['card_style'] == $style ? ' selected' : '', '>', $txt['up_style_' . $style], '</option>';

	echo '
					</select></dd>
				</dl>
				<hr>
				<strong>', $txt['up_social_links'], '</strong> <span class="smalltext">', $txt['up_social_sub'], '</span>
				<dl class="settings">';

	foreach ($context['up_socials'] as $key => $info)
		echo '
					<dt><label for="up_', $key, '">', $txt[$info['label']], '</label></dt>
					<dd><input type="text" id="up_', $key, '" name="', $key, '" value="', $f[$key], '" maxlength="255"></dd>';

	echo '
					<dt><label for="up_discord">', $txt['up_discord'], '</label></dt>
					<dd><input type="text" id="up_discord" name="discord" value="', $f['discord'], '" maxlength="64"></dd>
				</dl>
				<hr>
				<strong>', $txt['up_privacy'], '</strong>
				<dl class="settings">
					<dt>', $txt['up_hide_visits'], '</dt>
					<dd><input type="checkbox" name="hide_visits" value="1"', !empty($f['hide_visits']) ? ' checked' : '', '></dd>';

	if ($context['up_buddies_on'])
	{
		echo '
					<dt><label for="up_bpriv">', $txt['up_buddy_privacy'], '</label></dt>
					<dd><select id="up_bpriv" name="buddy_privacy">';

		for ($i = 0; $i <= 2; $i++)
			echo '
						<option value="', $i, '"', (int) $f['buddy_privacy'] === $i ? ' selected' : '', '>', $txt['up_buddy_priv_' . $i], '</option>';

		echo '
					</select></dd>';
	}

	echo '
				</dl>';

	if ($context['up_buddies_on'])
	{
		echo '
				<hr>
				<strong>', $txt['up_buddies'], '</strong>
				<dl class="settings">
					<dt><label for="up_addbuddy">', $txt['up_buddies_add'], '</label></dt>
					<dd><input type="text" id="up_addbuddy" name="add_buddy" value="" maxlength="80"></dd>
				</dl>';

		if (empty($context['up_buddies']))
			echo '
				<p>', $txt['up_buddies_none'], '</p>';
		else
		{
			echo '
				<table class="up_buddy_table">';
			foreach ($context['up_buddies'] as $b)
				echo '
					<tr>
						<td><label><input type="checkbox" name="remove_buddy[]" value="', $b['id'], '"> ', $txt['up_buddies_remove'], '</label></td>
						<td><a href="', $scripturl, '?action=profile;u=', $b['id'], '">', $b['name'], '</a>', $b['mutual'] ? ' <span class="smalltext">(' . $txt['up_buddies_mutual'] . ')</span>' : '', '</td>
					</tr>';
			echo '
				</table>
				<label><input type="checkbox" name="up_remove_all" value="1"> ', $txt['up_buddies_remove_all'], '</label>';
		}
	}
	else
		echo '
				<p class="smalltext">', $txt['up_buddies_disabled'], '</p>';

	echo '
				<div class="righttext">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">
					<input type="hidden" name="', $context['profile-ultimate_token_var'], '" value="', $context['profile-ultimate_token'], '">
					<input type="submit" name="up_save" value="', $txt['up_save'], '" class="button">
				</div>
			</div>
		</form>';
}
