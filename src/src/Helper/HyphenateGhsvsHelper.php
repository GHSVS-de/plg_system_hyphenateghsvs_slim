<?php
/**
 * @package plugin.system hyphenateghsvs.helper for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim
 */
?>
<?php

namespace Joomla\Plugin\System\HyphenateGhsvs\Helper;

\defined('JPATH_BASE') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\Folder;

class HyphenateGhsvsHelper
{
	protected static $loaded;

	protected static $basepath = 'media/plg_system_hyphenateghsvs';

	protected static $renewalFile;

	protected static $renewalDays;

	public static function prepareSelectors($string)
	{
		if (!trim($string))
		{
			return '';
		}

		$filter = InputFilter::getInstance();
		$string = array_unique(preg_split("/(\n|\r)+/", $filter->clean($string, 'TRIM')));

		if (!$string)
		{
			return '';
		}

		foreach ($string as $key => $selector)
		{
			$string[$key] = str_replace(['"', "'"], '', $filter->clean($selector, 'TRIM'));

			if (!$string[$key])
			{
				unset($string[$key]);
				continue;
			}
		}

		return implode(', ', array_unique($string));
	}

	/**
	 * Prepare/initialise $this->require and $this->fallbacks
	 */
	public static function getRequiredAndFallback($params, &$require, &$fallbacks)
	{
		$languages = $params->get('languageshyphenopoly', null);

		if (\is_object($languages) && \count(get_object_vars($languages)))
		{
			foreach ($languages as $language)
			{
				$language = new Registry($language);

				if (
					$language->get('active', 0)
					&& ($lang = $language->get('lang', ''))
					&& ($langtext = str_replace(' ', '', $language->get('langtext', '')))
				) {
					if ($langTag = trim($language->get('langTag', '')))
					{
						$require[$langTag] = $langtext;
						$fallbacks[$langTag] = $lang;
					}
					else
					{
						$require[$lang] = $langtext;
					}
				}
			}
		}

		$require = array_change_key_case($require, CASE_LOWER);
		$fallbacks = array_change_key_case($fallbacks, CASE_LOWER);

		return !empty($require);
	}

	public static function removeJPATH_SITE($str)
	{
		return str_replace(JPATH_SITE, '', $str);
	}

	public static function getMediaVersion()
	{
		if (!isset(self::$loaded[__METHOD__]))
		{
			self::$loaded[__METHOD__] = json_decode(file_get_contents(
				__DIR__ . '/../../package.json'
			))->version;
		}

		return self::$loaded[__METHOD__];
	}

	/*
	csp_nonce of HTTP Header plugin
	*/
	public static function getNonce($app)
	{
		if (!isset(self::$loaded[__METHOD__]))
		{
			if (self::$loaded[__METHOD__] = $app->get('csp_nonce', ''))
			{
				self::$loaded[__METHOD__] = ' nonce="' . self::$loaded[__METHOD__] . '"';
			}
		}

		return self::$loaded[__METHOD__];
	}

	/*
	At the moment just for adding $version in some cases.
	*/
	public static function cloneAndUseWamAsset(String $type, String $wamName, array $options)
	{
		$wa = PlgSystemHyphenateGhsvs::getWa();
		$war = $wa->getRegistry();
		$asset = $war->get($type, $wamName);
		$war->remove($type, $wamName);
		$war->add(
			$type,
			$war->createAsset(
				$wamName,
				$asset->getUri(false),
				array_merge($asset->getOptions(), $options),
				// $asset->getAttributes(),
				// $asset->getDependencies(),
			)
		);
		$wa->useAsset($type, $wamName);
	}

	public static function renewal($params)
	{
		// Just once per page load.
		if (!isset(self::$loaded['renewalDone']))
		{
			self::$renewalFile = JPATH_SITE . '/' . self::$basepath . '/renewal.log';

			if (self::renewalCheck($params) === true)
			{
				$root = JPATH_SITE . '/' . self::$basepath;
				$forceRenewals = [
					'/js/_byPlugin',
				];

				foreach ($forceRenewals as $item)
				{
					if (is_dir($root . $item))
					{
						Folder::delete($root . $item);
					}

					if (!is_dir($root . $item))
					{
						Folder::create($root . $item);
					}
				}

				file_put_contents(self::$renewalFile, time() + self::$renewalDays);
			}

			self::$loaded['renewalDone'] = 1;
		}
	}

	protected static function renewalCheck($params)
	{
		self::$renewalDays = $params->get('forceRenewalDays', 90) * 24 * 60 * 60;

		// User selected 0.
		if (!self::$renewalDays)
		{
			return true;
		}
		else
		{
			$firstDate = (int) file_get_contents(self::$renewalFile);

			if (!$firstDate || time() > ($firstDate + self::$renewalDays))
			{
				return true;
			}
		}

		return false;
	}
}
