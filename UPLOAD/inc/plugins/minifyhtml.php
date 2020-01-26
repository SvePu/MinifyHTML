<?php

/*
MinifyHTML Plugin v 1.6 for MyBB
Copyright (C) 2015 - 2020 SvePu

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
        "name"          =>  $db->escape_string($lang->minifyhtml),
        "description"   =>  $db->escape_string($lang->minifyhtml_desc),
        "website"       =>  "https://github.com/SvePu/MinifyHTML",
        "author"        =>  "SvePu",
        "authorsite"    =>  "https://github.com/SvePu",
        "codename"      =>  "minifyhtml",
        "version"       =>  "1.6",
        "compatibility"     => "18*"
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
    $query_add = $db->simple_select("settinggroups", "COUNT(*) as disporder");
    $disporder = $db->fetch_field($query_add, "disporder");
    $minifyhtml_group = array(
        "name"          =>  "minifyhtml_settings",
        "title"         =>  $db->escape_string($lang->minifyhtml_settings_title),
        "description"   =>  $db->escape_string($lang->minifyhtml_settings_title_desc),
        "disporder"     =>  $disporder+1,
        "isdefault"     =>  0
    );
    $gid = $db->insert_query("settinggroups", $minifyhtml_group);

    $setting_array = array(
        'minifyhtml_enable' => array(
            'title' => $db->escape_string($lang->minifyhtml_enable_title),
            'description' => $db->escape_string($lang->minifyhtml_enable_title_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 1
        ),
        'minifyhtml_limit' => array(
            "title" => $db->escape_string($lang->minifyhtml_limit_title),
            "description" => $db->escape_string($lang->minifyhtml_limit_title_desc),
            'optionscode' => 'numeric',
            'value' => 700000,
            "disporder" => 2
        ),
        'minifyhtml_exclpage' => array(
            "title" => $db->escape_string($lang->minifyhtml_exclpage_title),
            "description" => $db->escape_string($lang->minifyhtml_exclpage_title_desc),
            'optionscode' => 'text',
            'value' => '',
            "disporder" => 3
        )
    );

    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid'] = (int)$gid;
        $db->insert_query('settings', $setting);
    }

    rebuild_settings();
}

function minifyhtml_deactivate()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='minifyhtml_settings'");
    $gid = $db->fetch_field($query, "gid");
    if(!$gid)
    {
        return;
    }
    $db->delete_query("settinggroups", "name='minifyhtml_settings'");
    $db->delete_query("settings", "gid=$gid");
    rebuild_settings();
}

function minifyhtml($page)
{
    global $mybb;
    if ($mybb->settings['minifyhtml_enable'] == 1)
    {
        if($mybb->settings['minifyhtml_limit'] <= 0)
        {
            $mybb->settings['minifyhtml_limit'] = 700000;
        }

        if(strlen($page) > $mybb->settings['minifyhtml_limit'] || strpos($_SERVER['REQUEST_URI'], 'popup=true') !== false)
        {
            return $page;
        }

        if(!empty($mybb->settings['minifyhtml_exclpage']))
        {
            if(strpos($mybb->settings['minifyhtml_exclpage'], THIS_SCRIPT) !== false)
            {
                return $page;
            }
        }

        $ignore_tags = array('textarea','pre','script');
        $ignore_regex = implode('|', $ignore_tags);
        $cleaned_page = preg_replace(array('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/','#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:' .$ignore_regex. ')\b))*+)(?:<(?>' .$ignore_regex. ')\b|\z))#'),array('',' '),$page);
        if (strlen($cleaned_page) <= 1)
        {
            return $page;
        }
        $valfix = $mybb->settings['tplhtmlcomments'] == "1" ? " -->" : ""; //Validation Fix
        return $cleaned_page.$valfix;
    }
}
