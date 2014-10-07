<?php
/**
 * Helper class for working with typography,
 * including web fonts, etc.
 * Private statics defined here should be set from config.yml
 * 
 * @author Anselm Christophersen <ac@title.dk>
 * @copyright Copyright (c) 2014, Title Web Solutions
 */
class TypographyUtility extends Object {
	
	//Google fonts to be used in the project
	private static $google_fonts = array();
	//Local fonts to be used in the project
	private static $local_fonts = array();
	private static $local_fonts_location = 'fonts';
	
	
	public static function debug(){
		Debug::dump(self::config()->google_fonts);
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
	 * Setting the html editor config to conform with this utility
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
		
		//Altering styles dropdown
		//It looks like we won't need the styles dropdown in this project
		HtmlEditorConfig::get('cms')->removeButtons('styleselect');
		//		HtmlEditorConfig::get('cms')->setOption(
		//			'theme_advanced_styles', 
		//			'Red text=red;Blue text=blue;Green text=green;No style=default;'
		//		);
		
		//Font dropdown
		//notes: http://maxfoundry.com/blog/how-to-add-google-web-fonts-to-your-tinymce-editor-in-wordpress/
		//http://stackoverflow.com/questions/12247339/how-to-enable-font-family-and-color-options-in-tinymce-editor
		$dropdownList = '';
		//google fonts
		foreach(self::config()->google_fonts as $font => $settings) {
			$fontNice = str_replace('+', ' ', $font);
			$dropdownList .= "$fontNice=$fontNice;";
		}
		//local fonts
		foreach(self::config()->local_fonts as $font => $settings) {
			$fontNice = str_replace('+', ' ', $font);
			$dropdownList .= "$fontNice=$fontNice;";
		}
		//there seems to be an issue with the last not being able to be selected
		//thus, I'm just adding a "default" there
		$dropdownList .= "Default=;";
		
		HtmlEditorConfig::get('cms')->setOption(
			'theme_advanced_fonts', 
			$dropdownList
			//'Default=;Rambla=Rambla, sans-serif;Dosis=Dosis, sans-serif;Noto Serif=Noto Serif, serif;Exo=Exo, sans-serif;Merriweather=Merriweather, serif;Merriweather Sans=Merriweather Sans, sans-serif;' .
			//'Gabriela=Gabriela, serif;Titillium=Titillium Web, sans-serif;'
		);

		// add a button to remove formatting
		HtmlEditorConfig::get('cms')->insertButtonsBefore(
			'styleselect',
			'removeformat'
		);

		//Adding google fonts to the CSS that's rendered in tinyMCE
		$theme = Config::inst()->get('SSViewer', 'theme');
		//we're calling fonts without styles - as the comma in the url messes up with
		//the tinyMCE config
		$googleFonts = TypographyUtility::GoogleFontRequirements_string(true, false);
		
		HtmlEditorConfig::get('cms')->setOption(
			'content_css', 
			//TypographyUtility::GoogleFontRequirements_string() . ',' . HtmlEditorConfig::get('cms')->getOption('content_css')
			 "$googleFonts, /typography/localfonts.css, themes/$theme/css/editor.css" 
		);		
		
	}
	
	/**
	 * Basic html editor config
	 * See https://stojg.se/blog/2013-03-29-customize-tinymce-for-silverstripe-cms
	 * 
	 */
	public static function set_basic_html_editor_config(){
		//disabled for now, see set_html_editor_configs
//		HtmlEditorConfig::get("basic")->setOptions(array(
//				"friendly_name" => "basic editor",
//				"priority" => 0,
//				"mode" => "none",
//				"editor_selector" => "htmleditor",
//				"auto_resize" => true,
//				"theme" => "advanced",
//				"skin" => "default",
//				// Remove the bottom status bar
//				"theme_advanced_statusbar_location" => "none"
//		));
//		// Clear the default buttons
//		HtmlEditorConfig::get("basic")->setButtonsForLine(1, array());
//		HtmlEditorConfig::get("basic")->setButtonsForLine(2, array());
//		HtmlEditorConfig::get("basic")->setButtonsForLine(3, array());
//		// Add the buttons you would like to add, see http://www.tinymce.com/wiki.php/buttons/controls for a comprehensive list 
//		HtmlEditorConfig::get("basic")->setButtonsForLine(1, "bold", "italic");		
	}

	/**
	 * Setting all html editor configs
	 */
	public static function set_html_editor_configs(){
		TypographyUtility::set_html_editor_config();
		//TypographyUtility::set_basic_html_editor_config();
		
		//we're also using a module to accomplish this (silverstripe-customhtmleditorfield)
		//see https://github.com/nathancox/silverstripe-customhtmleditorfield/wiki
		$basicConfig = CustomHtmlEditorConfig::copy('basic', 'cms');
		$basicConfig->setOption('friendly_name', 'Basic');
		$basicConfig->setButtonsForLine(2, array());
		$basicConfig->setButtonsForLine(3, array());
	}

	
	
}


/**
 * Extension for {@see LeftAndMainExtension} for typography rules
 */
class TypographyUtility_LeftAndMainExtension extends LeftAndMainExtension {
	
	public function init() {
		//Even though tiymce is laoding the fonts through configruation set in {TypographyUtility::set_html_editor_config()},
		//they also need to be loaded in the CMS to show proper fonts in the dropdowns - as tinyMCE is loaded in an iframe
		TypographyUtility::GoogleFontRequirements();
		TypographyUtility::LocalFontRequirements();

	}
}


/**
 * Note (7th october 2014):
 * I'm not so sure this is needed anymore - as it's taken care of 
 * via 
 */
class TypographyUtility_Controller extends Controller {

	private static $allowed_actions = array(
		'google_fontcollection',
		'localfonts'
	);	
	
	
	
	public function index(){
		//echo 'sdfdsf';
	}
	
	public function localfonts() {
		header("Content-Type: text/css");
		//also see http://stackoverflow.com/questions/5413107/headercontent-type-text-css-is-working-in-ff-cr-but-in-ie9-it-shows-up-as
		echo TypographyUtility::LocalFontRequirements(true);
		exit;
	}
	
	
	function google_fontcollection() {
		$link = TypographyUtility::google_font_collection_link();
		header("Location: $link");
		exit;
	}
	
	
}