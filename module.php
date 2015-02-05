<?php
namespace Fisharebest\Webtrees;

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

class vytux_pages_WT_Module extends Module implements ModuleBlockInterface, ModuleConfigInterface, ModuleMenuInterface {

	// Extend class WT_Module
	public function getTitle() {
		return I18N::translate('Vytux Pages');
	}

	public function getMenuTitle() {
		return I18N::translate('Resources');
	}

	// Extend class WT_Module
	public function getDescription() {
		return I18N::translate('Display resource pages.');
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
		
		$block_id=Filter::get('block_id');
		$default_block=Database::prepare(
			"SELECT block_id FROM `##block` WHERE block_order=? AND module_name=?"
		)->execute(array(0, $this->getName()))->fetchOne();

		if ($SEARCH_SPIDER) {
			return null;
		}
		
		if (file_exists(WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/')) {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/'.Theme::theme()->themeId().'/style.css" type="text/css">';
		} else {
			echo '<link rel="stylesheet" href="'.WT_MODULES_DIR.$this->getName().'/themes/webtrees/style.css" type="text/css">';
		}
		
		//-- main PAGES menu item
		$menu = new Menu($this->getMenuTitle(), 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;pages_id='.$default_block, 'menu-my_pages', 'down');
		$menu->addClass('menuitem', 'menuitem_hover', '');
		foreach ($this->getMenupagesList() as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items->pages_access>=WT_USER_ACCESS_LEVEL) {
				$path = 'module.php?mod='.$this->getName().'&amp;mod_action=show&amp;pages_id='.$items->block_id;
				$submenu = new Menu(I18N::translate($items->pages_title), $path, 'menu-my_pages-'.$items->block_id);
				$menu->addSubmenu($submenu);
			}
		}
		if (Auth::isAdmin()) {
			$submenu = new Menu(I18N::translate('Edit pages'), $this->getConfigLink(), 'menu-my_pages-edit');
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
			$this->moveDown();
			$this->config();
			break;
		case 'admin_moveup':
			$this->moveUp();
			$this->config();
			break;
		default:
			http_response_code(404);
		}
	}

	// Action from the configuration page
	private function edit() {
		if (Filter::postBool('save') && Filter::checkCsrf()) {
			$block_id=Filter::post('block_id');
			if ($block_id) {
				Database::prepare(
					"UPDATE `##block` SET gedcom_id=NULLIF(?, ''), block_order=? WHERE block_id=?"
				)->execute(array(
					Filter::post('gedcom_id'),
					(int)Filter::post('block_order'),
					$block_id
				));
			} else {
				Database::prepare(
					"INSERT INTO `##block` (gedcom_id, module_name, block_order) VALUES (NULLIF(?, ''), ?, ?)"
				)->execute(array(
					Filter::post('gedcom_id'),
					$this->getName(),
					(int)Filter::post('block_order')
				));
				$block_id=Database::getInstance()->lastInsertId();
			}
			set_block_setting($block_id, 'pages_title', Filter::post('pages_title'));
			set_block_setting($block_id, 'pages_content', Filter::post('pages_content')); // allow html
			set_block_setting($block_id, 'pages_access', Filter::post('pages_access'));
			$languages=array();
			foreach (I18N::installed_languages() as $code=>$name) {
				if (Filter::postBool('lang_'.$code)) {
					$languages[]=$code;
				}
			}
			set_block_setting($block_id, 'languages', implode(',', $languages));
			$this->config();
		} else {
			$block_id=Filter::get('block_id');
			$controller=new PageController();
			if ($block_id) {
				$controller->setPageTitle(I18N::translate('Edit pages'));
				$items_title=get_block_setting($block_id, 'pages_title');
				$items_content=get_block_setting($block_id, 'pages_content');
				$items_access=get_block_setting($block_id, 'pages_access');
				$block_order=Database::prepare(
					"SELECT block_order FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
				$gedcom_id=Database::prepare(
					"SELECT gedcom_id FROM `##block` WHERE block_id=?"
				)->execute(array($block_id))->fetchOne();
			} else {
				$controller->setPageTitle(I18N::translate('Add pages'));
				$items_title='';
				$items_content='';
				$items_access=1;
				$block_order=Database::prepare(
					"SELECT IFNULL(MAX(block_order)+1, 0) FROM `##block` WHERE module_name=?"
				)->execute(array($this->getName()))->fetchOne();
				$gedcom_id=WT_GED_ID;
			}
			$controller->pageHeader();
			
			if (array_key_exists('ckeditor', WT_Module::getActiveModules())) {
				ckeditor_WT_Module::enableEditor($controller);
			}
			?>
			
			<ol class="breadcrumb small">
				<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
				<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
				<li><a href="module.php?mod=<?php echo $this->getName(); ?>&mod_action=admin_config"><?php echo I18N::translate($this->getTitle()); ?></a></li>
				<li class="active"><?php echo $controller->getPageTitle(); ?></li>
			</ol>

			<form class="form-horizontal" method="POST" action="#" name="pages" id="pagesForm">
				<?php echo Filter::getCsrf(); ?>
				<input type="hidden" name="save" value="1">
				<input type="hidden" name="block_id" value="<?php echo $block_id; ?>">
				<h3><?php echo I18N::translate('General'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="pages_title">
						<?php echo I18N::translate('Title'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="pages_title"
							size="90"
							name="pages_title"
							required
							type="text"
							value="<?php echo Filter::escapeHtml($items_title); ?>"
							>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="pages_content">
						<?php echo I18N::translate('Content'); ?>
					</label>
					<div class="col-sm-9">
						<textarea
							class="form-control html-edit"
							id="pages_content"
							rows="10"
							cols="90"
							name="pages_content"
							required
							type="text">
								<?php echo Filter::escapeHtml($items_content); ?>
						</textarea>
					</div>
				</div>
				
				<h3><?php echo I18N::translate('Languages'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="lang_*">
						<?php echo I18N::translate('Show this page for which languages?'); ?>
					</label>
					<div class="row col-sm-9">
						<?php 
							$accepted_languages=explode(',', get_block_setting($block_id, 'languages'));
							foreach (I18N::installed_languages() as $locale => $language) {
								$checked = in_array($locale, $accepted_languages) ? 'checked' : ''; 
						?>
								<div class="col-sm-3">
									<label class="checkbox-inline "><input type="checkbox" name="lang_<?php echo $locale; ?>" <?php echo $checked; ?> ><?php echo $language; ?></label>
								</div>
						<?php 
							}
						?>
					</div>
				</div>
				
				<h3><?php echo I18N::translate('Visibility and Access'); ?></h3>
				
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo I18N::translate('Pages position'); ?>
					</label>
					<div class="col-sm-9">
						<input
							class="form-control"
							id="position"
							name="block_order"
							size="3"
							required
							type="number"
							value="<?php echo Filter::escapeHtml($block_order); ?>"
						>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo I18N::translate('This field controls the order in which the pages are displayed.'),
							'<br><br>',
							I18N::translate('You do not have to enter the numbers sequentially. If you leave holes in the numbering scheme, you can insert other pages later. For example, if you use the numbers 1, 6, 11, 16, you can later insert pages with the missing sequence numbers. Negative numbers and zero are allowed, and can be used to insert menu items in front of the first one.'),
							'<br><br>',
							I18N::translate('When more than one page has the same position number, only one of these pages will be visible.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="block_order">
						<?php echo I18N::translate('Pages visibility'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo select_edit_control('gedcom_id', Tree::getIdList(), I18N::translate('All'), $gedcom_id, 'class="form-control"'); ?>
					</div>
					<span class="help-block col-sm-9 col-sm-offset-3 small text-muted">
						<?php 
							echo I18N::translate('You can determine whether this page will be visible regardless of family tree, or whether it will be visible only to the current family tree.');
						?>
					</span>
				</div>
				<div class="form-group">
					<label class="control-label col-sm-3" for="pages_access">
						<?php echo I18N::translate('Access level'); ?>
					</label>
					<div class="col-sm-9">
						<?php echo edit_field_access_level('pages_access', $items_access, 'class="form-control"'); ?>
					</div>
				</div>
				
				<div class="row col-sm-9 col-sm-offset-3">
					<button class="btn btn-primary" type="submit">
						<i class="fa fa-check"></i>
						<?php echo I18N::translate('save'); ?>
					</button>
					<button class="btn" type="button" onclick="window.location='<?php echo $this->getConfigLink(); ?>';">
						<i class="fa fa-close"></i>
						<?php echo I18N::translate('cancel'); ?>
					</button>
				</div>
			</form>
<?php
		}
	}

	private function delete() {
		$block_id=Filter::get('block_id');

		Database::prepare(
			"DELETE FROM `##block_setting` WHERE block_id=?"
		)->execute(array($block_id));

		Database::prepare(
			"DELETE FROM `##block` WHERE block_id=?"
		)->execute(array($block_id));
	}

	private function moveUp() {
		$block_id=Filter::get('block_id');

		$block_order=Database::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=Database::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MAX(block_order) FROM `##block` WHERE block_order < ? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function moveDown() {
		$block_id=Filter::get('block_id');

		$block_order=Database::prepare(
			"SELECT block_order FROM `##block` WHERE block_id=?"
		)->execute(array($block_id))->fetchOne();

		$swap_block=Database::prepare(
			"SELECT block_order, block_id".
			" FROM `##block`".
			" WHERE block_order=(".
			"  SELECT MIN(block_order) FROM `##block` WHERE block_order>? AND module_name=?".
			" ) AND module_name=?".
			" LIMIT 1"
		)->execute(array($block_order, $this->getName(), $this->getName()))->fetchOneRow();
		if ($swap_block) {
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($swap_block->block_order, $block_id));
			Database::prepare(
				"UPDATE `##block` SET block_order=? WHERE block_id=?"
			)->execute(array($block_order, $swap_block->block_id));
		}
	}

	private function show() {
		global $controller;
		$items_header_description = '';//Add your own header here.
		$items_id=Filter::get('pages_id');
		$controller=new PageController();
		$controller->setPageTitle(I18N::translate('Resource pages'))//Edit this line for a different summary page title
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
					'<span title="'.str_replace("{@PERC@}", "%", I18N::translate(str_replace("%", "{@PERC@}", $items->pages_title))).'">'.str_replace("{@PERC@}", "%", I18N::translate(str_replace("%", "{@PERC@}", $items->pages_title))).'</span></a></li>';
			}
		}
		$html.='</ul>';
		$html.='<div id="outer_pages_container" style="padding: 1em;">';
		foreach ($items_list as $items) {
			$languages=get_block_setting($items->block_id, 'languages');
			if ((!$languages || in_array(WT_LOCALE, explode(',', $languages))) && $items_id==$items->block_id && $items->pages_access>=WT_USER_ACCESS_LEVEL) {
				$items_content=str_replace("{@PERC@}", "%", I18N::translate(str_replace("%", "{@PERC@}", $items->pages_content)));
			}
		}
		if (isset($items_content)){
			$html.=$items_content;
		} else {
			$html.=I18N::translate('No content found for current access level and language');
		}
		$html.='</div>'; //close outer_pages_container
		$html.='</div>'; //close pages_tabs
		$html.='</div>'; //close pages-container
		$html.='<script>document.onreadystatechange = function () {if (document.readyState == "complete") {$(".pages-accordion").accordion({heightStyle: "content", collapsible: true});}}</script>';
		echo $html;
	}

	private function config() {
		$controller=new PageController();
		$controller->setPageTitle($this->getTitle());
		$controller->pageHeader();

		$items=Database::prepare(
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

		$min_block_order=Database::prepare(
			"SELECT MIN(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();

		$max_block_order=Database::prepare(
			"SELECT MAX(block_order) FROM `##block` WHERE module_name=?"
		)->execute(array($this->getName()))->fetchOne();
		?>
		
		<ol class="breadcrumb small">
			<li><a href="admin.php"><?php echo I18N::translate('Control panel'); ?></a></li>
			<li><a href="admin_modules.php"><?php echo I18N::translate('Module administration'); ?></a></li>
			<li class="active"><?php echo $controller->getPageTitle(); ?></li>
		</ol>
		
		<div class="row">
			<div class="col-sm-4">
				<form class="form form-inline">
					<label for="ged" class="sr-only">
						<?php echo I18N::translate('Family tree'); ?>
					</label>
					<input type="hidden" name="mod" value="<?php echo  $this->getName(); ?>">
					<input type="hidden" name="mod_action" value="admin_config">
					<?php echo select_edit_control('ged', Tree::getNameList(), null, WT_GEDCOM, 'class="form-control"'); ?>
					<input type="submit" class="btn btn-primary" value="<?php echo I18N::translate('show'); ?>">
				</form>
			</div>
			<div class="col-sm-4 text-center">
				<p>
					<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit" class="btn btn-primary">
						<i class="fa fa-plus"></i>
						<?php echo I18N::translate('Add page'); ?>
					</a>
				</p>
			</div>
			<div class="col-sm-4 text-right">		
				<?php // TODO: Move to internal item/page
				if (file_exists(WT_MODULES_DIR.$this->getName().'/readme.html')) { ?>
					<a href="<?php echo WT_MODULES_DIR.$this->getName(); ?>/readme.html" class="btn btn-info">
						<i class="fa fa-newspaper-o"></i>
						<?php echo I18N::translate('ReadMe'); ?>
					</a>
				<?php } ?>
			</div>
		</div>
		
		<table class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th class="col-sm-2"><?php echo I18N::translate('Position'); ?></th>
					<th class="col-sm-3"><?php echo I18N::translate('Title'); ?></th>
					<th class="col-sm-1" colspan=4><?php echo I18N::translate('Controls'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($items as $item): ?>
				<tr>
					<td>
						<?php echo $item->block_order, ', ';
						if ($item->gedcom_id==null) {
							echo I18N::translate('All');
						} else {
							echo Tree::get($item->gedcom_id)->titleHtml();
						} ?>
					</td>
					<td>
						<?php echo Filter::escapeHtml(I18N::translate($item->pages_title)); ?>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_edit&amp;block_id=<?php echo $item->block_id; ?>">
							<div class="icon-edit">&nbsp;</div>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_moveup&amp;block_id=<?php echo $item->block_id; ?>">
							<?php
								if ($item->block_order==$min_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-uarrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_movedown&amp;block_id=<?php echo $item->block_id; ?>">
							<?php
								if ($item->block_order==$max_block_order) {
									echo '&nbsp;';
								} else {
									echo '<div class="icon-darrow">&nbsp;</div>';
								} 
							?>
						</a>
					</td>
					<td class="text-center">
						<a href="module.php?mod=<?php echo $this->getName(); ?>&amp;mod_action=admin_delete&amp;block_id=<?php echo $item->block_id; ?>"
							onclick="return confirm('<?php echo I18N::translate('Are you sure you want to delete this page?'); ?>');">
							<div class="icon-delete">&nbsp;</div>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
<?php
	}

	// Return the list of pages
	private function getPagesList() {
		return Database::prepare(
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
		return Database::prepare(
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
