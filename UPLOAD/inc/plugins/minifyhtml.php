<?php

/*
MinifyHTML Plugin for MyBB
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
	global $lang;
	$lang->load('config_minifyhtml');
	return array
	(
		"name"			=>	$lang->minifyhtml,
		"description"	=>	$lang->minifyhtml_desc,
		"website"		=>	"http://svepu.bplaced.net/minifyhtml-plugin-fuer-mybb/",
		"author"		=>	"SvePu",
		"authorsite"	=> 	"http://svepu.bplaced.net",
		"version"		=>	"1.0",
		"guid"			=>	"",
		"compatibility"	=>	"16*,18*"
	);
}

function minifyhtml_activate()
{
    global $db, $lang;
	$lang->load('config_minifyhtml');
	$query_add = $db->simple_select("settinggroups", "COUNT(*) as rows");
	$rows = $db->fetch_field($query_add, "rows");
    $minifyhtml_group = array(
		"name" 			=>	"minifyhtml_settings",
		"title" 		=>	$lang->minifyhtml_settings_title,
		"description" 	=>	$lang->minifyhtml_settings_title_desc,
		"disporder"		=> 	$rows+1,
		"isdefault" 	=>  0
	);
    $db->insert_query("settinggroups", $minifyhtml_group);
	$gid = $db->insert_id();
	
	$minifyhtml_1 = array(
        'sid'           => 'NULL',
        'name'			=> 'minifyhtml_enable',
        'title'			=> $lang->minifyhtml_enable_title,
        'description'  	=> $lang->minifyhtml_enable_title_desc,
        'optionscode'  	=> 'yesno',
        'value'        	=> '1',
        'disporder'		=> 1,
        "gid" 			=> (int)$gid
    );
	$db->insert_query('settings', $minifyhtml_1);
	
	
    $minifyhtml_2 = array(
		"name"			=> "minifyhtml_limit",
		"title"			=> $lang->minifyhtml_limit_title,
		"description" 	=> $lang->minifyhtml_limit_title_desc,
        'optionscode'  	=> 'text',
        'value'        	=> '700000',
		"disporder"		=> "2",
		"gid" 			=> (int)$gid
	);
	$db->insert_query("settings", $minifyhtml_2);
	rebuild_settings();
}

function minifyhtml_deactivate()
{
	global $db;	
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='minifyhtml_settings'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='minifyhtml_enable'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='minifyhtml_limit'");
	rebuild_settings();
}

function minifyhtml($page)
{
	global $mybb;
	if (empty($mybb->settings['minifyhtml_limit'])){$mybb->settings['minifyhtml_limit'] = 700000;}
	if ($mybb->settings['minifyhtml_enable'] == 1){
		if (strlen($page) > $mybb->settings['minifyhtml_limit']){return $page;}
		$ignore_tags = array('textarea','pre','script');
		$ignore_regex = implode('|', $ignore_tags);
		$cleaned_page = preg_replace(array('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/','#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' .$ignore_regex. ')\b))*+)(?:<(?>' .$ignore_regex. ')\b|\z))#'),array('',' '),$page);
		if ( strlen($cleaned_page) <= 1 ) {return $page;}
		return $cleaned_page;
	}
}

?>