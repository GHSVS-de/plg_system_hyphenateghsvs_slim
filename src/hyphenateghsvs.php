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
This plugin uses Hyphenator.js - client side hyphenation for webbrowsers.
https://github.com/mnater/Hyphenator
See LICENSE_Hyphenator.txt.
*/
/*
This plugin uses Hyphenopoly.js - client side hyphenation for webbrowsers.
https://github.com/mnater/Hyphenopoly
See LICENSE_Hyphenopoly.txt.
*/
?>
<?php
defined('JPATH_BASE') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/*
Bugfix for Joomla 4, at least with Cassiopeia.
Register helper early. Under some weird circumstances you can get an error
"Class "PlgHyphenateGhsvsHelper" not found".
For example when you remove the snippet
"<jdoc:include type="metas" />"
in template's index.php.
*/
JLoader::register('PlghyphenateghsvsHelper', __DIR__ . '/helper.php');

class PlgSystemhyphenateghsvs extends CMSPlugin
{
	protected $app;

	protected $autoloadLanguage = true;

	protected static $basepath = 'plg_system_hyphenateghsvs';

	protected $require = [];

	protected $fallbacks = [];

	protected $setup = [];

	protected $paths = [];

	protected $handleEvent = [];

	protected $uncompressed = null;

	protected $execute = null;

	protected $isHyphenopoly = null;

	protected $log = null;

	protected $logFile = null;

	protected $cleanup = null;

	protected $cleanupMarker = '<meta name=!hyphenopolyInit! content=!INITHYPHENOPOLYHERE!>';

	protected $prettyPrint = null;

	// Subforms with fields to clean by filter="something" when saving.
	private $usedSubforms = [
		// subformFieldName => xml file (without .xml)
		'languages' => 'languages-subform',
		'languageshyphenopoly' => 'languages-subform-hyphenopoly',
	];

	// Marker in params to identify myself in back-end.
	private $meMarker = '"hyphenateghsvsplugin":"1"';

	// Make csp_nonce of HTTP Header plugin available via JS.
	private $nonce = '';

	public function onAfterDispatch()
	{
		// Set by Http-Header-plugin?
		if ($this->nonce = $this->app->get('csp_nonce', ''))
		{
			$this->nonce = ' nonce="' . $this->nonce . '"';
		}
	}

	public function onAfterRender()
	{
		if ($this->goOn() && $this->cleanup)
		{
			// found lang tags in document body.
			$usedLangsInPage = [];

			// Lang tags that are really required (compare $usedLangsInPage with configuration).
			$required = [];

			$buffer = $this->app->getBody();

			// Fixes DOMDocument::loadHTML(): Argument #1 ($source) must not be empty
			if (empty($buffer))
			{
				$this->execute = false;
				return;
			}

			$Doc = new DOMDocument('1.0', 'UTF-8');
			@$Doc->loadHTML($buffer);
			$xpath = new DOMXPath($Doc);
			$attrib = 'lang';
			$finds = $xpath->query('//*[@' . $attrib . ']');

			foreach ($finds as $find)
			{
				$usedLangsInPage[] = strtolower($find->getAttribute($attrib));
			}
			unset($finds, $find);

			// Reality vs Configuration.
			foreach ($usedLangsInPage as $key => $langTag)
			{
				if (array_key_exists($langTag, $this->require))
				{
					$required[$langTag] = $this->require[$langTag];

					// if !empty afterwards these languagea are not configured.
					unset($usedLangsInPage[$key]);
				}
			}

			// Absolutely no matches.
			if (!$required)
			{
				// Force remove.
				if ($this->cleanup === 2)
				{
					$replaceWith = '';
				}
				// Leave $this->require untouched.
				elseif ($this->cleanup === 1)
				{
					$replaceWith  = '<script' . $this->nonce . '>' . $this->getHyphenopolyInit() . '</script>';
					$replaceWith .= '<script' . $this->nonce . ' src="' . $this->getHyphenopolyLink() . '"></script>';
				}
			}
			else
			{
				$this->require = $required;
				$replaceWith  = '<script' . $this->nonce . '>' . $this->getHyphenopolyInit() . '</script>';
				$replaceWith .= '<script' . $this->nonce . ' src="' . $this->getHyphenopolyLink() . '"></script>';
			}

			// Insert or remove scripts.
			$buffer = str_replace($this->cleanupMarker, $replaceWith, $buffer);
			$this->app->setBody($buffer);

			if ($this->log && ($usedLangsInPage || !$required))
			{
				$uri = Uri::getInstance();
				$uri = $uri->toString(['path', 'query', 'fragment']);

				if ($usedLangsInPage)
				{
					sort($usedLangsInPage);
					$data = Text::sprintf(
						'PLG_HYPHENATEGHSVS_LOG_LANG_TAG_FOUND_BUT_NOT_LOADED',
						implode(', ', $usedLangsInPage),
						$uri
					);
					PlgHyphenateGhsvsHelper::log($this->logFile, $data);
				}

				if (!$required)
				{
					$merged = array_unique(array_merge(array_keys($this->require), array_keys($this->fallbacks)));
					sort($merged);

					$data = Text::sprintf(
						'PLG_HYPHENATEGHSVS_LOG_CLEANUP_RETURNS_EMPTY_REQUIRED',
						implode(', ', $merged),
						$uri,
						$this->cleanup === 2 ? 'Hyphenopoly configuration script removed.' : 'Uncleaned Hyphenopoly configuration script (= like configured by you) inserted.'
					);
					PlgHyphenateGhsvsHelper::log($this->logFile, $data);
				}
			}
		}
	}

