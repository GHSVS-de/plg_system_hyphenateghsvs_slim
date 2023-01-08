<?php
/**
 * @package plugin.system hyphenateghsvs for Joomla!
 * @version See hyphenateghsvs.xml
 * @author G@HService Berlin Neukölln, Volkmar Volli Schlothauer
 * @copyright Copyright (C) 2016-2019, G@HService Berlin Neukölln, Volkmar Volli Schlothauer. All rights reserved.
 * @license GNU General Public License version 3 or later; see LICENSE.txt; see also LICENSE_Hyphenopoly.txt
 * @authorUrl https://www.ghsvs.de
 * @link https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim
 */
/*
This plugin uses Hyphenopoly.js - client side hyphenation for webbrowsers.
https://github.com/mnater/Hyphenopoly
See LICENSE_Hyphenopoly.txt.
*/
?>
<?php
defined('JPATH_BASE') or die;

if (version_compare(JVERSION, '4', 'lt'))
{
	JLoader::registerNamespace(
		'GHSVS\Plugin\System\HyphenateGhsvs',
		__DIR__ . '/src',
		false,
		false,
		'psr4'
	);
}

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use GHSVS\Plugin\System\HyphenateGhsvs\Helper\HyphenateGhsvsHelper;
use Joomla\Registry\Registry;

class PlgSystemHyphenateGhsvs extends CMSPlugin
{
	protected $app;

	public static $basepath = 'plg_system_hyphenateghsvs';

	// Configured and active languages.
	protected $require = [];

	// Configured associated language fallbacks.
	protected $fallbacks = [];

	protected $setup = [];

	// HyphonopolyJS paths. E.g. mainDir.
	protected $paths = [];

	// HyphonopolyJS event functions.
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
		$nonce = HyphenateGhsvsHelper::getNonce($this->app);
		$doc = $this->app->getDocument();
		$version = JDEBUG ? time() : HyphenateGhsvsHelper::getMediaVersion();
		$combinedJs = [];

		// Always load basic CSS with .hypenate/.donthyphenate rules if user selected.
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

		// Did user configure any selectors?
		$hyphenate     = HyphenateGhsvsHelper::prepareSelectors($this->params->get('hyphenate', ''));
		$donthyphenate = HyphenateGhsvsHelper::prepareSelectors($this->params->get('donthyphenate', ''));

		if (!$hyphenate && !$donthyphenate)
		{
			// Stop processing.
			$this->goOn(true, false);

			return;
		}

		// Load always to insert hyphenate/donthyphenate classes in HTML.
		$combinedJs[] = file_get_contents(JPATH_SITE . '/media/' . self::$basepath
			. '/js/hyphenateghsvsVanilla.min.js');
		$js = ';document.addEventListener("DOMContentLoaded", function(){';

		if ($hyphenate)
		{
			$js .= 'var selectors = new Hyphenateghsvs("' . $hyphenate . '");';
			$js .= 'selectors.addClass("hyphenate");';
		}

		if ($donthyphenate)
		{
			$js .= 'var selectors = new Hyphenateghsvs("' . $donthyphenate . '");';
			$js .= 'selectors.addClass("donthyphenate");';
		}

		$js .= '})';
		$combinedJs[] = $js;

		// Clean folder /_byPlugin? There are the Hyphonopoly configuration JS files.
		HyphenateGhsvsHelper::renewal($this->params);

		// Collect configured languages and associated fallbacks.
		// if no languages: FALSE.
		$hasFound = HyphenateGhsvsHelper::getRequiredAndFallback(
			$this->params,
			$this->require,
			$this->fallbacks
		);

		// Prepare some script snippets that shall be included in $js later on:
		if ($hasFound) {
			// Suppress Hyphonopoly console messages.
			if ($this->params->get('silenterrors') === 1) {
				// "||||" is for removing double quotes in Json later on
				$this->handleEvent['error'] = '||||function (e) {e.preventDefault();}||||';
			}

			$this->paths = [
				'patterndir' => Uri::root(true) . '/media/' . self::$basepath
					. '/js/hyphenopoly/patterns/',

				'maindir' => Path::clean(
					Uri::root(true) . '/media/' . self::$basepath . '/js/hyphenopoly/',
					'/'
				),
			];

			$this->setup['hide'] = $this->params->get('setup_hide', 'all');

			// @since hyphenopoly 5.0.0 Loader must be loaded before Hyphenopoly.config().
			$combinedJs[] = file_get_contents(JPATH_SITE . '/media/' . self::$basepath
				. '/js/hyphenopoly/Hyphenopoly_Loader.min.js');

			$combinedJs[] = $this->getHyphenopolyInit();
		}

		$combinedJs = implode(';', $combinedJs);
		$wamName = self::$basepath . 'Config';

		// loadInline: Not implemented yet.
		if (!($loadInline = $this->params->get('loadInline', 0))) {
			$configFile = '_byPlugin/hyphenopolyInit-'
				. hash('sha256', $combinedJs) . '.js';
			$configFileAbs = JPATH_SITE . '/media/' . self::$basepath  . '/js/'
				. $configFile;

			if (!is_file($configFileAbs)) {
				file_put_contents($configFileAbs, $combinedJs);
			}

			$file = self::$basepath . '/' . $configFile;

			if ($wa) {
				$wa->registerAndUseScript($wamName, $file, ['version' => $version], ['id' => $wamName, 'defer' => true]);
			} else {
				HTMLHelper::_('script', $file, ['relative' => true, 'version' => $version]);
			}
		} else {
			if ($wa) {
				$wa->addInline('script', $combinedJs, ['name' => $wamName], ['id' => $wamName]);
			} else {
				$doc->addScriptDeclaration($combinedJs);
		}
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
				$file = __DIR__ . '/src/Form/' . $file . '.xml';

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
		// Languages configuration JS snippet.
		$Hyphenoply = ['require' => $this->require];

		// Other configuration JS snippets.
		$dos = ['fallbacks', 'paths', 'handleEvent', 'setup'];

		foreach ($dos as $do)
		{
			if ($this->$do)
			{
				$Hyphenoply[$do] = $this->$do;
			}
		}

		$Hyphenoply = json_encode($Hyphenoply, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		// @since hyphenopoly v5.0.0. Use Hyphenopoly.config() instead.
		//$Hyphenoply = ';var Hyphenopoly = ' . str_replace(['"||||', '||||"'], '', $Hyphenoply);
		// Why str_replace? A:Remove quotes around handleEvent functions:
		$Hyphenoply = ';Hyphenopoly.config('
			. str_replace(['"||||', '||||"'], '', $Hyphenoply) . ')';
		return $Hyphenoply;
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
