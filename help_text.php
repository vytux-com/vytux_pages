<?php
// Module help text.
//
// This file is included from the application help_text.php script.
// It simply needs to set $title and $text for the help topic $help_topic
//
// webtrees: Web based Family History software
// Copyright (C) 2011 webtrees development team.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//

if (!defined('WT_WEBTREES') || !defined('WT_SCRIPT_NAME') || WT_SCRIPT_NAME!='help_text.php') {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

switch ($help) {
case 'pages_position':
	$title=WT_I18N::translate('Page position');
	$text=WT_I18N::translate('This field controls the order in which the pages are displayed.').'<br><br>'.WT_I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other pages later. For example, if you use the numbers 1, 6, 11, 16, you can later insert pages with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert pages in front of the first one.').'<br><br>'.WT_I18N::translate('When more than one page has the same position number, only one of these pages will be visible.');
	break;

case 'pages_visibility':
	$title=WT_I18N::translate('Page visibility');
	$text=WT_I18N::translate('You can determine whether this page will be visible regardless of family tree, or whether it will be visible only to the current family tree.').
	'<br><ul><li><b>'.WT_I18N::translate('All').'</b>&nbsp;&nbsp;&nbsp;'.WT_I18N::translate('The page will always appear, regardless of family tree.').'</li><li><b>'.get_gedcom_setting(WT_GED_ID, 'title').'</b>&nbsp;&nbsp;&nbsp;'.WT_I18N::translate('The page will appear only in the currently active family tree.').'</li></ul>';
	break;

case 'pages_language':
	$title=WT_I18N::translate('Page language');
	$text=WT_I18N::translate('Either leave all languages un-ticked to display the page contents in every language, or tick the specific languages you want to display it for.<br><br>To create translated pages for different languages create multiple copies setting the appropriate language only for each version.');
	break;
	
}