	public function onBeforeCompileHead()
	{
		if (!$this->goOn())
		{
			return;
		}

		$doc = $this->app->getDocument();

		if ($this->params->get('add_hypenate_css', 0))
		{
			$add_hypenate_css = file_get_contents(
				JPATH_SITE . '/media/' . self::$basepath . '/css/hyphenate.min.css'
			);
			$doc->addCustomTag('<style' . $this->nonce . '>' . $add_hypenate_css . '</style>');
		}

		$hyphenate     = PlghyphenateghsvsHelper::prepareSelectors($this->params->get('hyphenate', ''));
		$donthyphenate = PlghyphenateghsvsHelper::prepareSelectors($this->params->get('donthyphenate', ''));

		if (!$hyphenate && !$donthyphenate)
		{
			if ($this->log)
			{
				$data = Text::_('PLG_HYPHENATEGHSVS_LOG_COULD_NOT_FIND_ANY_HYPHENATE_SELECTOR');
				PlgHyphenateGhsvsHelper::log($this->logFile, $data);
			}
			$this->goOn(true, false);

			return;
		}

		$version = JDEBUG ? time() : 'auto';
		$vanilla = (int) $this->params->get('vanilla', 0);

		// Prepare $this->require and $this->fallbacks
		$hasFound = PlgHyphenateGhsvsHelper::getRequiredAndFallback(
			$this->isHyphenopoly,
			$this->params,
			$this->require,
			$this->fallbacks
		);

		$hyphenatorLoaderInit = $hyphenopolyInit = '';

		// Prepare some script snippets that shall be included in $js later on:
		if ($this->isHyphenopoly === false)
		{
			// useCSS3hyphenation : true macht keinen Sinn, wenn Hyphenate_Loader sowieso nur in Browsern
			// lädt, die gar kein CSS3hyphenation unterstützen!
			// defaultlanguage : "de", rausgenommen, da unklar.
			$config = json_encode([
				'useCSS3hyphenation' => false,
				'intermediatestate' => 'visible',
				// default:local (bis der Browser schließt), session (Storage pro Fenster)
				// Only for Network debug: none
				// 'storagetype' => 'none',
			]);

			$path = Uri::root(true) . '/media/' . self::$basepath . '/js/Hyphenator' . $this->uncompressed . '.js';
			$hyphenatorLoaderInit = 'Hyphenator_Loader.init(' . json_encode($this->require) . ', "' . $path . '",' . $config . ');';
		}
		elseif ($this->isHyphenopoly === true && $hasFound)
		{
			if ($this->params->get('silenterrors') === 1)
			{
				// "||||" is for removing double quotes in Json later on
				$this->handleEvent['error'] = '||||function (e) {e.preventDefault();}||||';
			}

			$this->paths['patterndir'] =
				Uri::root(true) . '/media/' . self::$basepath . '/js/hyphenopoly/patterns/';
			$this->paths['maindir'] =
				Path::clean(
					Uri::root(true) . '/media/' . self::$basepath . '/js/hyphenopoly/' . $this->uncompressed . '/',
					'/'
				);

			$this->setup['hide'] = $this->params->get('setup_hide', 'all');

			if (!$this->cleanup)
			{
				$hyphenopolyInit = $this->getHyphenopolyInit();
				$doc->addCustomTag('<script src="' . $this->getHyphenopolyLink() . '"></script>');
			}
			else
			{
				$doc->addCustomTag($this->cleanupMarker);
			}
		}

		// Build and include basic JS that adds classes hyphenate and donthyphenate.
		// If an init() script snippet exists include it here, too.
		$js = [];

		if (!$vanilla)
		{
			HTMLHelper::_('jquery.framework');
			$js[] = ';(function($){';
			$js[] = '$(document).ready(function(){';

			if ($hyphenate)
			{
				$js[] = '$("' . $hyphenate . '").addClass("hyphenate");';
			}

			if ($donthyphenate)
			{
				$js[] = '$("' . $donthyphenate . '").addClass("donthyphenate");';
			}

			$js[] = $hyphenatorLoaderInit;
			$js[] = '});';
			$js[] = '})(jQuery);';
			$js[] = $hyphenopolyInit;
		}
		else
		{
			$file = self::$basepath . '/hyphenateghsvsVanilla' . $this->uncompressed . '.js';

			HTMLHelper::_(
				'script',
				$file,
				['relative' => true, 'version' => $version]
			);

			$js[] = ';document.addEventListener("DOMContentLoaded", function(){';

			if ($hyphenate)
			{
				$js[] = 'var selectors = new Hyphenateghsvs("' . $hyphenate . '");';
				$js[] = 'selectors.addClass("hyphenate");';
			}

			if ($donthyphenate)
			{
				$js[] = 'var selectors = new Hyphenateghsvs("' . $donthyphenate . '");';
				$js[] = 'selectors.addClass("donthyphenate");';
			}

			$js[] = $hyphenatorLoaderInit;
			$js[] = '});';
			$js[] = $hyphenopolyInit;
		}
		// inclusive init for already loaded Hyphenator_Loader.js.
		$doc->addScriptDeclaration(implode('', $js));

		if (!$hasFound)
		{
			if ($this->log)
			{
				$data = Text::_('PLG_HYPHENATEGHSVS_LOG_COULD_NOT_FIND_ANY_CONFIGURED_LANGUAGE');
				PlgHyphenateGhsvsHelper::log($this->logFile, $data);
			}
			$this->goOn(true, false);

			return;
		}
		elseif ($this->isHyphenopoly === false)
		{
			$file = self::$basepath . '/Hyphenator_Loader' . $this->uncompressed . '.js';
			HTMLHelper::_(
				'script',
				$file,
				['version' => $version, 'relative' => true]
			);
		}
	}

