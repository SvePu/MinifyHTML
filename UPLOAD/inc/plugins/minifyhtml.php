<?php

/*
MinifyHTML Plugin v 1.2 for MyBB
Copyright (C) 2014 SvePu

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("pre_output_page","minifyhtml");

function minifyhtml_info()
{
	global $plugins_cache, $mybb, $db, $lang;
	$lang->load('config_minifyhtml');
	$info = array
	(
		"name"			=>	$db->escape_string($lang->minifyhtml),
		"description"	=>	$db->escape_string($lang->minifyhtml_desc),
		"website"		=>	"https://github.com/SvePu/MinifyHTML",
		"author"		=>	"SvePu",
<<<<<<< HEAD
		"authorsite"	=> 	"https://github.com/SvePu",
		"codename"		=>	"minifyhtml",
=======
		"authorsite"	=> 	"http://svepu.bplaced.net",
		"codename"	=>	"minifyhtml",
>>>>>>> 0d76cbbf3d31c2f463316e746e7b34cc49404463
		"version"		=>	"1.2",
		"guid"			=>	"",
		"compatibility"	=>	"16*,18*"
	);
	
	$info_desc = '';
	$gid_result = $db->simple_select('settinggroups', 'gid', "name = 'minifyhtml_settings'", array('limit' => 1));
	$settings_group = $db->fetch_array($gid_result);
	if(!empty($settings_group['gid']))
	{
		$info_desc .= "<span style=\"font-size: 0.9em;\">(~<a href=\"index.php?module=config-settings&action=change&gid=".$settings_group['gid']."\"> ".$db->escape_string($lang->minifyhtml_settings_title)." </a>~)</span>";
	}
    
    if(is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['minifyhtml'])
    {
		$info_desc .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float: right;" target="_blank" />
<input type="hidden" name="cmd" value="_s-xclick" />
<input type="hidden" name="hosted_button_id" value="VGQ4ZDT8M7WS2" />
<input type="image" src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" />
<img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1" />
</form>';
	}
	
	if($info_desc != '')
	{
		$info['description'] = $info_desc.'<br />'.$info['description'];
	}
    
    return $info;
}

function minifyhtml_activate()
{
    global $db, $lang;
	$lang->load('config_minifyhtml');
	$query_add = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query_add, "rows");
    $minifyhtml_group = array(
		"name" 			=>	"minifyhtml_settings",
		"title" 		=>	$db->escape_string($lang->minifyhtml_settings_title),
		"description" 	=>	$db->escape_string($lang->minifyhtml_settings_title_desc),
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
    $db->insert_query("settinggroups", $minifyhtml_group);
	$gid = $db->insert_id();
	
	$minifyhtml_1 = array(
        'sid'           => 'NULL',
        'name'			=> 'minifyhtml_enable',
        'title'			=> $db->escape_string($lang->minifyhtml_enable_title),
        'description'  	=> $db->escape_string($lang->minifyhtml_enable_title_desc),
        'optionscode'  	=> 'yesno',
        'value'        	=> '1',
        'disporder'		=> 1,
        "gid" 			=> (int)$gid
    );
	$db->insert_query('settings', $minifyhtml_1);
	
	
    $minifyhtml_2 = array(
		"name"			=> "minifyhtml_limit",
		"title"			=> $db->escape_string($lang->minifyhtml_limit_title),
		"description" 	=> $db->escape_string($lang->minifyhtml_limit_title_desc),
        'optionscode'  	=> 'numeric',
        'value'        	=> '700000',
		"disporder"		=> "2",
		"gid" 			=> (int)$gid
	);
	$db->insert_query("settings", $minifyhtml_2);
	
	 $minifyhtml_3 = array(
		"name"			=> "minifyhtml_exclpage",
		"title"			=> $db->escape_string($lang->minifyhtml_exclpage_title),
		"description" 	=> $db->escape_string($lang->minifyhtml_exclpage_title_desc),
        'optionscode'  	=> 'text',
        'value'        	=> '',
		"disporder"		=> "3",
		"gid" 			=> (int)$gid
	);
	$db->insert_query("settings", $minifyhtml_3);
	rebuild_settings();
}

function minifyhtml_deactivate()
{
	global $mybb, $db;
	
	$result = $db->simple_select('settinggroups', 'gid', "name = 'minifyhtml_settings'", array('limit' => 1));
	$group = $db->fetch_array($result);
	
	if(!empty($group['gid']))
	{
		$db->delete_query('settinggroups', "gid='{$group['gid']}'");
		$db->delete_query('settings', "gid='{$group['gid']}'");
		rebuild_settings();
	}
}

function minifyhtml($page)
{
	global $mybb;
	if ($mybb->settings['minifyhtml_enable'] == 1){
		if ($mybb->settings['minifyhtml_limit'] <= 0){
			$mybb->settings['minifyhtml_limit'] = 700000;
		}
		if ((strlen($page) > $mybb->settings['minifyhtml_limit']) || (strpos($mybb->settings['minifyhtml_exclpage'], THIS_SCRIPT) !== false)){
			return $page;
		}
		$ignore_tags = array('textarea','pre','script');
		$ignore_regex = implode('|', $ignore_tags);
		$cleaned_page = preg_replace(array('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/','#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' .$ignore_regex. ')\b))*+)(?:<(?>' .$ignore_regex. ')\b|\z))#'),array('',' '),$page);
		if ( strlen($cleaned_page) <= 1 ) {
			return $page;
		}
		return $cleaned_page;
	}
}

?>
