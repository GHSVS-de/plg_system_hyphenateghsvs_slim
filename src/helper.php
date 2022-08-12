<?php
/**
 * @package plugin.system hyphenateghsvs.helper for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenator.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs
 */
?>
<?php
defined('JPATH_BASE') or die;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Registry\Registry;

class PlgHyphenateGhsvsHelper
{
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
	public static function getRequiredAndFallback($isHypenopoly, $params, &$require, &$fallbacks)
	{
		$languagesKey = 'languages';

		if ($isHypenopoly)
		{
			$languagesKey .= 'hyphenopoly';
		}
		else
		{
			if (!isset($required['en']))
			{
				$require['en'] = 'hyphenationalgorithm';
			}
		}

		$languages = $params->get($languagesKey, null);

		if (!empty($languages) && is_object($languages))
		{
			foreach ($languages as $language)
			{
				$language = new Registry($language);

				if (
					$language->get('active', 0)
					&& ($lang = $language->get('lang', ''))
					// B\C Hyphenator:
					&& !is_numeric($lang)
					&& ($langtext = str_replace(' ', '', $language->get('langtext', '')))
				) {
					if ($isHypenopoly && ($langTag = trim($language->get('langTag', ''))))
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

	public static function log($logFile, $data)
	{
		if ($logFile)
		{
			$data = PlgSystemHyphenateGhsvs::removeJPATH_SITE(strip_tags($data));

			$lines = [];

			if (is_file($logFile))
			{
				$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				//$lines = array_map('TRIM', $lines);
				$lines = array_flip($lines);
			}

			if (!isset($lines[$data]))
			{
				$date = '--DATE: ' . date('Y-m-d', time());

				if (!isset($lines[$date]))
				{
					file_put_contents($logFile, $date . "\n", FILE_APPEND);
				}
				file_put_contents($logFile, $data . "\n", FILE_APPEND);
			}
		}
	}
}