	public function onExtensionBeforeSave($context, $table, $isNew, $data = [])
	{
		// Sanitize subform fields.
		if (
			$this->app->isClient('administrator')
			&& $context === 'com_plugins.plugin'
			&& !empty($table->params) && is_string($table->params)
			&& strpos($table->params, $this->meMarker) !== false
			//&& $table->enabled
			&& !empty($this->usedSubforms)
		) {
			$do = false;
			$excludeTypes = [
				//'filelist'
			];
			$inputFilter = InputFilter::getInstance();

			foreach ($this->usedSubforms as $fieldName => $file)
			{
				$cleans = [];
				$params = new Registry($table->params);
				$subformData = $params->get($fieldName);
				$file = __DIR__ . '/myforms/' . $file . '.xml';

				if (
					!is_object($subformData) || !count(get_object_vars($subformData))
					|| !is_file($file)
				) {
					continue;
				}

				$subform = new Form('dummy');
				$subform->loadFile($file);
				$xml = $subform->getXml();
				$fieldsAsXMLArray = $xml->xpath('//field[@name=@name and not(ancestor::field/form/*)]');

				foreach ($fieldsAsXMLArray as $field)
				{
					if (in_array((string) $field->attributes()->type, $excludeTypes))
					{
						continue;
					}

					if (!($filter = trim((string) $field->attributes()->filter)))
					{
						$filter = 'string';
					}

					$cleans[(string) $field->attributes()->name] = $filter;
				}

				foreach ($subformData as $key => $item)
				{
					foreach ($item as $property => $value)
					{
						if (array_key_exists($property, $cleans))
						{
							// Special for plg_system_hyphenateghsvs
							if ($property === 'langtext')
							{
								$value = str_replace(' ', '', $value);
							}

							$subformData->$key->$property = $inputFilter->clean($value, $cleans[$property]);
						}
					}
				}
				$params->set($fieldName, $subformData);
				$do = true;
			} // foreach $this->usedSubforms

			if ($do)
			{
				$table->params = $params->toString();
			}
		}
	}

