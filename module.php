<?php
// webtrees - vytux_pages module based on simpl_pages
//
// Copyright (C) 2013 Vytautas Krivickas and vytux.com. All rights reserved.
//
// Copyright (C) 2012 Nigel Osborne and kiwtrees.net. All rights reserved.
//
// webtrees: Web based Family History software
// Copyright (C) 2012-2012 webtrees development team.
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

if (!defined('WT_WEBTREES')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

class vytux_pages_WT_Module extends WT_Module implements WT_Module_Menu, WT_Module_Block, WT_Module_Config {

	// Extend class WT_Module
	public function getTitle() {
		return WT_I18N::translate('Vytux_pages');
	}

	public function getMenuTitle() {
		return WT_I18N::translate('Resources');
	}

	// Extend class WT_Module
	public function getDescription() {
		return WT_I18N::translate('Display resource pages.');
	}

	// Implement WT_Module_Menu
	public function defaultMenuOrder() {
		return 40;
	}

	// Extend class WT_Module
	public function defaultAccessLevel() {
		return WT_PRIV_NONE;
	}

	// Implement WT_Module_Config
	public function getConfigLink() {
		return 'module.php?mod='.$this->getName().'&amp;mod_action=admin_config';
	}

	// Implement class WT_Module_Block
	public function getBlock($block_id, $template=true, $cfg=null) {
	}

	// Implement class WT_Module_Block
	public function loadAjax() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isUserBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function isGedcomBlock() {
		return false;
	}

	// Implement class WT_Module_Block
	public function configureBlock($block_id) {
	}

	// Implement WT_Module_Menu
	public function getMenu() {
		global $controller, $SEARCH_SPIDER;
		
		$block_id=WT_Filter::get('block_id');
		$default_block=WT_DB::prepare(
			"SELECT block_id FROM `##block` WHERE block_order=? AND module_name=?"
		)->execute(array(0, $this->getName()))->fetchOne();

		if ($SEARCH_SPIDER) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR.$this->getName().'/'.WT_THEME_URL)) {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/'.WT_THEME_URL.'style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/webtrees/style.css" type="text/css">';
		}
		
		//-- main PAGES menu item
		$menu = new WT_Menu($this->getMenuTitle(), 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;pages_id='.$default_block, 'menu-my_pages', 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($this->getMenupagesList() as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->pages_access>=WT_USER_ACCESS_LEVEL) {
				$path = 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;pages_id='.$items->block_id;
				$submenu = new WT_Menu(WT_I18N::translate($items->pages_title), $path, 'menu-my_pages-'.$items->block_id);
				$menu->addSubmenu($submenu);
			}
		}
		if (WT_USER_IS_ADMIN) {
			$submenu = new WT_Menu(WT_I18N::translate('Edit pages'), $this->getConfigLink(), 'menu-my_pages-edit');
			$menu->addSubmenu($submenu);
		}
		return $menu;
	}

	// Extend WT_Module
	public function modAction($mod_action) {
		switch($mod_action) {
		case 'show':
			$this->show();
			break;
		case 'admin_config':
			$this->config();
			break;
		case 'admin_delete':
			$this->delete();
			$this->config();
			break;
		case 'admin_edit':
			$this->edit();
			break;
		case 'admin_movedown':
			$this->movedown();
			$this->config();
			break;
		case 'admin_moveup':
			$this->moveup();
			$this->config();
			break;
		}
	}

	// Action from the configuration page
	private function edit() {

		require_once WT_ROOT.'includes/functions/functions_edit.php';

		if (WT_Filter::postBool('save') && WT_Filter::checkCsrf()) {
			$block_id=WT_Filter::post('block_id');
			if ($block_id) {
				WT_DB::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					(int)WT_Filter::post('block_order'),
					$block_id
				));
			} else {
				WT_DB::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					WT_Filter::post('gedcom_id'),
					$this->getName(),
					(int)WT_Filter::post('block_order')
				));
				$block_id=WT_DB::getInstance()->lastInsertId();
			}
			set_block_setting($block_id, 'pages_title', WT_Filter::post('pages_title'));
			set_block_setting($block_id, 'pages_content', WT_Filter::post('pages_content')); // allow html
			set_block_setting($block_id, 'pages_access', WT_Filter::post('pages_access'));
			$languages=array();
			foreach (WT_I18N::installed_languages() as $code=>$name) {
				if (WT_Filter::postBool('lang_'.$code)) {
					$languages[]=$code;
				}
			}
			set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id=WT_Filter::get('block_id');
			$controller=new WT_Controller_Page();
			if ($block_id) {
				$controller->setPageTitle(WT_I18N::translate('Edit pages'));
				$items_title=get_block_setting($block_id, 'pages_title');
				$items_content=get_block_setting($block_id, 'pages_content');
				$items_access=get_block_setting($block_id, 'pages_access');
				$block_order=WT_DB::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id=WT_DB::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(WT_I18N::translate('Add pages'));
				$items_title='';
				$items_content='';
				$items_access=1;
				$block_order=WT_DB::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
				$gedcom_id=WT_GED_ID;
			}
			$controller->pageHeader();
			if (array_key_exists('ckeditor', WT_Module::getActiveModules())) {
				ckeditor_WT_Module::enableEditor($controller);
			}

			echo '<form name="pages" method="post" action="#">';
			echo WT_Filter::getCsrf();
			echo '<input type="hidden" name="save" value="1">';
			echo '<input type="hidden" name="block_id" value="', $block_id, '">';
			echo '<table id="faq_module">';
			echo '<tr><th>';
			echo WT_I18N::translate('Title');
			echo '</th></tr><tr><td><input type="text" name="pages_title" size="90" tabindex="1" value="'.htmlspecialchars($items_title).'"></td></tr>';
			echo '<tr><th>';
			echo WT_I18N::translate('Content');
			echo '</th></tr><tr><td>';
			echo '<textarea name="pages_content" class="html-edit" rows="10" cols="90" tabindex="2">', htmlspecialchars($items_content), '</textarea>';
			echo '</td></tr>';
			echo '<tr><th>', WT_I18N::translate('Access level');
			echo '</th></tr><tr><td>', edit_field_access_level('pages_access', $items_access, 'tabindex="4"'), '</td></tr>';
			echo '</table><table id="pages_module2">';
			echo '<tr>';
			echo '<th>', WT_I18N::translate('Show this pages for which languages?'), help_link('pages_language', $this->getName()), '</th>';
			echo '<th>', WT_I18N::translate('Pages position'), help_link('pages_position', $this->getName()), '</th>';
			echo '<th>', WT_I18N::translate('Pages visibility'), help_link('pages_visibility', $this->getName()), '</th>';
			echo '</tr><tr>';
			echo '<td>';
			$languages=get_block_setting($block_id, 'languages');
			echo edit_language_checkboxes('lang_', $languages);
			echo '</td><td>';
			echo '<input type="text" name="block_order" size="3" tabindex="5" value="', $block_order, '"></td>';
			echo '</td><td>';
			echo select_edit_control('gedcom_id', WT_Tree::getIdList(), WT_I18N::translate('All'), $gedcom_id, 'tabindex="4"');
			echo '</td></tr>';
			echo '</table>';

			echo '<p><input type="submit" value="', WT_I18N::translate('Save'), '" tabindex="7">';
			echo '&nbsp;<input type="button" value="', WT_I18N::translate('Cancel'), '" onclick="window.location=\''.$this->getConfigLink().'\';" tabindex="8"></p>';
			echo '</form>';
			exit;
		}
	}

	private function delete() {
		$block_id=WT_Filter::get('block_id');

		WT_DB::prepare(
			"DELETE FROM `##block_setting` WHERE block_id=?"
		)->execute(array($block_id));

		WT_DB::prepare(
			"DELETE FROM `##block` WHERE block_id=?"
		)->execute(array($block_id));
	}

	private function moveup() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function movedown() {
		$block_id=WT_Filter::get('block_id');

		$block_order=WT_DB::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=WT_DB::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			WT_DB::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function show() {
		global $controller;
		$items_header_description = '';//Add your own header here.
		$items_id=WT_Filter::get('pages_id');
		$controller=new WT_Controller_Page();
		$controller->setPageTitle(WT_I18N::translate('Resource pages'))//Edit this line for a different summary page title
			->pageHeader();
		// HTML common to all pages
		$html='<div id="pages-container">'.
				'<h2>'.$controller->getPageTitle().'</h2>'.
				$items_header_description.
				'<div style="clear:both;"></div>'.
				'<div id="pages_tabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all">'.
				'<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">';
		$items_list=$this->getPagesList();
		foreach ($items_list as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->pages_access>=WT_USER_ACCESS_LEVEL) {
				$html.='<li class="ui-state-default ui-corner-top'.($items_id==$items->block_id ? ' ui-tabs-selected ui-state-active' : '').'">'.
					'<a href="module.php?mod='.$this->getName().'&amp;mod_action=show&amp;pages_id='.$items->block_id.'">'.
					'<span title="'.WT_I18N::translate($items->pages_title).'">'.WT_I18N::translate($items->pages_title).'</span></a></li>';
			}
		}
		$html.='</ul>';
		$html.='<div id="outer_pages_container" style="padding: 1em;">';
		foreach ($items_list as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items_id==$items->block_id && $items->pages_access>=WT_USER_ACCESS_LEVEL) {
				$items_content=WT_I18N::translate($items->pages_content);
			}
		}
		$html.=$items_content;
		$html.='</div>'; //close outer_pages_container
		$html.='</div>'; //close pages_tabs
		$html.='</div>'; //close pages-container
		echo $html;
	}

	private function config() {
		require_once 'includes/functions/functions_edit.php';

		$controller=new WT_Controller_Page();
		$controller->setPageTitle($this->getTitle());
		$controller->pageHeader();

		$items=WT_DB::prepare(
			"SELECT block_id, block_order, gedcom_id, bs1.setting_value AS pages_title, bs2.setting_value AS pages_content".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='pages_title'".
			" AND bs2.setting_name='pages_content'".
			" AND IFNULL(gedcom_id, ?)=?".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID, WT_GED_ID))->fetchAll();

		$min_block_order=WT_DB::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		$max_block_order=WT_DB::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		echo
			'<p><form method="get" action="', WT_SCRIPT_NAME ,'">',
			WT_I18N::translate('Family tree'), ' ',
			'<input type="hidden" name="mod", value="', $this->getName(), '">',
			'<input type="hidden" name="mod_action", value="admin_config">',
			select_edit_control('ged', WT_Tree::getNameList(), null, WT_GEDCOM),
			'<input type="submit" value="', WT_I18N::translate('show'), '">',
			'</form></p>';

		echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_edit">', WT_I18N::translate('Add page'), '</a>';
		echo '<table id="faq_edit">';
		if (empty($items)) {
			echo '<tr><td class="error center" colspan="5">', WT_I18N::translate('No pages have been created.'), '</td></tr></table>';
		} else {
			$trees=WT_Tree::getAll();
			foreach ($items as $item) {
				// NOTE: Print the position of the current item
				echo '<tr class="faq_edit_pos"><td>';
				echo WT_I18N::translate('Position item'), ': ', $item->block_order, ', ';
				if ($item->gedcom_id==null) {
					echo WT_I18N::translate('All');
				} else {
					echo $trees[$item->gedcom_id]->tree_title_html;
				}
				echo '</td>';
				// NOTE: Print the edit options of the current item
				echo '<td>';
				if ($item->block_order==$min_block_order) {
					echo '&nbsp;';
				} else {
					echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_moveup&amp;block_id=', $item->block_id, ' "class="icon-uarrow"></a>';
				}
				echo '</td><td>';
				if ($item->block_order==$max_block_order) {
					echo '&nbsp;';
				} else {
					echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_movedown&amp;block_id=', $item->block_id, ' "class="icon-darrow"></a>';
				}
				echo '</td><td>';
				echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_edit&amp;block_id=', $item->block_id, '">', WT_I18N::translate('Edit'), '</a>';
				echo '</td><td>';
				echo '<a href="module.php?mod=', $this->getName(), '&amp;mod_action=admin_delete&amp;block_id=', $item->block_id, '" onclick="return confirm(\'', WT_I18N::translate('Are you sure you want to delete this pages?'), '\');">', WT_I18N::translate('Delete'), '</a>';
				echo '</td></tr>';
				// NOTE: Print the title text of the current item
				echo '<tr><td colspan="5">';
				echo '<div class="faq_edit_item">';
				echo '<div class="faq_edit_title">', WT_I18N::translate($item->pages_title), '</div>';
				// NOTE: Print the body text of the current item
				echo '<div>', substr(WT_I18N::translate($item->pages_content), 0, 1)=='<' ? WT_I18N::translate($item->pages_content) : nl2br(WT_I18N::translate($item->pages_content)), '</div></div></td></tr>';
			}
			echo '</table>';
		}
	}

	// Return the list of pages
	private function getPagesList() {
		return WT_DB::prepare(
			"SELECT block_id, bs1.setting_value AS pages_title, bs2.setting_value AS pages_access, bs3.setting_value AS pages_content".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" JOIN `##block_setting` bs3 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='pages_title'".
			" AND bs2.setting_name='pages_access'".
			" AND bs3.setting_name='pages_content'".
			" AND (gedcom_id IS NULL OR gedcom_id=?)".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID))->fetchAll();
	}
	
	// Return the list of pages for menu
	private function getMenupagesList() {
		return WT_DB::prepare(
			"SELECT block_id, bs1.setting_value AS pages_title, bs2.setting_value AS pages_access".
			" FROM `##block` b".
			" JOIN `##block_setting` bs1 USING (block_id)".
			" JOIN `##block_setting` bs2 USING (block_id)".
			" WHERE module_name=?".
			" AND bs1.setting_name='pages_title'".
			" AND bs2.setting_name='pages_access'".
			" AND (gedcom_id IS NULL OR gedcom_id=?)".
			" ORDER BY block_order"
		)->execute(array($this->getName(), WT_GED_ID))->fetchAll();
	}
	
}
