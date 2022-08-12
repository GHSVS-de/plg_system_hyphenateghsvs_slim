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
<field name="logbuttons" type="plgSystemHyphenateGhsvs.logbuttons" hiddenLabel="true"/>

Inserts Ajax-Buttons for Log File.
*/
defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\PluginHelper;

class plgSystemHyphenateGhsvsFormFieldLogButtons extends FormField
{
	protected $type = 'logbuttons';

	protected $renderLayout = 'ghsvs.renderfield';

	protected $myLayoutPath = 'plugins/system/hyphenateghsvs/layouts';

	protected function getInput()
	{
		$offHint = '';

		if (PluginHelper::isEnabled('system', 'hyphenateghsvs'))
		{
			HTMLHelper::_('behavior.core');
			$files = [
				'buttons-ajax.js',
				'log-buttons.js',
			];

			foreach ($files as $file)
			{
				HTMLHelper::_(
					'script',
					'plg_system_hyphenateghsvs/' . $file,
					[
						'relative' => true,
						'version' => 'auto',
					],
					[
						'defer' => true,
					]
				);
			}

			Factory::getDocument()->addScriptOptions(
				'plg_system_hyphenateghsvs',
				[
					'ajaxError' => Text::sprintf(
						'PLG_SYSTEM_HYPHENATEGHSVS_AJAX_ERROR'
					),
					'bePatient' => Text::sprintf(
						'PLG_SYSTEM_HYPHENATEGHSVS_BE_PATIENT'
					),

				]
			);
		}
		else
		{
			$offHint = Text::_('PLG_SYSTEM_HYPHENATEGHSVS_BUTTONS_INACTIVE');

			return $offHint;
		}

		return '
		<div id=logButtonsContainer>
			<div><button class=showFilePath>'
			. Text::_('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_LOG_FILE_INFOS')
			. '</button></div>
			<div><br><button class=showFile>'
			. Text::_('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_LOG_FILE_SHOW')
			. '</button></div>
			<div><br><button class=deleteFile>'
			. Text::_('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_LOG_FILE_DELETE')
			. '</button></div>
			<div class=ajaxOutput></div>
		</div>';
	}

	public function getLayoutPaths()
	{
		$customPaths = [JPATH_SITE . '/' . $this->myLayoutPath];

		$defaultPaths = new FileLayout('');
		$defaultPaths = $defaultPaths->getDefaultIncludePaths();

		$parentFieldPaths = parent::getLayoutPaths();

		return array_merge($customPaths, $defaultPaths, $parentFieldPaths);
	}
}
