<?php
/**
 * @package plugin.system hyphenateghsvs for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt;  see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim
 */

namespace GHSVS\Plugin\System\HyphenateGhsvs\Field;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class VersionField extends FormField
{
	protected $type = 'Version';

	protected function getInput()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
		->select($db->qn('manifest_cache'))->from($db->qn('#__extensions'))
		->where($db->qn('extension_id') . '='
		. (int) Factory::getApplication()->input->get('extension_id'))
		;
		$db->setQuery($query);

		try
		{
			$manifest = $db->loadResult();
		}
		catch (Exception $e)
		{
			return '';
		}
		$manifest = @json_decode($manifest);
		$version = isset($manifest->version) ? $manifest->version : Text::_('JLIB_UNKNOWN');
		#$date = isset($manifest->date) ? $manifest->date : Text::_('JLIB_UNKNOWN');

		return $version;
	}
}
