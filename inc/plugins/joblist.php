<?php

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
	die("Direct initialization of this file is not allowed.");
}

// ACP
$plugins->add_hook("admin_load", "joblist_manage_joblist");
$plugins->add_hook("admin_config_menu", "joblist_admin_config_menu");
$plugins->add_hook("admin_config_permissions", "joblist_admin_config_permissions");
$plugins->add_hook("admin_config_action_handler", "joblist_admin_config_action_handler");
$plugins->add_hook("admin_formcontainer_end", "joblist_usergroup_permission");
$plugins->add_hook("admin_user_groups_edit_commit", "joblist_usergroup_permission_commit");

// Misc
$plugins->add_hook('misc_start', 'joblist_misc');

// global
$plugins->add_hook("global_intermediate", "joblist_global");

// Modcp
$plugins->add_hook("modcp_nav", "joblist_modcp_nav");
$plugins->add_hook("modcp_start", "joblist_modcp");

// Alert
if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
	$plugins->add_hook("global_start", "joblist_alerts");
}

// Profile
$plugins->add_hook("member_profile_start", "joblist_profile");

function joblist_info()
{
	return array(
		"name" => "Joblist",
		"description" => "Übersicht aller Arbeitsstellen, bei denen die Charaktere einen Job ergreifen können.",
		"website" => "",
		"author" => "Ales",
		"authorsite" => "https://github.com/Ales12",
		"version" => "1.0",
		"guid" => "",
		"codename" => "",
		"compatibility" => "*"
	);
}

