<?php
/**
 * @package plugin.system hyphenateghsvs for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs
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

class PlgSystemHyphenateGhsvs extends CMSPlugin
{
	protected $app;

	protected $autoloadLanguage = true;

	public static $basepath = 'plg_system_hyphenateghsvs';

	protected $require = [];

	protected $fallbacks = [];

	protected $setup = [];

	protected $paths = [];

	protected $handleEvent = [];

	// Stupid but B\C. "uncompressed" removed.
	protected $min = null;

	protected $execute = null;

	// Marker in params to identify myself in back-end.
	private $meMarker = '"hyphenateghsvsplugin":"1"';

	// Usable in other files via PlgSystemHyphenateGhsvs::$isJ3.
	public static $isJ3 = true;

	// Getable in other files via $wa =  PlgSystemHyphenateGhsvs::getWa().
	protected static $wa = null;

	function __construct(&$subject, $config = [])
	{
		// NEIN!!!!!!!!!!!!!!!! Das darfst nicht in __construct!!!!
		#if (Factory::getDocument()->getType() !== 'html')

		parent::__construct($subject, $config);

		self::$isJ3 = version_compare(JVERSION, '4', 'lt');
	}

	public function onBeforeCompileHead()
	{
		if (!$this->goOn())
		{
			return;
		}

		$wa = self::getWa();
		$nonce = PlgHyphenateGhsvsHelper::getNonce($this->app);
		$doc = $this->app->getDocument();
		$version = JDEBUG ? time() : PlgHyphenateGhsvsHelper::getMediaVersion();

		if ($this->params->get('add_hypenate_css', 0))
		{
			if ($wa)
			{
				$wa->useStyle('plg_system_hyphenateghsvs.add_hypenate_css');
			}
			else
			{
				$add_hypenate_css = file_get_contents(
					JPATH_SITE . '/media/' . self::$basepath . '/css/hyphenate.min.css'
				);
				$doc->addCustomTag('<style' . $nonce . '>' . $add_hypenate_css . '</style>');
			}
		}

		$hyphenate     = PlghyphenateghsvsHelper::prepareSelectors($this->params->get('hyphenate', ''));
		$donthyphenate = PlghyphenateghsvsHelper::prepareSelectors($this->params->get('donthyphenate', ''));

		if (!$hyphenate && !$donthyphenate)
		{
			// Stop processing.
			$this->goOn(true, false);

			return;
		}

		// Prepare $this->require and $this->fallbacks
		$hasFound = PlgHyphenateGhsvsHelper::getRequiredAndFallback(
			$this->params,
			$this->require,
			$this->fallbacks
		);

		$hyphenopolyInit = '';

		// Prepare some script snippets that shall be included in $js later on:
		if ($hasFound)
		{
			if ($this->params->get('silenterrors') === 1)
			{
				// "||||" is for removing double quotes in Json later on
				$this->handleEvent['error'] = '||||function (e) {e.preventDefault();}||||';
			}

			$this->paths = [
				'patterndir' => Uri::root(true) . '/media/' . self::$basepath
					. '/js/hyphenopoly/patterns/',

				'maindir' => Path::clean(
					Uri::root(true) . '/media/' . self::$basepath . '/js/hyphenopoly/',
					'/'
				)
			];

			$this->setup['hide'] = $this->params->get('setup_hide', 'all');
			$hyphenopolyInit = $this->getHyphenopolyInit();
			$doc->addCustomTag('<script' . $nonce . ' src="' . $this->getHyphenopolyLink() . '"></script>');
		}

		// Build and include basic JS that adds classes hyphenate and donthyphenate.
		// If an init() script snippet exists include it here, too.
		$js = [];

		if ($wa)
		{
			$wa->useScript('plg_system_hyphenateghsvs.vanilla');
		}
		else
		{
			$file = self::$basepath . '/hyphenateghsvsVanilla' . $this->min . '.js';
			HTMLHelper::_(
				'script',
				$file,
				['relative' => true, 'version' => $version]
			);
		}

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

		$js[] = '});';
		$js[] = $hyphenopolyInit;
		$js = implode('', $js);

		if ($wa)
		{
			// inclusive init for Hyphenopoly_Loader.js.
			$wa->addInline('script', $js, ["name" => "plg_system_hyphenateghsvs.HyphenopolyConfig"]);
		}
		else
		{
			// inclusive init for Hyphenopoly_Loader.js.
			$doc->addScriptDeclaration($js);
		}

		if (!$hasFound)
		{
			// Stop processing.
			$this->goOn(true, false);

			return;
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
			&& !empty($this->usedSubforms)
		) {

			// Subforms with fields to clean by filter="something" when saving.
			$usedSubforms = [
				'languageshyphenopoly' => 'languages-subform-hyphenopoly',
			];

			$do = false;
			$excludeTypes = [
				//'filelist'
			];
			$inputFilter = InputFilter::getInstance();

			foreach ($usedSubforms as $fieldName => $file)
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
			} // foreach $usedSubforms

			if ($do)
			{
				$table->params = $params->toString();
			}
		}
	}

	protected function goOn($refresh = false, $force = null)
	{
		if (is_null($this->execute) || $refresh === true)
		{
			if (
				!$this->app->isClient('site')
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

		$this->min = JDEBUG ? '' : '.min';

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

		$Hyphenoply = json_encode($Hyphenoply, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// Remove quotes around handleEvent functions:
		$Hyphenoply = ';var Hyphenopoly = ' . str_replace(['"||||', '||||"'], '', $Hyphenoply) . ';';

		return $Hyphenoply;
	}

	protected function getHyphenopolyLink()
	{
		return Path::clean(
			Uri::root(true) . '/media/' . self::$basepath
				. '/js/hyphenopoly/Hyphenopoly_Loader' . $this->min . '.js',
			'/'
		);
	}

	public static function getWa()
	{
		if (self::$isJ3 === false && empty(self::$wa))
		{
			self::$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
			self::$wa->getRegistry()->addExtensionRegistryFile('plg_system_hyphenateghsvs');
		}

		return self::$wa;
	}
}
