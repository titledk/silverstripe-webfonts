<?php
/**
 * Helper class for working with web fonts,
 * including web fonts, etc.
 * Private statics defined here should be set from config.yml
 * 
 * @author Anselm Christophersen <ac@title.dk>
 * @copyright Copyright (c) 2015, Title Web Solutions
 */
class WebfontsUtility extends Object {
	
	//Google fonts to be used in the project
	private static $google_fonts = array();
	//Local fonts to be used in the project
	private static $local_fonts = array();
	private static $local_fonts_location = 'fonts';


	/**
	 * Font requirements
	 */
	public static function Requirements() {
		self::GoogleFontRequirements();
		self::LocalFontRequirements();

		//refreshing settings cache
		self::write_settings_cache();
	}
	
	
	/**
	 * Creating of the font requirements string based on configured fonts
	 */
	public static function GoogleFontRequirements_string($addLink = true, $addStyles = true){
		$link = 'http://fonts.googleapis.com/css?family=';
		
		$fontsStr = '';
		foreach (self::config()->google_fonts as $font => $settings) {
			if ($addStyles && isset($settings['styles'])) {
				$styles = $settings['styles'];
				$fontsStr .= "$font:$styles|";
			} else {
				$fontsStr .= "$font|";
			}
		}
		$fontsStr = rtrim($fontsStr, '|');
		
		
		//$fontStr = implode('|', self::config()->google_fonts);
		if ($addLink) {
			$fontsStr = $link . $fontsStr;
		}
		
		return $fontsStr;
	}

	/**
	 * Google Font Requirements
	 */
	public static function GoogleFontRequirements(){
		$str = self::GoogleFontRequirements_string();
		Requirements::css($str);
	}

	public static function google_font_collection_link(){
		//http://www.google.com/fonts#UsePlace:use/Collection:Rambla|Dosis|Noto+Serif|Exo|Merriweather|Merriweather+Sans|Gabriela|Titillium+Web		
		$link = 'http://www.google.com/fonts#UsePlace:use/Collection:';
		$fontStr = self::GoogleFontRequirements_string(false);
		
		$str = $link . $fontStr;
		return $str;
	}

	/**
	 * Local Font Requirements
	 * 
	 * TODO:
	 * the filetypes setting in the config is not
	 * being used at the moment, and thus when additional (or fewer file types)
	 * are present, this won't be reflected (e.g. don't we add woff2 file type here) 
	 * 
	 * @param bool $returnString
	 */
	public static function LocalFontRequirements($returnString = false){

		$fontsDir = self::config()->local_fonts_location;
		
		$css = '';
		foreach (self::config()->local_fonts as $font => $settings) {
			$styles = explode(",", $settings['styles']);
			$directory = $settings['directory'];
			foreach ($styles as $style) {
				$styleArr = $settings[$style];
				$filepattern = $styleArr['filepattern'];
				$cssStyle = $styleArr['style'];
				$dir = "/$fontsDir/$directory";
				
				
				$css .= "			
					@font-face {
						font-family: '$font';
						src: url('$dir/$filepattern.eot');
						src: url('$dir/$filepattern.eot?#iefix') format('embedded-opentype'),
							 url('$dir/$filepattern.woff') format('woff'),
							 url('$dir/$filepattern.ttf') format('truetype'),
							 url('$dir/$filepattern.svg#$font') format('svg');
						$cssStyle
					}
				";
			}
		}
		
		if ($returnString) {
			return $css;
		} else {
			Requirements::customCSS($css);
		}
	}

	/**
	 * A list of all available fonts
	 */
	public static function all_fonts($labels = false) {
		$fonts = array();
		
		//adding Google fonts
		foreach(self::config()->google_fonts as $font => $settings) {
			$fonts[] = $font;
		}
		//adding local fonts
		foreach(self::config()->local_fonts as $font => $settings) {
			$fonts[] = $font;
		}
		
		asort($fonts);
		
		if ($labels) {
			return self::labeled_fonts($fonts);
		} else {
			return $fonts;
		}
		
	}

	/**
	 * A list of all enabled fonts
	 * If no enabled settings have been set, this returns all fonts
	 */
	public static function enabled_fonts($labels = false) {
		
		$enabledFonts = null;
		//$sc = SiteConfig::current_site_config();
		//if ($sc && $sc->exists()) {
		//	//$enabledFonts = $sc->EnabledFonts;
		//}
		
		//we can't access SiteConfig at this stage of the initialization,
		//and thus we load it through the cache instead (it's set on WebfontsUtility::Requirements())
		
		
		$cachekey = self::settings_cache_key();
		$cache = SS_Cache::factory($cachekey);
		if ($result = $cache->load($cachekey)) {
			$enabledFonts = unserialize($result);
		}
		
		if ($enabledFonts && strlen($enabledFonts)) {
			//if enabled fonts are set, only return enabled fonts
			$fonts = explode(',', $enabledFonts);

			if ($labels) {
				return self::labeled_fonts($fonts);
			} else {
				return $fonts;
			}
			
			
		} else {
			//if no enabled fonts have been set, return all
			return self::all_fonts($labels);
		}
	}
	
	private static function labeled_fonts($fonts) {
		$labeledFonts = array();

		foreach ($fonts as $font) {
			$fontNice = str_replace('+', ' ', $font);
			$labeledFonts[$font] = $fontNice;
		}
		return $labeledFonts;
	}


