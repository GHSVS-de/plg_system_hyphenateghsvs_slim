<?php
/**
 * @package plugin.system hyphenateghsvs for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2020, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim
 */
/**
 * Use in your extension manifest file (any tag is optional!!!!!):
 * <minimumPhp>7.0.0</minimumPhp>
 * <minimumJoomla>3.9.0</minimumJoomla>
 * Yes, use 999999 to match '3.9'. Otherwise comparison will fail.
 * <maximumJoomla>3.10.999999</maximumJoomla>
 * <maximumPhp>7.3.999999</maximumPhp>
 * <allowDowngrades>1</allowDowngrades>
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Log\Log;

class plgSystemHyphenateGhsvsInstallerScript extends InstallerScript
{
	/**
	 * A list of files to be deleted with method removeFiles().
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $deleteFiles = [
		// hpb files replaced with wasm files since version 2020.07.03.
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/hyphenEngine.asm.js',
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/hyphenEngine.wasm',
		'/media/plg_system_hyphenateghsvs/js/backend-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/backend.js',
		'/media/plg_system_hyphenateghsvs/js/buttons-ajax-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/buttons-ajax.js',
		'/media/plg_system_hyphenateghsvs/js/buttons-ajax.min.js',
		'/media/plg_system_hyphenateghsvs/js/hyphenateghsvsVanilla-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/Hyphenator_Loader-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/Hyphenator_Loader.js',
		'/media/plg_system_hyphenateghsvs/js/Hyphenator-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/Hyphenator.js',
		'/media/plg_system_hyphenateghsvs/js/LICENSE.txt',
		'/media/plg_system_hyphenateghsvs/js/log-buttons-uncompressed.js',
		'/media/plg_system_hyphenateghsvs/js/log-buttons.js',
		'/media/plg_system_hyphenateghsvs/js/log-buttons.min.js',
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/Hyphenopoly.min.js',

		'/media/plg_system_hyphenateghsvs/css/backend-uncompressed.css',

		'/plugins/system/hyphenateghsvs/language/de-DE/Cleanup.html',
		'/plugins/system/hyphenateghsvs/language/en-GB/Cleanup.html',
		'/plugins/system/hyphenateghsvs/LICENSE_Hyphenator.txt',
		'/plugins/system/hyphenateghsvs/fields/logbuttons.php',
		'/plugins/system/hyphenateghsvs/helper.php',
	];

	/**
	 * A list of folders to be deleted with method removeFiles().
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $deleteFolders = [
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/_version2.7.0',
		'/media/plg_system_hyphenateghsvs/js/patterns',
		'/plugins/system/hyphenateghsvs/myforms',
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/_hyphenopoly-version',
		'/media/plg_system_hyphenateghsvs/js/hyphenopoly/-uncompressed',
	];

	/**
		* @var SimpleXMLElement
		*/
	protected $previousManifest = null;

	public function __construct()
	{
		$files = [];
		$deletePrefix = '/media/plg_system_hyphenateghsvs/js/hyphenopoly';

		// hpb files replaced with wasm files since version 2020.07.03.
		$patternsPath = JPATH_SITE . $deletePrefix . '/patterns/';

		if (is_dir($patternsPath))
		{
			$files = Folder::files(
				$patternsPath,
				$filter = '\.hpb$'
			);
		}

		foreach ($files as $file)
		{
			$this->deleteFiles[] = $deletePrefix . '/patterns/' . $file;
		}

		$this->deleteFiles[] =
			str_replace(
				JPATH_SITE,
				'',
				Factory::getApplication()->get('log_path') . '/plg_system_hyphenateghsvs-log.txt'
			);
	}

	public function preflight($type, $parent)
	{
		$manifest = @$parent->getManifest();

		if ($manifest instanceof SimpleXMLElement)
		{
			if ($type === 'update' || $type === 'install' || $type === 'discover_install')
			{
				$minimumPhp = trim((string) $manifest->minimumPhp);
				$minimumJoomla = trim((string) $manifest->minimumJoomla);

				// Custom
				$maximumPhp = trim((string) $manifest->maximumPhp);
				$maximumJoomla = trim((string) $manifest->maximumJoomla);

				$this->minimumPhp = $minimumPhp ? $minimumPhp : $this->minimumPhp;
				$this->minimumJoomla = $minimumJoomla ? $minimumJoomla : $this->minimumJoomla;

				if ($maximumJoomla && version_compare(JVERSION, $maximumJoomla, '>'))
				{
					$msg = 'Your Joomla version (' . JVERSION . ') is too high for this extension. Maximum Joomla version is: ' . $maximumJoomla . '.';
					Log::add($msg, Log::WARNING, 'jerror');
				}

				// Check for the maximum PHP version before continuing
				if ($maximumPhp && version_compare(PHP_VERSION, $maximumPhp, '>'))
				{
					$msg = 'Your PHP version (' . PHP_VERSION . ') is too high for this extension. Maximum PHP version is: ' . $maximumPhp . '.';

					Log::add($msg, Log::WARNING, 'jerror');
				}

				if (isset($msg))
				{
					return false;
				}
			}

			if ((int) $manifest->allowDowngrades === 1)
			{
				$this->allowDowngrades = true;
			}
		}

		if (!parent::preflight($type, $parent))
		{
			return false;
		}

		if ($type === 'update')
		{
			$this->removeOldUpdateservers();
		}

		return true;
	}

	public function uninstall($parent)
	{
		$this->removeFiles();
	}

	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @param  string    $type   - Type of PostFlight action. Possible values are:
	 *                           - * install
	 *                           - * update
	 *                           - * discover_install
	 * @param  \stdClass $parent - Parent object calling object.
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
		if ($type === 'update')
		{
			$this->removeFiles();
		}
	}

	/**
	 * Remove the outdated updateservers.
	 *
	 * @return  void
	 *
	 * @since   version after 2019.05.29
	 */
	protected function removeOldUpdateservers()
	{
		$db = Factory::getDbo();

		try
		{
			$query = $db->getQuery(true);
			$query->select('update_site_id')
				->from($db->qn('#__update_sites'))
				->where($db->qn('location') . ' = '
					. $db->q('https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/plg_system_hyphenateghsvs_changelog.xml'), 'OR')
				->where($db->qn('location') . ' = '
					. $db->q('https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/hyphenateghsvs-update.xml'), 'OR')
				->where($db->qn('location') . ' = '
					. $db->q('https://snapshots.ghsvs.de/updates/joomla/plg_system_hyphenateghsvs.xml'), 'OR')
				->where($db->qn('location') . ' = '
					. $db->q('https://raw.githubusercontent.com/GHSVS-de/upadateservers/master/plg_system_hyphenateghsvs-update.xml'), 'OR')
				->where($db->qn('location') . ' = '
					. $db->q('http://snapshots.ghsvs.de/updates/joomla/plg_system_hyphenateghsvs.xml'));

			$ids = $db->setQuery($query)->loadAssocList('update_site_id');

			if (!$ids)
			{
				return;
			}

			$ids = array_keys($ids);
			$ids = implode(',', $ids);

			// Delete from update sites
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' IN (' . $ids . ')')
			)->execute();

			// Delete from update sites extensions
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->qn('#__update_sites_extensions'))
					->where($db->qn('update_site_id') . ' IN (' . $ids . ')')
			)->execute();
		}
		catch (Exception $e)
		{
			return;
		}
	}
}