function joblist_install()
{
	global $db, $mybb, $templates, $cache;
	if ($db->engine == 'mysql' || $db->engine == 'mysqli') {
		$db->query("CREATE TABLE `" . TABLE_PREFIX . "joblist` (
          `jid` int(10) NOT NULL auto_increment,
		  `industry`  varchar(500) CHARACTER SET utf8 NOT NULL,
          `job` varchar(500) CHARACTER SET utf8 NOT NULL,
          `jobplace` varchar(500) CHARACTER SET utf8 NOT NULL,
          `jobdesc` text  CHARACTER SET utf8 NOT NULL,
		  `otherinfos`  varchar(500) CHARACTER SET utf8 NOT NULL,
    	  `ok` int(10) NOT NULL DEFAULT 0,
		   `uid` int(10) NOT NULL DEFAULT 0,
          PRIMARY KEY (`jid`)
        ) ENGINE=MyISAM" . $db->build_create_table_collation());

		$db->query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `jtitle` varchar(400) CHARACTER SET utf8 NOT NULL;");
		$db->query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `jid` int(10) NOT NULL;");
		// Nebenjob
		$db->query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `sjtitle` varchar(400) CHARACTER SET utf8 NOT NULL;");
		$db->query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `sjid` int(10) NOT NULL;");

		// Spalte bei Usertabelle hinzufügen
		$db->add_column("usergroups", "canaddjob", "tinyint NOT NULL default '1'");
		$db->add_column("usergroups", "canjoinjob", "tinyint NOT NULL default '1'");
		$cache->update_usergroups();
	}

	// Einstellungen
	$setting_group = array(
		'name' => 'joblist',
		'title' => 'Einstellungen für die Joblist',
		'description' => 'Hier kannst du alles für die Joblist einstellen',
		'disporder' => 5, // The order your setting group will display
		'isdefault' => 0
	);

	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(
		// A text setting
		'joblist_industries' => array(
			'title' => 'Branchen',
			'description' => 'In welcher Sparten von Branchen gibt es?:',
			'optionscode' => 'textarea',
			'value' => 'Agriculture, education system, services, retail, leisure & culture, gastronomy, health, crafts, hotel business, industry, law & order, media, nightlife, other, tourism', // Default
			'disporder' => 1
		),
		// A select box
		'joblist_userstyle' => array(
			'title' => 'Gruppenfarben',
			'description' => 'Sollen die Usernamen in Gruppenfarben dargestellt werden?',
			'optionscode' => 'yesno',
			'value' => 1,
			'disporder' => 2
		),
		'joblist_desc' => array(
			'title' => 'Infotext',
			'description' => 'Hier kannst du eine kleinen Infotext hinzufügen, welcher auf der Mainside angezeigt wird. BBCodes und HTML sind aktiv!',
			'optionscode' => 'textarea',
			'value' => 'Hier könnt ihr euren Charakter einen Job zuweisen oder eine neue Arbeitsstelle erstellen. Dein Charakter hat einen Zweitjob? Dann kannst du das natürlich auch hier auswählen. 
Neue Arbeitsstellen müssen erst vom Team abgesegnet werden.', // Default
			'disporder' => 3
		),
	);

	foreach ($setting_array as $name => $setting) {
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	// Templates
	$insert_array = array(
        'title' => 'joblist',
        'template' => $db->escape_string('<html>
<head>
<title>{$lang->joblist}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead"><strong>{$lang->joblist}</strong></td>
	</tr>
	<tr>
		<td class="trow1" valign="top" width="10%">
			<div class="joblist">
			<div class="tab">
			<button class="tablinks" onclick="openBranche(event, \'main\')" id="defaultOpen">{$lang->joblist_main}</button>
{$joblist_nav}
			</div>
<div id="main" class="tabcontent">
<div class="joblist_infotext">
{$infotext}	
	</div>
		{$add_job}
{$join_job}
</div>
				{$joblist_tabcontent}
			</div></td>
</tr>
</table>
{$footer}
</body>
</html>
<script>
function openBranche(evt, Branche) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(Branche).style.display = "block";
  evt.currentTarget.className += " active";
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'joblist_add_job',
        'template' => $db->escape_string('<form action="misc.php?action=joblist" method="post" id="add_job">
	<table width="80%" style="margin: 10px auto; text-align: center;"  cellpadding="5">
		<tr><td class="tcat" colspan="3"><strong>{$lang->joblist_add_job}</strong></td></tr>
		<tr>
			<td class="trow1" width="50%"><strong>{$lang->joblist_add_name}</strong>
			<div class="smalltext">{$lang->joblist_add_name_desc}</div>
			</td>
						<td class="trow1" width="50%"><strong>{$lang->joblist_add_industry}</strong>
			<div class="smalltext">{$lang->joblist_add_industry_desc}</div>
			</td>	<td class="trow1"><strong>{$lang->joblist_add_place}</strong>
			<div class="smalltext">{$lang->joblist_add_place_desc}</div>
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<input type="text" class="textbox" name="job" id="job" size="40" maxlength="1155" placeholder="Name der Arbeitsstelle" required>
			</td>
						<td class="trow2">
				<select name="industry">
					<option value="0">Branche auswählen</option>
				{$industry_option}
							</select>
			</td>		<td class="trow2">
				<input type="text" class="textbox" name="jobplace" id="jobplace" size="40" maxlength="1155" placeholder="Straßenname, Ortsteil etc." required>
			</td>
				<tr>		
		</tr>
						<tr>				<td class="trow1"><strong>{$lang->joblist_add_otherinfos}</strong>
			<div class="smalltext">{$lang->joblist_add_otherinfos_desc}</div>
			</td>
		
			<td class="trow1" colspan="2"><strong>{$lang->joblist_add_jobdesc}</strong>
			<div class="smalltext">{$lang->joblist_add_jobdesc_desc}</div>
			</td>
		</tr>
		<tr>			<td class="trow2">
				<textarea name="otherinfos" id="otherinfos" placeholder="Öffnungszeiten, Mitarbeiter gesucht. Kannst du auch leer lassen, wenn es keine gibt."style="width: 80%; height: 50px;"></textarea>
			</td>
<td class="trow2" colspan="2">
				<textarea name="jobdesc" id="jobdesc" placeholder="Beschreibe hier den Betrieb kurz. Was macht ihn aus, was tut er etc." style="width: 80%; height: 50px;" required></textarea>
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="3">
				<input type="submit" class="textbox" name="add_job" id="add_job" class="buttom" value="{$lang->joblist_add_send}">
			</td>
			</tr>
	</table>
</form>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
  
	$insert_array = array(
        'title' => 'joblist_bit',
        'template' => $db->escape_string('<div class="joblist_job">
	<div class="joblist_job_top">
		<strong>{$jobtitle}</strong> {$joblist_otherinfos}
		<div class="smalltext">{$place}</div>
		{$joblist_options}
	</div>
	<div class="joblist_job_desc">
		{$jobdesc}
	</div>
	<div class="joblist_job_staff_top tcat"><strong>{$lang->joblist_staff}</strong></div>
	<div class="joblist_job_staff">
		{$joblist_staff}
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_global',
        'template' => $db->escape_string('<div class="red_alert"><a href="modcp.php?action=joblist"><strong>{$globalalert}</strong></a></div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_join_job',
        'template' => $db->escape_string('<form action="misc.php?action=joblist" method="post" id="join_job">
	<table width="80%" style="margin: 20px auto; text-align: center;"  cellpadding="5">
		<tr><td class="tcat" colspan="4"><strong>{$lang->joblist_join_job}</strong></td></tr>
		<tr>
			<td class="trow1" width="25%"><strong>{$lang->joblist_join_jobtitle}</strong></td>
			<td class="trow1" width="25%"><strong>{$lang->joblist_join_where}</strong></td>		
			<td class="trow1" width="25%"><strong>{$lang->joblist_join_secondjob_title}</strong></td>
			<td class="trow1" width="25%"><strong>{$lang->joblist_join_where}</strong></td>
		</tr>
		<tr>
			<td class="trow2">
				<input type="text" class="textbox" name="jtitle" id="jtitle" size="40" maxlength="500" value="{$jtitle}" required>
			</td>
					<td class="trow2">
				<select name="jid">
					<option value="0">{$lang->joblist_join_nonjob}</option>
				{$job_option}
							</select>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="sjtitle" id="sjtitle" size="40" maxlength="500" value="{$sjtitle}">
			</td>
					<td class="trow2">
				<select name="sjid">
						<option value="0">{$lang->joblist_join_nonjob}</option>
				{$secondjob_option}
							</select>
			</td>
		</tr>
			<td class="trow1" colspan="4">
				<input type="submit" class="textbox" name="join_job" id="join_job" class="buttom" value="{$lang->joblist_join_send}">
			</td>
			</tr>
	</table>
</form>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_modcp',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->joblist_modcp}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
{$modcp_nav}
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" align="center"><strong>{$lang->joblist_modcp}</strong></td>
</tr>
<tr>
	<td class="trow1">
		{$joblist_modcp_bit}
	</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_modcp_bit',
        'template' => $db->escape_string('<table width="90%" style="margin: 20px  auto;" cellpadding="5">
	<tr><td class="thead" colspan="2" align="center">
		<strong>{$job}</strong>
		<div class="smalltext">{$owner}</div>
		</td>
	</tr>
	<tr><td class="tcat" colspan="2" align="center">{$industry} // {$jobplace}</td></tr>
	<tr>
		<td class="trow1" colspan="2">
			<div class="joblist_job_desc">
				{$jobdesc}
			</div>
		</td>
	</tr>
	<tr>
		<td class="trow2" align="center">
			<form action="modcp.php?action=joblist" method="post" id="denyjob">
				<input type="hidden" value="{$jid}" name="jid">
								<textarea name="denyreason" id="denyreason" placeholder="Schreibe hier rein, wieso du ablehnst. " style="width: 80%; height: 50px;"></textarea>
				<div><input type="submit" class="textbox" name="denyjob" id="denyjob" class="buttom" value="{$lang->joblist_modcp_deny}"></div>
			</form>
		</td>
				<td class="trow2" align="center">
			<form action="modcp.php?action=joblist" method="post" id="acceptjob">
				<input type="hidden" value="{$jid}" name="jid">
			<div>	<input type="submit" class="textbox" name="acceptjob" id="acceptjob" class="buttom" value="{$lang->joblist_modcp_accept}"></div>
			</form>
		</td>
</table>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_modcp_nav',
        'template' => $db->escape_string('<tr><td class="trow1 smalltext"><a href="modcp.php?action=joblist" class="modcp_nav_item modcp_nav_modlogs">{$lang->joblist_modcp_nav}</a></td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_nav',
        'template' => $db->escape_string('{$branche}'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_options',
        'template' => $db->escape_string('<div class="smalltext"><a onclick="$(\'#edit_{$jid}\').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== \'undefined\' ? modal_zindex : 9999) }); return false;" style="cursor: pointer;">{$lang->joblist_edit}</a>	// <a href="misc.php?action=joblist&deletejob={$jid}">{$lang->joblist_delete}</a>
</div>
	<div class="modal" id="edit_{$jid}" style="display: none;">

<form action="misc.php?action=joblist" method="post" id="edit_job">
	<input type="hidden" class="textbox" name="jid" id="jid"  value="{$jid}">
	<table w style="margin: 10px ; text-align: center;"  cellpadding="5">
		<tr><td class="thead" colspan="2"><strong>{$lang->joblist_edit_job}</strong></td></tr>
	<tr>
			<td class="trow1" width="50%"><strong>{$lang->joblist_add_name}</strong>
			<div class="smalltext">{$lang->joblist_add_name_desc}</div>
			</td>
			<td class="trow2" width="50%">
				<input type="text" class="textbox" name="job" id="job" size="40" maxlength="1155" value="{$jobtitle}">
			</td>
		</tr>
		<tr>
							<td class="trow1" width="50%"><strong>{$lang->joblist_add_industry}</strong>
			<div class="smalltext">{$lang->joblist_add_industry_desc}</div>
			</td>
							<td class="trow2">
				<select name="industry">
					<option value="0">Branche auswählen</option>
				{$industry_option}
							</select>
			</td>	
		</tr>
		<tr>
						<td class="trow1"><strong>{$lang->joblist_add_place}</strong>
			<div class="smalltext">{$lang->joblist_add_place_desc}</div>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="jobplace" id="jobplace" size="40" maxlength="1155" value="{$place}">
			</td>
			</tr>
		
						<tr>				<td class="trow1" colspan="2"><strong>{$lang->joblist_add_otherinfos}</strong>
			<div class="smalltext">{$lang->joblist_add_otherinfos_desc}</div>
			</td>
		</tr>
		<tr>			<td class="trow2" colspan="2">
				<textarea name="otherinfos" id="otherinfos" placeholder="Öffnungszeiten, Mitarbeiter gesucht. Kannst du auch leer lassen, wenn es keine gibt."style="width: 90%; height: 50px;">{$row[\'otherinfos\']}</textarea>
			</td></tr>
		<tr>	<td class="trow1" colspan="2"><strong>{$lang->joblist_add_jobdesc}</strong>
			<div class="smalltext">{$lang->joblist_add_jobdesc_desc}</div>
			</td>
		</tr>
		<tr>
<td class="trow2" colspan="2">
				<textarea name="jobdesc" id="jobdesc" placeholder="Beschreibe hier den Betrieb kurz. Was macht ihn aus, was tut er etc." style="width: 90%; height: 50px;">{$row[\'jobdesc\']}</textarea>
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="3">
				<input type="submit" class="textbox" name="edit_job" id="edit_job" class="buttom" value="{$lang->joblist_edit_job}">
			</td>
			</tr>
	</table>
</form>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_otherinfos',
        'template' => $db->escape_string('<div class="float_right"><a onclick="$(\'#jid_{$jid}\').modal({ fadeDuration: 250, keepelement: true, zIndex: (typeof modal_zindex !== \'undefined\' ? modal_zindex : 9999) }); return false;" style="cursor: pointer;">{$lang->joblist_otherinfos}</a>	<div class="modal" id="jid_{$jid}" style="display: none;">
<div class="joblist_otherinfos">	{$otherinfos}
	</div></div></div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_staff',
        'template' => $db->escape_string('<div class="joblist_staff">{$staff} - {$charajob}</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
	  
	$insert_array = array(
        'title' => 'joblist_tabcontent',
        'template' => $db->escape_string('<div id="{$industry}" class="tabcontent">
  <div class="joblist_job_flex">
	  {$joblist_bit}
	</div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //CSS einfügen
    $css = array(
        'name' => 'joblist.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '.joblist{
	display: flex;
}

/* Style the tab */
.tab {
  overflow: hidden;
	display: flex;
	 flex-flow: column wrap;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 12px;
	box-sizing: border-box;
	width: 100%;
  animation: fadeEffect 1s; /* Fading effect takes 1 second */
}

/* Go from zero to full opacity */
@keyframes fadeEffect {
  from {opacity: 0;}
  to {opacity: 1;}
}

.joblist_infotext{
	margin: 20px;
	text-align: justify;
	font-size: 13px;
}



.joblist_job_flex{
	display: flex;
	flex-flow: row wrap;
}

.joblist_job_flex > div{
	margin: 5px;	
}

.joblist_job{
	width: 33%;
}

.joblist_job_top{
	background: #efefef;
  color: #333;
  border-top: 1px solid #fff;
  border-bottom: 1px solid #ccc;
  padding: 5px;
	box-sizing: border-box;
	text-align: center;
}

.joblist_job_desc{
	height: 100px;
	overflow: auto;
	padding: 2px 5px;
	box-sizing: border-box;
	text-align: justify;
}

.joblist_job_staff_top{
	text-align: center;	
}

.joblist_job_staff{
	padding: 10px;
	box-sizing: border-box;
	max-height: 100px;
	overflow: auto;
}

	.joblist_staff{
	padding: 2px 5px;		
}

.joblist_staff::before{
		content: "» ";
	padding-right: 2px;
}

.joblist_otherinfos{
	padding: 10px 20px;	
}
        ',
        'cachefile' => $db->escape_string(str_replace('/', '', 'joblist.css')),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

	// Don't forget this!
	rebuild_settings();


}

function joblist_is_installed()
{
	global $db;
	if ($db->table_exists("joblist")) {
		return true;
	}
	return false;
}

function joblist_uninstall()
{
	global $db;

	if ($db->table_exists("joblist")) {
		$db->drop_table("joblist");
	}
	// Spalte wieder aus Usergruppe Tabelle entfernen
	if ($db->field_exists("canaddjob", "usergroups")) {
		$db->drop_column("usergroups", "canaddjob");
	}
	if ($db->field_exists("canjoinjob", "usergroups")) {
		$db->drop_column("usergroups", "canjoinjob");
	}

	// Spalte wieder aus Usertabelle entfernen
	if ($db->field_exists("jtitle", "users")) {
		$db->drop_column("users", "jtitle");
	}
	if ($db->field_exists("jid", "users")) {
		$db->drop_column("users", "jid");
	}
	if ($db->field_exists("sjtitle", "users")) {
		$db->drop_column("users", "sjtitle");
	}
	if ($db->field_exists("sjid", "users")) {
		$db->drop_column("users", "sjid");
	}

	$db->delete_query('settings', "name IN ('joblist_industries','joblist_userstyle', 'joblist_desc')");
	$db->delete_query('settinggroups', "name = 'joblist'");

	$db->delete_query("templates", "title LIKE '%joblist%'");
    // Don't forget this
    rebuild_settings();

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'joblist.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
        rebuild_settings();
    }

	// Don't forget this
	rebuild_settings();

}

function joblist_activate()
{
	global $db, $cache;
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
		$alertType->setCode('joblist_acceptjob'); // The codename for your alert type. Can be any unique string.
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
	}

	require MYBB_ROOT . "/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#" . preg_quote('<navigation>') . "#i", '{$joblist_global} <navigation>');
	find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$nav_ipsearch}') . "#i", '{$nav_ipsearch}{$nav_joblist}');
	find_replace_templatesets("member_profile", "#" . preg_quote('{$online_status}') . "#i", '{$online_status} <br />   {$memprofile[\'job\']}');

}

function joblist_deactivate()
{
	global $db, $cache;
	//Alertseinstellungen
	if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		if (!$alertTypeManager) {
			$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
		}

		$alertTypeManager->deleteByCode('joblist_acceptjob');
	}

	require MYBB_ROOT . "/inc/adminfunctions_templates.php";
	find_replace_templatesets("header", "#" . preg_quote('{$joblist_global}') . "#i", '', 0);
    find_replace_templatesets("member_profile", "#" . preg_quote('{$global_newentry_alert}') . "#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$memprofile[\'job\']}') . "#i", '', 0);

}

function joblist_usergroup_permission()
{
	global $mybb, $lang, $form, $form_container, $run_module;

	if ($run_module == 'user' && !empty($form_container->_title) & !empty($lang->misc) & $form_container->_title == $lang->misc) {
		$joblist_options = array(
			$form->generate_check_box('canaddjob', 1, "Kann eine neue Arbeitsstelle hinzufügen?", array("checked" => $mybb->input['canaddjob'])),
			$form->generate_check_box('canjoinjob', 1, "Kann einen Job eintagen?", array("checked" => $mybb->input['canjoinjob'])),
		);
		$form_container->output_row("Einstellung für die Joblist", "", "<div class=\"group_settings_bit\">" . implode("</div><div class=\"group_settings_bit\">", $joblist_options) . "</div>");
	}
}

function joblist_usergroup_permission_commit()
{
	global $db, $mybb, $updated_group;
	$updated_group['canaddjob'] = $mybb->get_input('canaddjob', MyBB::INPUT_INT);
	$updated_group['canjoinjob'] = $mybb->get_input('canjoinjob', MyBB::INPUT_INT);
}

// Admin CP konfigurieren - 
//Action Handler erstellen


function joblist_admin_config_action_handler(&$actions)
{
	$actions['joblist'] = array('active' => 'joblist', 'file' => 'joblist');
}

//ACP Permissions - Berechtigungen für die Admins (über ACP einstellbar)

function joblist_admin_config_permissions(&$admin_permissions)
{
	global $lang;
	$lang->load('joblist');
	$admin_permissions['joblist'] = $lang->joblist_canadmin;
	return $admin_permissions;
}

function joblist_admin_config_menu(&$sub_menu)
{
	global $lang;
	$lang->load('joblist');

	$sub_menu[] = [
		"id" => "joblist",
		"title" => $lang->joblist_nav,
		"link" => "index.php?module=config-joblist"
	];
}

function joblist_manage_joblist()
{
	global $mybb, $db, $lang, $page, $run_module, $action_file;
	$lang->load('joblist');

	if ($page->active_action != 'joblist') {
		return false;
	}

	if ($run_module == 'config' && $action_file == "joblist") {
		//Aufnahmestop Übersicht 
		if ($mybb->input['action'] == "" || !isset($mybb->input['action'])) {
			// Add a breadcrumb - Navigation Seite 
			$page->add_breadcrumb_item($lang->joblist);

			//Header Auswahl Felder im Aufnahmestop verwalten Menü hinzufügen
			$page->output_header($lang->joblist . " - " . $lang->joblist_overview);

			//Übersichtsseite über alle Stops
			$sub_tabs['joblist'] = [
				"title" => $lang->joblist_overview,
				"link" => "index.php?module=config-joblist",
				"description" => $lang->joblist_overview_desc
			];

			//Neuen Kategorie hinzufügen
			$sub_tabs['joblist_addjob'] = [
				"title" => $lang->joblist_add_job,
				"link" => "index.php?module=config-joblist&amp;action=add_job",
				"description" => $lang->joblist_add_job_desc
			];
			$page->output_nav_tabs($sub_tabs, 'joblist');
			//Übersichtsseite erstellen 
			$form = new Form("index.php?module=config-joblist", "post");


			//Hauptcontainer!
			$form_container = new FormContainer("<div style=\"text-align: center;\">$lang->joblist</div>");
			// die Unteren spalten
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->joblist_name</div>");
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->joblist_industry</div>");
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->joblist_place</div>");
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->joblist_desc</div>");
			$form_container->output_row_header("<div style=\"text-align: center;\">$lang->joblist_status</div>");
			//Optionen
			$form_container->output_row_header($lang->joblist_option, array('style' => 'text-align: center; width: 10%;'));

			$jobquery = $db->query("SELECT *
			FROM " . TABLE_PREFIX . "joblist
			ORDER BY industry ASC, job ASC
			");

			while ($job = $db->fetch_array($jobquery)) {
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($job['job']) . '</strong>', array("class" => "align_center", 'style' => 'width: 20%;'));
				$form_container->output_cell('<strong>' . htmlspecialchars_uni($job['industry']) . '</strong>', array("class" => "align_center", 'style' => 'width: 15%;'));
				$form_container->output_cell(htmlspecialchars_uni($job['jobplace']), array("class" => "align_center", 'style' => 'width: 15%;'));
				$form_container->output_cell("<div style='max-height: 100px; overflow: auto; text-align: justify; padding: 0 5px;'>" . htmlspecialchars_uni($job['jobdesc']) . "</div>", array('style' => 'width:35%; '));
				if ($job['ok'] == "0") {
					$Job_status = "<img src='styles/default/images/icons/no_change.png' title='{$lang->joblist_noaccept}'>";
				} else {
					$Job_status = "<img src='styles/default/images/icons/success.png' title='{$lang->joblist_accept}'>";
				}

				$form_container->output_cell('<div style="text-align: center;">' . $Job_status . '</div>', array("class" => "align_center", 'style' => 'width: 5%;'));
				//Pop Up für Bearbeiten & Löschen
				$popup = new PopupMenu("job_{$job['jid']}", $lang->joblist_option);
				$popup->add_item(
					$lang->joblist_edit,
					"index.php?module=config-joblist&amp;action=edit_job&amp;jid={$job['jid']}"
				);
				$popup->add_item(
					$lang->joblist_delete,
					"index.php?module=config-joblist&amp;action=delete_job&amp;jid={$job['jid']}"
					. "&amp;my_post_key={$mybb->post_code}"
				);
				$form_container->output_cell($popup->fetch(), array("class" => "align_center"));
				$form_container->construct_row();
			}

			$form_container->end();
			$form->end();
			$page->output_footer();
		}

		// Job hinzufügen
		if ($mybb->input['action'] == "add_job") {
			if ($mybb->request_method == "post") {
				if (empty($mybb->input['job'])) {
					$error[] = $lang->joblist_error_job;
				}

				if (empty($mybb->input['jobdesc'])) {
					$error[] = $lang->joblist_error_jobdesc;
				}

				if (empty($mybb->input['jobplace'])) {
					$error[] = $lang->joblist_error_jobplace;
				}

				if (empty($error)) {
					$industry = $db->escape_string($mybb->input['industry']);
					$job = $db->escape_string($mybb->input['job']);
					$jobplace = $db->escape_string($mybb->input['jobplace']);
					$jobdesc = $db->escape_string($mybb->input['jobdesc']);
					$otherinfos = $db->escape_string($mybb->input['otherinfos']);
					$ok = (int) 1;
					$uid = (int) $mybb->user['uid'];

					$new_job = array(
						"industry" => $industry,
						"job" => $job,
						"jobplace" => $jobplace,
						"jobdesc" => $jobdesc,
						"otherinfos" => $otherinfos,
						"ok" => $ok,
						"uid" => $uid
					);

					$db->insert_query("joblist", $new_job);

					$mybb->input['module'] = "joblist";
					$mybb->input['action'] = $lang->joblist_add_solved;
					log_admin_action(htmlspecialchars_uni($mybb->input['industry']));

					flash_message($lang->joblist_add_solved, 'success');
					admin_redirect("index.php?module=config-joblist");
				}
			}

			// ACP Aufbau
			$page->add_breadcrumb_item($lang->joblist_addjob);

			//Header Auswahl Felder im Aufnahmestop verwalten Menü hinzufügen
			$page->output_header($lang->joblist . " - " . $lang->joblist_overview);
			//Übersichtsseite über aller Jobs
			$sub_tabs['joblist'] = [
				"title" => $lang->joblist_overview,
				"link" => "index.php?module=config-joblist",
				"description" => $lang->joblist_overview_desc
			];

			//Neuen Kategorie hinzufügen
			$sub_tabs['joblist_addjob'] = [
				"title" => $lang->joblist_add_job,
				"link" => "index.php?module=config-joblist&amp;action=add_job",
				"description" => $lang->joblist_add_job_desc
			];

			$page->output_nav_tabs($sub_tabs, 'joblist_addjob');

			// Erstellen der "Formulareinträge"
			$form = new Form("index.php?module=config-joblist&amp;action=add_job", "post", "", 1);
			$form_container = new FormContainer($lang->joblist_addjob);

			$form_container->output_row(
				$lang->joblist_add_name . "<em>*</em>",
				$lang->joblist_add_name_desc,
				$form->generate_text_box('job', isset($mybb->input['job']))
			);

			$options = array();
			//Wenn es welche gibt: 
			$industries = str_replace(", ", ",", $mybb->settings['joblist_industries']);
			$industries = explode(",", $industries);
			asort($industries);
			foreach ($industries as $industrie) {
				$options[$industrie] = $industrie;
			}

			$form_container->output_row(
				$lang->joblist_add_industry . " <em>*</em>",
				$lang->joblist_add_industry_desc,
				$form->generate_select_box(
					'industry',
					$options,
					array($mybb->get_input('industry', MyBB::INPUT_INT)),
					array('id' => 'industry')
				),
				'industry'
			);

			$form_container->output_row(
				$lang->joblist_add_place . "<em>*</em>",
				$lang->joblist_add_place_desc,
				$form->generate_text_box('jobplace', isset($mybb->input['jobplace']))
			);

			$form_container->output_row(
				$lang->joblist_add_otherinfos,
				$lang->joblist_add_otherinfos_desc,
				$form->generate_text_area('otherinfos', isset($mybb->input['otherinfos']))
			);

			$form_container->output_row(
				$lang->joblist_add_jobdesc . "<em>*</em>",
				$lang->joblist_add_jobdesc_desc,
				$form->generate_text_area('jobdesc', isset($mybb->input['jobdesc']))
			);

			$form_container->output_row(
				$lang->joblist_add_owner,
				$lang->joblist_add_owner_desc,
				$form->generate_numeric_field('uid', isset($mybb->input['uid']))
			);

			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->joblist_add_send);
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();

			exit;

		}


		// Job hinzufügen
		if ($mybb->input['action'] == "edit_job") {

			if ($mybb->request_method == "post") {
				if (empty($mybb->input['job'])) {
					$error[] = $lang->joblist_error_job;
				}

				if (empty($mybb->input['jobdesc'])) {
					$error[] = $lang->joblist_error_jobdesc;
				}

				if (empty($mybb->input['jobplace'])) {
					$error[] = $lang->joblist_error_jobplace;
				}

				if (empty($error)) {
					$jid = $mybb->get_input('jid', MyBB::INPUT_INT);
					$industry = $db->escape_string($mybb->input['industry']);
					$job = $db->escape_string($mybb->input['job']);
					$jobplace = $db->escape_string($mybb->input['jobplace']);
					$jobdesc = $db->escape_string($mybb->input['jobdesc']);
					$otherinfos = $db->escape_string($mybb->input['otherinfos']);
					$uid = (int) $mybb->input['uid'];

					$edit_job = array(
						"industry" => $industry,
						"job" => $job,
						"jobplace" => $jobplace,
						"jobdesc" => $jobdesc,
						"otherinfos" => $otherinfos,
						"uid" => $uid
					);

					$db->update_query("joblist", $edit_job, "jid = '{$jid}'");

					$mybb->input['module'] = "joblist";
					$mybb->input['action'] = $lang->joblist_edit_solved;
					log_admin_action(htmlspecialchars_uni($mybb->input['industry']));

					flash_message($lang->joblist_edit_solved, 'success');
					admin_redirect("index.php?module=config-joblist");
				}
			}

			// ACP Aufbau
			$page->add_breadcrumb_item($lang->joblist_editjob);

			//Header Auswahl Felder im Aufnahmestop verwalten Menü hinzufügen
			$page->output_header($lang->joblist . " - " . $lang->joblist_overview);
			//Übersichtsseite über aller Jobs
			$sub_tabs['joblist'] = [
				"title" => $lang->joblist_overview,
				"link" => "index.php?module=config-joblist",
				"description" => $lang->joblist_overview_desc
			];

			//Neuen Kategorie hinzufügen
			$sub_tabs['joblist_editjob'] = [
				"title" => $lang->joblist_edit_job,
				"link" => "index.php?module=config-joblist&amp;action=edit_job",
				"description" => $lang->joblist_edit_job_desc
			];

			$page->output_nav_tabs($sub_tabs, 'joblist_editjob');

			// Get the data
			$jid = $mybb->get_input('jid', MyBB::INPUT_INT);
			$query = $db->simple_select("joblist", "*", "jid={$jid}");
			$edit_job = $db->fetch_array($query);

			// Erstellen der "Formulareinträge"
			$form = new Form("index.php?module=config-joblist&amp;action=edit_job", "post", "", 1);
			echo $form->generate_hidden_field('jid', $jid);
			$form_container = new FormContainer($lang->joblist_editjob);


			// Job hinzufügen
			$form_container->output_row(
				$lang->joblist_add_name . "<em>*</em>",
				$lang->joblist_add_name_desc,
				$form->generate_text_box('job', $edit_job['job'])
			);

			$options = array();
			//Wenn es welche gibt: 
			$industries = str_replace(", ", ",", $mybb->settings['joblist_industries']);
			$industries = explode(",", $industries);
			asort($industries);
			foreach ($industries as $industry) {
				$options[$industry] = $industry;
			}

			$form_container->output_row(
				$lang->joblist_add_industry . " <em>*</em>",
				$lang->joblist_add_industry_desc,
				$form->generate_select_box('industry', $options, $edit_job['industry'], array($mybb->get_input('industry', MyBB::INPUT_INT)), array('id' => 'industry')),
				'industry'
			);

			$form_container->output_row(
				$lang->joblist_add_place . "<em>*</em>",
				$lang->joblist_add_place_desc,
				$form->generate_text_box('jobplace', $edit_job['jobplace'])
			);

			$form_container->output_row(
				$lang->joblist_add_otherinfos,
				$lang->joblist_add_otherinfos_desc,
				$form->generate_text_area('otherinfos', $edit_job['otherinfos'])
			);

			$form_container->output_row(
				$lang->joblist_add_jobdesc . "<em>*</em>",
				$lang->joblist_add_jobdesc_desc,
				$form->generate_text_area('jobdesc', $edit_job['jobdesc'])
			);
			$form_container->output_row(
				$lang->joblist_add_owner . "<em>*</em>",
				$lang->joblist_add_owner_desc,
				$form->generate_numeric_field('uid', $edit_job['uid'])
			);
			$form_container->end();
			$buttons[] = $form->generate_submit_button($lang->joblist_edit_send);
			$form->output_submit_wrapper($buttons);
			$form->end();
			$page->output_footer();

			exit;

		}

		if ($mybb->input['action'] == "delete_job") {
			$jid = $mybb->get_input('jid', MyBB::INPUT_INT);
			$query = $db->simple_select("joblist", "*", "jid={$jid}");
			$delete_job = $db->fetch_array($query);

			if (empty($jid)) {
				flash_message($lang->joblist_error_option, 'error');
				admin_redirect("index.php?module=config-joblist");

			}
			// Cancel button pressed?
			if (isset($mybb->input['no']) && $mybb->input['no']) {
				admin_redirect("index.php?module=config-joblist");
			}

			if (!verify_post_check($mybb->input['my_post_key'])) {
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-joblist");
			} else {
				if ($mybb->request_method == "post") {

					$db->delete_query("joblist", "jid='{$jid}'");
					;
					$mybb->input['module'] = "joblist";
					$mybb->input['action'] = $lang->joblist_delete_job_solved;
					log_admin_action(htmlspecialchars_uni($delete_job['job']));

					flash_message($lang->joblist_delete_job_solved, 'success');
					admin_redirect("index.php?module=config-joblist");
				} else {

					$page->output_confirm_action(
						"index.php?module=config-joblist&amp;action=delete_job&amp;jid={$jid}",
						$lang->joblist_delete_Job_question
					);
				}
			}
		}
	}
}

// Misc generieren
function joblist_misc()
{
	global $mybb, $templates, $lang, $header, $headerinclude, $industry_option, $footer, $lang, $joblist_nav, $branche, $infotext, $db, $add_job, $theme, $joblist_options;
	$lang->load('joblist');
	require_once MYBB_ROOT . "inc/class_parser.php";
	;
	$parser = new postParser;
	// Do something, for example I'll create a page using the hello_world_template
	$options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1,
		"filter_badwords" => 0,
		"nl2br" => 1,
		"allow_videocode" => 0
	);

	if ($mybb->get_input('action') == 'joblist') {
		// Do something, for example I'll create a page using the hello_world_template

		// Add a breadcrumb
		add_breadcrumb($lang->joblist, "misc.php?action=joblist");
		$industries = str_replace(", ", ",", $mybb->settings['joblist_industries']);
		$industries = explode(",", $industries);
		asort($industries);
		$infotext = $parser->parse_message($mybb->settings['joblist_desc'], $options);

		if ($mybb->usergroup['canaddjob'] == 1) {
			foreach ($industries as $industry) {
				$industry_option .= "<option value='{$industry}'>{$industry}</option>";
			}

			eval ("\$add_job = \"" . $templates->get("joblist_add_job") . "\";");
		}

		if ($mybb->usergroup['canjoinjob'] == 1) {
			$jtitle = "";
			$ujid = 0;
			$sjtitle = "";
			$sjid = 0;

			$jtitle = $mybb->user['jtitle'];
			$ujid = $mybb->user['jid'];
			$sjtitle = $mybb->user['sjtitle'];
			$sjid = $mybb->user['sjid'];

			$get_jobs = $db->query("SELECT jid, job, jobplace
			FROM " . TABLE_PREFIX . "joblist
			WHERE ok = '1'
			ORDER BY jobplace ASC, job ASC
			");
			$secondjob_option = "";
			$job_option = "";
			while ($jobinfo = $db->fetch_array($get_jobs)) {
				$jid = 0;
				$job = "";
				$jobplace = "";
				$selected = "";
				$selected2 = "";

				$jid = $jobinfo['jid'];
				$job = $jobinfo['job'];
				$jobplace = $jobinfo['jobplace'];


				if ($jid == $ujid) {
					$selected = "selected";
				}
				if ($jid == $sjid) {
					$selected2 = "selected";
				}

				$job_option .= "<option value='{$jid}' {$selected} >[{$jobplace}] {$job}</option>";
				$secondjob_option .= "<option value='{$jid}' {$selected2} >[{$jobplace}] {$job}</option>";
			}

			eval ("\$join_job = \"" . $templates->get("joblist_join_job") . "\";");
		}

		$joblist_tabcontent = "";
		foreach ($industries as $industry) {
			$branche .= "  <button class=\"tablinks\" onclick=\"openBranche(event, '{$industry}')\">{$industry}</button>";

			$get_jobs = $db->query("SELECT *
			FROM " . TABLE_PREFIX . "joblist
			WHERE industry = '{$industry}'
			AND ok = 1
			ORDER BY job ASC
			");
			$joblist_bit = "";
			$joblist_otherinfos = "";
			$joblist_options = "";
			while ($row = $db->fetch_array($get_jobs)) {
				$job = "";
				$place = "";
				$jobdesc = "";
				$otherinfos = "";
				$jid = 0;
				$joblist_staff = "";

				$jid = $row['jid'];
				$jobtitle = $row['job'];
				$place = $row['jobplace'];
				$jobdesc = $parser->parse_message($row['jobdesc'], $options);

				if (!empty($row['otherinfos'])) {
					$otherinfos = $parser->parse_message($row['otherinfos'], $options);
					eval ("\$joblist_otherinfos = \"" . $templates->get("joblist_otherinfos") . "\";");
				}

				if ($row['uid'] == $mybb->user['uid'] and $mybb->user['uid'] != 0) {
					$industry_option = "";
					foreach ($industries as $ind) {
						$select = "";
						if ($ind == $row['industry']) {
							$select = "selected";
						}
						$industry_option .= "<option {$select}>{$ind}</option>";
					}

					eval ("\$joblist_options = \"" . $templates->get("joblist_options") . "\";");
				}
				// Mitarbeiter auslesen

				$get_staff = $db->query("SELECT *
				FROM " . TABLE_PREFIX . "users u
				WHERE jid = '{$jid}'
				OR sjid = '{$jid}'
				ORDER BY username ASC
				");

				while ($user = $db->fetch_array($get_staff)) {
					$staff = "";
					$username = "";
					$charajob = "";

					if ($mybb->settings['joblist_userstyle'] == 1) {
						$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);
						$staff = build_profile_link($username, $user['uid']);
					} else {
						$staff = build_profile_link($user['username'], $user['uid']);
					}

					if ($jid == $user['jid']) {
						$charajob .= $user['jtitle'];
					} elseif ($jid == $user['sjid']) {
						$charajob .= $user['sjtitle'] . $lang->joblist_secondjob;
					}



					eval ("\$joblist_staff .= \"" . $templates->get("joblist_staff") . "\";");
				}

				eval ("\$joblist_bit .= \"" . $templates->get("joblist_bit") . "\";");
			}
			eval ("\$joblist_tabcontent .= \"" . $templates->get("joblist_tabcontent") . "\";");
		}


		eval ("\$joblist_nav = \"" . $templates->get("joblist_nav") . "\";");


		// Arbeitsstelle hinzufügen

		if (isset($mybb->input['add_job'])) {
			$new_job = array(
				"industry" => $db->escape_string($mybb->input['industry']),
				"job" => $db->escape_string($mybb->input['job']),
				"jobplace" => $db->escape_string($mybb->input['jobplace']),
				"jobdesc" => $db->escape_string($mybb->input['jobdesc']),
				"otherinfos" => $db->escape_string($mybb->input['otherinfos']),
			);

			$db->insert_query("joblist", $new_job);
			redirect("misc.php?action=joblist");


		}

		// Job eintragen
		if (isset($mybb->input['join_job'])) {
			$uid = 0;
			$uid = $mybb->user['uid'];

			$new_job = array(
				"jtitle" => $db->escape_string($mybb->input['jtitle']),
				"jid" => (int) $mybb->input['jid'],
				"sjtitle" => $db->escape_string($mybb->input['sjtitle']),
				"sjid" => (int) $mybb->input['sjid'],
			);

			$db->update_query("users", $new_job, "uid = '{$uid}'");
			redirect("misc.php?action=joblist");
		}


		// Arbeitsstelle bearbeiten
		if (isset($mybb->input['edit_job'])) {
			$jid = 0;

			$jid = $mybb->input['jid'];
			$edit_job = array(
				"industry" => $db->escape_string($mybb->input['industry']),
				"job" => $db->escape_string($mybb->input['job']),
				"jobplace" => $db->escape_string($mybb->input['jobplace']),
				"jobdesc" => $db->escape_string($mybb->input['jobdesc']),
				"otherinfos" => $db->escape_string($mybb->input['otherinfos']),
				"uid" => (int) $mybb->user['uid']
			);

			$db->update_query("joblist", $edit_job, "jid = '{$jid}'");
			redirect("misc.php?action=joblist");

		}

		// arbeitstelle gelöscht

		if (isset($mybb->input['deletejob'])) {
			$jid = 0;
			$jid = $mybb->input['deletejob'];

			// Usertabelle updaten
			$update_user_main = array(
				"jtitle" => "",
				"jid" => 0
			);

			$update_user_second = array(
				"sjtitle" => "",
				"sjid" => 0
			);
			$db->update_query("users", $update_user_main, "jid = '{$jid}'");
			$db->update_query("users", $update_user_second, "sjid = '{$jid}'");

			// Arbeitsstelle löschen
			$db->delete_query("joblist", "jid='{$jid}'");
			redirect("misc.php?action=joblist");
		}

		// Using the misc_help template for the page wrapper
		eval ("\$page = \"" . $templates->get("joblist") . "\";");
		output_page($page);
	}


}

// Alert für die Teamies :D

function joblist_global()
{
	global $db, $lang, $mybb, $templates, $joblist_global, $globalalert;
	$lang->load('joblist');

	if ($mybb->usergroup['canmodcp'] == 1) {
		$get_openjobs = "";
		$globalalert = "";
		$get_openjobs = $db->fetch_field($db->simple_select("joblist", "COUNT(*) as accepted", "ok = 0"), "accepted");
		if ($get_openjobs > 0) {
			$count = "";
			if ($get_openjobs == 1) {
				$count = "Job";
			} else {
				$count = "Jobs";
			}

			$lang->joblist_global = (isset($lang->joblist_global) ? $lang->joblist_global : false);
			$globalalert = $lang->sprintf($lang->joblist_global, $get_openjobs, $count);

			eval ("\$joblist_global = \"" . $templates->get("joblist_global") . "\";");
		}
	}
}

// Modcp
function joblist_modcp_nav()
{
	global $nav_joblist, $templates, $lang;
	$lang->load('joblist');
	eval ("\$nav_joblist = \"" . $templates->get("joblist_modcp_nav") . "\";");
}

function joblist_modcp()
{
	global $mybb, $templates, $lang, $header, $headerinclude, $footer, $modcp_nav, $db, $theme, $owner, $joblist_modcp_bit, $pmhandler, $session;
	$lang->load('joblist');
	require_once MYBB_ROOT . "inc/class_parser.php";
	$parser = new postParser;
	require_once MYBB_ROOT . "inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();

	// Do something, for example I'll create a page using the hello_world_template
	$options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1,
		"filter_badwords" => 0,
		"nl2br" => 1,
		"allow_videocode" => 0
	);

	if ($mybb->get_input('action') == 'joblist') {
		add_breadcrumb($lang->joblist_modcp, "modcp.php?action=joblist");

		$get_jobs = $db->query("SELECT *
		FROM " . TABLE_PREFIX . "joblist j
		where ok = 0
		ORDER BY job ASC
		");

		while ($jobs = $db->fetch_array($get_jobs)) {
			$job = "";
			$jobplace = "";
			$industry = "";
			$jobdesc = "";
			$otherinfos = "";
			$owner = "";
			$user = "";
			$jid = 0;

			$jid = $jobs['jid'];
			$job = $jobs['job'];
			$jobplace = $jobs['jobplace'];
			$industry = $jobs['industry'];

			$jobdesc = $parser->parse_message($jobs['jobdesc'], $options);
			$otherinfos = $parser->parse_message($jobs['otherinfos'], $options);

			$get_user = $db->fetch_array($db->simple_select("users", "*", "uid = '{$jobs['uid']}'"));
			$user = build_profile_link($get_user['username'], $get_user['uid']);

			$lang->joblist_modcp_owner = (isset($lang->joblist_modcp_owner) ? $lang->joblist_modcp_owner : false);
			$owner = $lang->sprintf($lang->joblist_modcp_owner, $user);

			eval ("\$joblist_modcp_bit .= \"" . $templates->get("joblist_modcp_bit") . "\";");
		}

		if (isset($mybb->input['denyjob'])) {
			$get_jid = 0;
			$get_reason = "";
			$fromuid = 0;
			$owner = 0;

			$get_jid = $mybb->input['jid'];
			$get_reason = $db->escape_string($mybb->input['denyreason']);
			$fromuid = $mybb->user['uid'];
			$get_job = $db->fetch_array($db->simple_select("joblist", "*", "jid = '{$get_jid}'"));

			$owner = $get_job['uid'];
			$subject = (isset($lang->joblist_deny) ? $lang->joblist_deny : false);
			$message = "Deine Arbeitstelle <strong>{$get_job['job']}</strong> wurde vom Team abgelehnt!
			
			Angegebener Grund: {$get_reason}

			Deine Angaben:
			<strong>Branche:<strong> {$get_job['industry']}
			<strong>Jobbeschreibung:</strong> {$get_job['jobdesc']}
			
			Wenn dich bitte an Team, wenn du noch Fragen hast!";



			$pm = array(
				"subject" => "{$lang->joblist_deny}",
				"message" => $message,
				//to: wer muss die anfrage bestätigen
				"fromid" => $fromuid,
				//from: wer hat die anfrage gestellt
				"toid" => $owner
			);

			$pm['options'] = array(
				'signature' => '0',
				'savecopy' => '0',
				'disablesmilies' => '0',
				'readreceipt' => '0',
			);
			if (isset($session)) {
				$pm['ipaddress'] = $session->packedip;
			}
			// $pmhandler->admin_override = true;
			$pmhandler->set_data($pm);
			if (!$pmhandler->validate_pm())
				return false;
			else {
				$pmhandler->insert_pm();
			}

			$db->delete_query("joblist", "jid = '{$get_jid}'");
			redirect("modcp.php?action=joblist", "{$lang->joblist_modcp_deny_solved}");


		}

		if (isset($mybb->input['acceptjob'])) {
			$get_jid = 0;
			$owner = 0;
			$fromuid = 0;
			$get_jid = $mybb->input['jid'];
			$fromuid = $mybb->user['uid'];

			$get_job = $db->fetch_array($db->simple_select("joblist", "*", "jid = '{$get_jid}'"));
			$owner = $get_job['uid'];
			$job = $get_job['job'];

			if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
				$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('joblist_acceptjob');
				if ($alertType != NULL && $alertType->getEnabled()) {
					$alert = new MybbStuff_MyAlerts_Entity_Alert((int) $owner, $alertType);
					$alert->setExtraDetails([
						'job' => $job,
					]);
					MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
				}
			}

			$accept_job = array(
				"ok" => 1
			);

			$db->update_query("joblist", $accept_job, "jid = '{$get_jid}'");
			redirect("modcp.php?action=joblist", "{$lang->joblist_modcp_accept_solved}");

		}

		eval ("\$page = \"" . $templates->get("joblist_modcp") . "\";");
		output_page($page);
	}
}


function joblist_profile()
{
	global $mybb, $db, $templates, $lang, $memprofile;
	$lang->load('joblist');

	if (isset($memprofile['jid'])) {
		$jid = 0;
		$job = "";
		$jid = $memprofile['jid'];
		$job = $db->fetch_field($db->simple_select("joblist", "job", "jid = '{$jid}'"), "job");

		$memprofile['job'] = $lang->sprintf($lang->joblist_profile, $memprofile['jtitle'], $job);

	}

}

function joblist_alerts()
{
	global $mybb, $lang;
	$lang->load('joblist');


	/**
	 * Alert, wenn die Job angenommen wurde
	 */
	class MybbStuff_MyAlerts_Formatter_AcceptJobFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
		/**
		 * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
		 *
		 * @return string The formatted alert string.
		 */
		public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
		{
			$alertContent = $alert->getExtraDetails();
			return $this->lang->sprintf(
				$this->lang->joblist_acceptjob,
				$outputAlert['from_user'],
				$alertContent['job'],
				$outputAlert['dateline']
			);
		}


		/**
		 * Init function called before running formatAlert(). Used to load language files and initialize other required
		 * resources.
		 *
		 * @return void
		 */
		public function init()
		{
		}

		/**
		 * Build a link to an alert's content so that the system can redirect to it.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
		 *
		 * @return string The built alert, preferably an absolute link.
		 */
		public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
		{
			$alertContent = $alert->getExtraDetails();
			return $this->mybb->settings['bburl'] . '/misc.php?action=joblist';
		}
	}

	if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		if (!$formatterManager) {
			$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
		}

		$formatterManager->registerFormatter(
			new MybbStuff_MyAlerts_Formatter_AcceptJobFormatter($mybb, $lang, 'joblist_acceptjob')
		);
	}

}