	/**
	 * Settings cache key
	 * For now only used for enabled fonts, but could be used for other things
	 */
	public static function settings_cache_key() {
		$cachekey = "WebfontsUtilityEnabledFonts";

		if (isset($_SERVER['SERVER_NAME'])) {
			$server = $_SERVER['SERVER_NAME'];
			$server = preg_replace('/[^A-Za-z0-9]/', '', $server);
			
			$cachekey = $cachekey . "_" . $server;
			
			//Debug::dump($cachekey);
		}
		return $cachekey;
	}
	
	
	/**
	 * Setting font and font size dropdown
	 * Font dropdown is populated with configured fonts
	 * This should be called from _config.php
	 * Unfortunately this means that it's called on all page requests - TODO: this could be alterated with a proper if statement
	 * 
	 * Resources
	 * https://stojg.se/blog/2013-03-29-customize-tinymce-for-silverstripe-cms
	 * http://www.balbuss.com/some-simple-tinymce-editor-settings-in-silverstripe/
	 * 
	 * Gists
	 * http://www.sspaste.com/paste/show/513dfaf026629
	 * https://gist.github.com/colymba/6121825
	 * https://gist.github.com/ryanwachtl/6251297
	 * 
	 * TinyMCE Configuration
	 * http://www.tinymce.com/wiki.php/configuration
	 * 
	 */
	public static function set_html_editor_config() {
		
		//HtmlEditorConfig::get("cms")->setButtonsForLine(3, "fontselect,fontsizeselect");
		HtmlEditorConfig::get("cms")->addButtonsToLine(3, "|,fontselect,fontsizeselect");
		
		//Font dropdown
		//notes: http://maxfoundry.com/blog/how-to-add-google-web-fonts-to-your-tinymce-editor-in-wordpress/
		//http://stackoverflow.com/questions/12247339/how-to-enable-font-family-and-color-options-in-tinymce-editor
		$dropdownList = '';
		
		foreach (self::enabled_fonts() as $font) {
			$fontNice = str_replace('+', ' ', $font);
			$dropdownList .= "$fontNice=$fontNice;";
		}

		//there seems to be an issue with the last not being able to be selected
		//thus, I'm just adding a "default" there
		$dropdownList .= "Default=;";
		
		HtmlEditorConfig::get('cms')->setOption(
			'theme_advanced_fonts', 
			$dropdownList
		);

		//Adding google fonts to the CSS that's rendered in tinyMCE
		$theme = Config::inst()->get('SSViewer', 'theme');
		//we're calling fonts without styles - as the comma in the url messes up with
		//the tinyMCE config
		$googleFonts = WebfontsUtility::GoogleFontRequirements_string(true, false);
		
		HtmlEditorConfig::get('cms')->setOption(
			'content_css', 
			//TypographyUtility::GoogleFontRequirements_string() . ',' . HtmlEditorConfig::get('cms')->getOption('content_css')
			 "$googleFonts, /webfonts/localfonts.css" 
		);
		
	}
	
	
	public static function write_settings_cache() {
		
		//Caching enabled fonts - they need to be accessible at a stage when SiteConfig is not loaded yet
		
		$cachekey = self::settings_cache_key();
		$cache = SS_Cache::factory($cachekey);
		if ($result = $cache->load($cachekey)) {
			$cache->remove($cachekey);
			
		}
		$sc = SiteConfig::current_site_config();
		$cache->save(serialize($sc->EnabledFonts));
	}

}


/**
 * WebfontsUtility_SiteConfigExtension
 * 
 * NOTE that in order to use this, you also need to have
 * https://github.com/silverstripe-australia/silverstripe-multivaluefield installed
 * 
 */
class WebfontsUtility_SiteConfigExtension extends DataExtension {

	static $db = array(
		'EnabledFonts' => 'Text',
	);

	function updateCMSFields(FieldList $fields) {
		
		
		//Setting for restricting available fonts
		//This would mostly be necessary when working with multiple sites
		$fontsField = new CheckboxSetField('EnabledFonts', 'Available Fonts', WebfontsUtility::all_fonts(true));
		$fontsField->setRightTitle('If none are selected, all will be available');
		$fields->addFieldToTab('Root.Main', $fontsField);
	}
	
	function onAfterWrite() {
		WebfontsUtility::write_settings_cache();
	}

}


/**
 * Extension for {@see LeftAndMainExtension} for typography rules
 */
class WebfontsUtility_LeftAndMainExtension extends LeftAndMainExtension {
	
	public function init() {
		//Even though tiymce is laoding the fonts through configuration set in {WebfontsUtility::set_html_editor_config()},
		//they also need to be loaded in the CMS to show proper fonts in the dropdowns - as tinyMCE is loaded in an iframe
		WebfontsUtility::Requirements();
	}
}


/**
 * These urls are available under webfonts, 
 * e.g. /webfonts/localfonts.css
 * 
 * TODO
 * Cache these under assets instead, this way there will be no more need
 * to have all fonts inline in the frontend, and they could even be built through the requirements
 * system
 */
class WebfontsUtility_Controller extends Controller {

	private static $allowed_actions = array(
		'google_fontcollection',
		'localfonts'
	);	
	

	public function localfonts() {
		header("Content-Type: text/css");
		//also see http://stackoverflow.com/questions/5413107/headercontent-type-text-css-is-working-in-ff-cr-but-in-ie9-it-shows-up-as
		echo WebfontsUtility::LocalFontRequirements(true);
		exit;
	}
	
	
	function google_fontcollection() {
		$link = WebfontsUtility::google_font_collection_link();
		header("Location: $link");
		exit;
	}
	
	
}