	protected function goOn($refresh = false, $force = null)
	{
		if (is_null($this->isHyphenopoly) || $refresh === true)
		{
			$this->isHyphenopoly = $this->params->get('mode', 'hyphenator') === 'hyphenopoly';
		}

		if (is_null($this->execute) || $refresh === true)
		{
			if (
				!$this->app->isClient('site')
				|| ($this->isHyphenopoly && $this->app->isClient('site')
					&& !$this->params->get('frontendon', 0))
				|| (!$this->params->get('robots', 0) && $this->app->client->robot)
				|| $this->app->getDocument()->getType() !== 'html'
			) {
				$this->execute = false;
			}
			else
			{
				$this->execute = is_bool($force) ? $force : true;
			}
		}

		if (is_null($this->log) || $refresh === true)
		{
			$this->log = $this->execute && $this->isHyphenopoly && $this->params->get('log', 0);
			$this->logFile = $this->log ? $this->getLogFile() : null;
		}

		if (is_null($this->cleanup) || $refresh === true)
		{
			// 0|1|2:
			$this->cleanup =
				$this->execute && $this->isHyphenopoly && $this->params->get('cleanup', 0)
				? (int) $this->params->get('cleanup') : false;
		}

		if (is_null($this->uncompressed) || $refresh === true)
		{
			$this->uncompressed = $this->params->get('uncompressed', '');
		}

		if (is_null($this->prettyPrint) || $refresh === true)
		{
			$this->prettyPrint = ($this->params->get('prettyPrint', 0) || JDEBUG) ? JSON_PRETTY_PRINT : 0;
		}

		return $this->execute;
	}

	protected function getHyphenopolyInit()
	{
		$Hyphenoply = ['require' => $this->require];

		$dos = ['fallbacks', 'paths', 'handleEvent', 'setup'];

		foreach ($dos as $do)
		{
			if ($this->$do)
			{
				$Hyphenoply[$do] = $this->$do;
			}
		}

		$Hyphenoply = json_encode(
			$Hyphenoply,
			$this->prettyPrint
			| JSON_UNESCAPED_SLASHES
			| JSON_UNESCAPED_UNICODE
		);

		// Remove quotes around handleEvent functions:
		$Hyphenoply = ';var Hyphenopoly = ' . str_replace(['"||||', '||||"'], '', $Hyphenoply) . ';';

		return $Hyphenoply;
	}

	protected function getHyphenopolyLink()
	{
		return Path::clean(
			Uri::root(true) . '/media/' . self::$basepath . '/js/hyphenopoly/' . $this->uncompressed . '/Hyphenopoly_Loader.js',
			'/'
		);
	}

	/**
	 * Delete the log file
	*/
	public function onAjaxPlgSystemHyphenateGhsvsDeleteLogFile()
	{
		if (!$this->isAjaxAllowed())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$filePath = $this->getLogFile();

		if (is_file($filePath))
		{
			$deleted = @unlink($filePath);
		}
		else
		{
			$deleted = true;
		}
		$file = self::removeJPATH_SITE($filePath);

		if (!$deleted)
		{
			$html = Text::sprintf('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_FILE_DELETE_ERROR', $file);
		}
		else
		{
			$html = Text::sprintf('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_FILE_DELETE_SUCCESS', $file);
		}
		echo json_encode(['html' => $html]);
	}

	/**
	 * Display content of log file
	*/
	public function onAjaxPlgSystemHyphenateGhsvsShowLogFile()
	{
		if (!$this->isAjaxAllowed())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$filePath = $this->getLogFile();
		$file     = @file_get_contents($filePath);
		$filePath = self::removeJPATH_SITE($filePath);

		if ($file === false || !trim($file))
		{
			$html = Text::sprintf('PLG_SYSTEM_HYPHENATEGHSVS_BUTTON_FILE_SHOW_CONTENT_EMPTY', $filePath);
		}
		else
		{
			$html = '** CONTENT OF FILE ' . $filePath . " **\n\n" . $file;
		}
		echo json_encode(['html' => $html]);
	}

	/**
	 * Show path and size and download of log file
	*/
	public function onAjaxPlgSystemHyphenateGhsvsShowLogFilePath()
	{
		if (!$this->isAjaxAllowed())
		{
			throw new Exception(JText::_('JGLOBAL_AUTH_ACCESS_DENIED'), 403);
		}

		$filesize = 0;
		$file     = $this->getLogFile();

		if (is_file($file))
		{
			$bytes    = filesize($file);
			$filesize = HTMLHelper::_('number.bytes', $bytes);
		}
		$file = self::removeJPATH_SITE($file);

		if (isset($bytes))
		{
			$download = JUri::root() . '/' . ltrim($file, '/');
			$download = '<a href=' . $download . ' target=_blank download>Download</a>';
		}
		echo json_encode(['html' => 'Path: ' . $file . "\nSize: " . $filesize
			. "\nDownload: " . (isset($download) ? $download : 'No file found'), ]);
	}

	private function isAjaxAllowed()
	{
		return
			(strtolower($this->app->input->server->get('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest')
			&& Factory::getUser()->authorise('core.manage');
	}

	private function getLogFile()
	{
		return $this->app->get('log_path') . '/' . self::$basepath . '-log.txt';
	}

	public static function removeJPATH_SITE($str)
	{
		return str_replace(JPATH_SITE, '', $str);
	}
}
