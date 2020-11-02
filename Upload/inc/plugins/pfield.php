<?php

 /*     This file is part of Profile Field Display

    Profile Field Display is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    PFD is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with PFD.  If not, see <http://www.gnu.org/licenses/>. */

if(!defined("IN_MYBB")) {
    die("Hacking Attempt.");
}

$plugins->add_hook("misc_start", "pfield_page_controller");

function pfield_info() {
	return array(
		'name'			=> 'Profile Field Display',
		'description'	=> 'Creates a page that displays a specific profile field for a group.',
		'website'		=> 'http://www.makestation.net',
		'author'		=> 'Darth Apple',
		'authorsite'	=> 'http://www.makestation.net',
		'codename' 		=> 'pfd',
		'version'		=> '1.0',
		"compatibility"	=> "18*"
	);
}

function pfield_is_installed() {
    global $mybb, $db;

    // This avoids a bug where the plugin doesn't recongize it's been uninstalled after redirect. 
    $query = $db->query("SELECT * FROM ".TABLE_PREFIX."settinggroups WHERE `name` = 'pfd';");
    if ($db->fetch_array($query)) {
        return true; 
    }
    return false; 
}

function pfield_activate () {
    return; // No template modifications. 
}

function pfield_install() {
    global $mybb, $db; 

    $templates = array();
    $templates['pfd_page'] = '
    <html>
    <head>
        <title>{$mybb->settings[\'bbname\']}</title>
        {$headerinclude}
    </head>
    <body>
        {$header}
		
        <table border="0" cellspacing="0" cellpadding="4" class="tborder">
            <tr>
                <td colspan="2" class="thead">
                        <strong>List of Users: </strong>
                </td>
            </tr>
			<tr>
				<td class="tcat" width=\'40%\'>Username</td>
				<td class="tcat" width=\'60%\'>{$pfd_field_name}</td>
			</tr>

       		{$pfd_table}
		</table>
        <br class="clear" />
        {$footer}
    </body>
</html>';		
            
    foreach($templates as $title => $template_new){
        $template = array('title' => $db->escape_string($title), 'template' => $db->escape_string($template_new), 'sid' => '-1', 'dateline' => TIME_NOW, 'version' => '1824');
        $db->insert_query('templates', $template);
    }

    $pfd_group = array (
        'name' => 'pfd', 
        'title' => $db->escape_string("Profile Field Display"),
        'description' => $db->escape_string("Creates a page and displays a specific profile field for a usergroup."),
        'disporder' => $rows+3,
        'isdefault' => 0
    ); 
    
    $group['gid'] = $db->insert_query("settinggroups", $pfd_group); // inserts new group for settings into the database. 
    
    $settings = array();
    $settings[] = array(
        'name' => 'pfd_usergroup',
        'title' => $db->escape_string("Usergroup selector: "),
        'description' => $db->escape_string("Enter the usergroup ID of the group you would like to display. Please enter only one. "),
        'optionscode' => 'numeric',
        'value' => '4',
        'disporder' => 1,
        'isdefault' => 0,
        'gid' => $group['gid']
    );	
    
    $settings[] = array(
        'name' => 'pfd_field',
        'title' => $db->escape_string("Field ID: "),
        'description' => $db->escape_string("Enter the profile field ID for the field you would like to display."),
        'optionscode' => 'numeric',
        'value' => '1',
        'disporder' => 2,
        'isdefault' => 0,
        'gid' => $group['gid']
    );				
    
    $settings[] = array(
        'name' => 'pfd_field_name',
        'title' => $db->escape_string("Field Header Text: "),
        'description' => $db->escape_string("What should the field header be named on the table? (Used for the tcat)"),
        'optionscode' => 'text',
        'value' => 'Field Data',
        'disporder' => 3,
        'isdefault' => 0,
        'gid' => $group['gid']
    );	
    // insert the settings
    foreach($settings as $array => $setting) {
        $db->insert_query("settings", $setting); // lots of queries
    }
    rebuild_settings();
}

function pfield_deactivate () {
    return; // No template modifications. 
}

function pfield_uninstall () {
    global $db, $mybb; 

    $templates = array('pfd_page'); // remove templates
    foreach($templates as $template) {
        $db->delete_query('templates', "title = '{$template}'");
    }
    
    $query = $db->simple_select('settinggroups', 'gid', 'name = "pfd"'); // remove settings
    $groupid = $db->fetch_field($query, 'gid');
    $db->delete_query('settings','gid = "'.$groupid.'"');
    $db->delete_query('settinggroups','gid = "'.$groupid.'"');
    rebuild_settings();
}

# Now we will build the page

function pfield_page_controller() {
    global $mybb; 
    if ($mybb->input['action'] == "field-display") {
        pfield_generate_page();
    }
}

function pfield_generate_page() { 
     global $mybb, $templates, $db, $header, $footer, $headerinclude; 

    $field_column = "fid" . (int) $mybb->settings['pfd_field'];
    $altbg = "trow1";
    $group =(int) $mybb->settings['pfd_usergroup'];

    // We need to take care of additional usergroups. 
    // This plugin was designed for smaller forums (<10,000 users). This may be slower on big boards. 

    $or_clause = "u.additionalgroups LIKE '%" . $group . ",%' ";
    $or_clause .= "OR u.additionalgroups LIKE '%," . $group . "' ";
    $or_clause .= "OR u.additionalgroups = '".$group."'";
    $or_clause .= "OR u.displaygroup = '".$group."'";

    $query = $db->query("SELECT * FROM ".TABLE_PREFIX."users u LEFT JOIN ".TABLE_PREFIX."userfields f ON f.ufid = u.uid WHERE u.usergroup = " . (int) $mybb->settings['pfd_usergroup'] . " OR ".$or_clause.";");
    while ($data = $db->fetch_array($query)) {
        $username = htmlspecialchars($data['username']);
        $field_value = htmlspecialchars($data[$field_column]);
        $pfd_field_name = htmlspecialchars($mybb->settings['pfd_field_name']);
        
        $pfd_table .= "<tr>";
        $pfd_table .= "<td class='$altbg'>$username</td>";
        $pfd_table .= "<td class='$altbg'>$field_value</td>";
        $pfd_table .= "</tr>";

        # MyBB probably has a build in class for this, but this was easy enough. 
        if ($altbg == "trow1") {
            $altbg = "trow2";
        } else {
            $altbg = "trow1";
        }
    }

    eval("\$pfield_output = \"".$templates->get("pfd_page")."\";");
    output_page($pfield_output);
}