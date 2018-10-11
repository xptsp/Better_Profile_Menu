<?php
/**********************************************************************************
* Subs-BetterProfile.php - Subs of the Lazy Admin Menu mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

/**********************************************************************************
* Better Profile Menu hook
**********************************************************************************/
function BetterProfile_Verify_User()
{
	if (isset($_GET['action']) && $_GET['action'] == 'profile' && isset($_GET['area']) && $_GET['area'] == 'betterprofile_ucp')
		return isset($_GET['u']) ? (int) $_GET['u'] : 0;
}

function BetterProfile_Load_Theme()
{
	// This admin hook must be last hook executed!
	add_integration_function('integrate_profile_areas', 'BetterProfile_Profile_Hook', false);
	add_integration_function('integrate_menu_buttons', 'BetterProfile_Menu_Buttons', false);
}

function BetterProfile_Profile_Hook(&$profile_areas)
{
	global $user_info, $scripturl, $txt;

	// Skip this if we are not requesting the layout of the moderator CPL:
	if (empty($user_info['id']) || !isset($_GET['area']) || !isset($_GET['u']) || $_GET['area'] != 'betterprofile_ucp')
		return;

	// Rebuild the Profile menu:
	$cached = array();
	foreach ($profile_areas as $id1 => $area1)
	{
		// Build first level menu:
		$cached[$id1] = array(
			'title' => $area1['title'],
			'show' => true,
			'sub_buttons' => array(),
		);
		$first = $last = false;
		if (isset($area1['custom_url']) && !empty($area1['custom_url']))
			$first = $cached[$id1]['href'] = $area1['custom_url'];
			
		// Build second level menus:
		foreach ($area1['areas'] as $id2 => $area2)
		{
			if (empty($area2['label']))
				continue;
			if (!$first)
				$first = $cached[$id1]['href'] = $scripturl . '?action=Profile;area=' . $id2;

			// Add the entry into the custom menu we're building:
			$link = isset($area2['custom_url']) ? $area2['custom_url'] : $scripturl . '?action=Profile;area=' . $id2;
			$show = (!isset($area2['enabled']) || $area2['enabled']) && !empty($area2['permission']['own']) && allowedTo($area2['permission']['own']);
			$cached[$id1]['sub_buttons'][$last = $id2] = array(
				'title' => $area2['label'],
				'href' => $link,
				'show' => $show,
			);

			// Let's add the "Show Posts" area to the menu under "Show Topics":
			if ($id2 == 'showposts')
				$cached[$id1]['sub_buttons'][$last = 'showtopics'] = array(
					'title' => $area2['label'] . ': ' . $txt['topics'],
					'href' => $link . ';sa=topics',
					'show' => $show,
				);
		}
		$cached[$id1]['sub_buttons'][$last]['is_last'] = true;
	}

	// Cache the menu we just built for the calling user:
	cache_put_data('betterprofile_' . $user_info['id'], $cached, 86400);
	exit;
}

function BetterProfile_Menu_Buttons(&$areas)
{
	global $txt, $scripturl, $user_info, $sourcedir;

	// Gotta prevent an infinite loop here:
	if (isset($_GET['action']) && $_GET['action'] == 'profile' && isset($_GET['area']) && $_GET['area'] == 'betterprofile_ucp')
		return;

	// Are you a guest, or can't see the Profile menu for some reason?  Then why bother with it....
	if (empty($user_info['id']) || empty($areas['profile']['show']))
		return;

	// Attempt to get the cached Profile menu:
	$Profile = &$areas['profile'];
	if (($cached = cache_get_data('betterprofile_' . $user_info['id'], 86400)) == null)
	{
		// Force the profile code to build our new Profile menu:
		@file_get_contents($scripturl . '?action=Profile;area=betterprofile_ucp;u=' . $user_info['id']);
		$cached = cache_get_data('betterprofile_' . $user_info['id'], 86400);
	}
	if (is_array($cached))
		$Profile['sub_buttons'] = $cached;

	// Define the rest of the Profile menu:
	if (file_exists($sourcedir . '/Bookmarks.php'))
	{
		unset($areas['bookmarks']);
		$areas['profile']['sub_buttons']['bookmarks'] = array(
			'title' => $txt['bookmarks'],
			'href' => $scripturl . '?action=bookmarks',
			'show' => allowedTo('make_bookmarks'),
		);
	}
}

function BetterProfile_CoreFeatures(&$core_features)
{
	global $cachedir;
	if (isset($_POST['save']))
		array_map('unlink', glob($cachedir . '/data_*-SMF-betterprofile_*'));
}

?>