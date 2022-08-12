<?php
/**
 * @package plugin.system hyphenateghsvs for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenator.txt; see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs
 */
/*
GHSVS 2019-02-01
Usage:

Use type="plgSystemHyphenateGhsvs.subformlayout" instead of type="subform"
and hiddenLabel="true"

to get rid of this f**** <div class="controls">

by using custom JLayout plugins/system/hyphenateghsvs/layouts/renderfield.php
*/

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Layout\FileLayout;

FormHelper::loadFieldClass('subform');

class plgSystemHyphenateGhsvsFormFieldSubformLayout extends JFormFieldSubform
{
	protected $type = 'subformlayout';

	protected $renderLayout = 'ghsvs.renderfield';

	protected $myLayoutPath = 'plugins/system/hyphenateghsvs/layouts';

	// Debugge Render-Pfade der Felder-Layouts und Fehler:
	protected $debugLayouts = false;

	/**
	 * Allow to override renderer include paths in child fields
	 *
	 * @return  array
	 *
	 * @since   3.5
	 */
	public function getLayoutPaths()
	{
		$customPaths = [JPATH_SITE . '/' . $this->myLayoutPath];

		$defaultPaths = new FileLayout('');
		$defaultPaths = $defaultPaths->getDefaultIncludePaths();

		$parentFieldPaths = parent::getLayoutPaths();

		return array_merge($customPaths, $defaultPaths, $parentFieldPaths);
	}

	protected function isDebugEnabled()
	{
		return $this->debugLayouts;
	}
}
