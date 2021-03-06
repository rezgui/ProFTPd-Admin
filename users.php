<?php
/**
 * This file is part of ProFTPd Admin
 *
 * @package ProFTPd-Admin
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 *
 * @copyright Christian Beer <djangofett@gmx.net>
 * @copyright Lex Brugman <lex_brugman@users.sourceforge.net>
 *
 * @todo Change tables to divs and declare colors in style.css
 * @todo some columns are passed directly to db, make them generic
 */

include_once ("configs/config.php");
include_once ("includes/AdminClass.php");
global $cfg;

$ac = new AdminClass($cfg);
echo $ac->get_header();

$req_order = "asc";
$bkw_order = "desc";
if (isset($_REQUEST["order"])) {
    $req_order = $_REQUEST["order"] == "asc"?"asc":"desc";
    $bkw_order = $_REQUEST["order"] == "asc"?"desc":"asc";
}
$sort = $cfg['field_userid'];
if (isset($_REQUEST["sort"])) {
	$sortfield = "field_" . $_REQUEST["sort"];
	$sort = $cfg[$sortfield];
}

if ($sort=="") $sort = $cfg['field_userid'];
$$sort = "&nbsp;<img src=\"images/" . $req_order . ".gif\" alt=\"Arrow\" border=\"0\" valign=\"middle\" />";
$nof_columns = 13; // added one for additional groups column that is not sortable
$columns = array("userid" => "Userid", "uid" => "UID", "email" => "E-mail", "last_login" => "Last login", "login_count" => "Nr. of logins", "bytes_in_used" => "Upload", "bytes_out_used" => "Download", "files_in_used" => "Nr. of<br />uploaded<br />files", "files_out_used" => "Nr. of<br />downloaded<br />files", "homedir" => "Home<br />directory", "disabled" => "Suspended", "gid" => "Main group");

$counter = 0;
$users = $ac->get_users_as_array($sort, $req_order);
$groups = $ac->parse_groups();
if(!is_array($users)) {
    print("<strong>There are no users in the database. Please add some users. </strong>");
    echo $ac->get_footer();
    die;
}

$ufilter="";
// see config_example.php on howto activate
if($cfg['userid_filter_separator'] != "") {
    $ufilter = isset($_REQUEST["uf"])?$_REQUEST["uf"]:"";
    $userfilter = array();
    foreach ($users as $user) {
        $pos = strpos($user[$cfg['field_userid']], $cfg['userid_filter_separator']);
        // userid's should not start with a - !
        if($pos != FALSE) {
            $prefix = substr($user[$cfg['field_userid']], 0, $pos);
            if(@$userfilter[$prefix] == "") {
                $userfilter[$prefix] = $prefix;
            }
        }
    }

    print("Filter user by prefix: ");
    print("<a href=\"users.php\">All</a>&nbsp;|&nbsp;");
    print("<a href=\"users.php?uf=None\">None</a>");
    foreach ($userfilter as $uf) {
        print("&nbsp;|&nbsp;<a href=\"users.php?uf=".$uf."\">".$uf."</a>");
    }
}

print("<table><tr><td colspan=\"" . $nof_columns . "\">");
print("</td></tr>");
print("<tr bgcolor=\"" . $cfg['tpbgcolor'] . "\">");
// output table header
foreach ($columns as $column => $title) {
    if ($sort == $column) {
        print("<td><b><a href=\"users.php?order=" . $bkw_order . "&sort=$column\">$title</a>" . $$column . "</b></td>");
    } else {
        print("<td><b><a href=\"users.php?sort=$column\">$title</a></b></td>");
    }
}
print("<td><b>Additional<br />groups</b></td>" .
    "</tr>");

foreach ($users as $user) {
    if($ufilter != "") {
        // None means users without a prefix
        if($ufilter == "None") {
            if(strpos($user[$cfg['field_userid']], $cfg['userid_filter_separator']) != 0) {
                continue;
            }
        // else check if user has the filtered prefix
        } elseif (strncmp($user[$cfg['field_userid']], $ufilter, strlen($ufilter)) != 0) {
            continue;
        }
    }
    
    if (empty($groups[$user[$cfg['field_userid']]])) {
        $groups[$user[$cfg['field_userid']]][0] = "none";
    }

    $all_groups = $ac->get_groups();
    $uid_group = $all_groups[$user[$cfg['field_gid']]];
    $bytes_in_mb = sprintf("%2.1f", $user[$cfg['field_bytes_in_used']] / 1048576);
    $bytes_out_mb = sprintf("%2.1f", $user[$cfg['field_bytes_out_used']] / 1048576);

    if ($counter % 2 == 0) {
        print("<tr bgcolor=\"".$cfg['dwbgcolor1']."\">");
    } else {
        print("<tr bgcolor=\"".$cfg['dwbgcolor2']."\">");
    }

    print("<td align=\"left\"><a href=\"edit_user.php?id=" . $user[$cfg['field_id']] . "&name=" . $user[$cfg['field_userid']] . "\" title=\"" . $user[$cfg['field_comment']] . "\">" . $user[$cfg['field_userid']] . "</a></td>" .
            "<td align=\"center\">" . $user[$cfg['field_uid']] . "</td>" .
            "<td align=\"center\">" . $user[$cfg['field_email']] . "</td>" .
            "<td align=\"center\">" . $user[$cfg['field_last_login']] . "</td>" .
            "<td align=\"center\">" . $user[$cfg['field_login_count']] . "</td>" .
            "<td align=\"center\">" . $bytes_in_mb . " Mb</td>" .
            "<td align=\"center\">" . $bytes_out_mb . " Mb</td>" .
            "<td align=\"center\">" . $user[$cfg['field_files_in_used']] . "</td>" .
            "<td align=\"center\">" . $user[$cfg['field_files_out_used']] . "</td>" .
            "<td align=\"center\">" . $user[$cfg['field_homedir']] . "</td>" .
            "<td align=\"center\">" . ($user[$cfg['field_disabled']] ? 'Yes' : 'No') . "</td>" .
            "<td align=\"center\">" . $uid_group . "</td>" .
            "<td align=\"center\">" . implode(", ", $groups[$user[$cfg['field_userid']]]) . "</td></tr>");
    $counter = $counter + 1;
}
print("<tr><td colspan=\"" . $nof_columns . ">\"><i>To edit a user: click on the username</i></td></tr></table>");

echo $ac->get_footer();
?>
