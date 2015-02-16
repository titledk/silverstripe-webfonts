# SilverStripe Webfonts

Utility/helper for working with web fonts.    
With this module you can easily add local and google fonts via your yaml-configuration,
and they'll automatically be added to site and editor.


## Configuration example

_By default the module expects your fonts to be inside of a `fonts` directory in the web root_

    SiteConfig:
      extensions:
        - WebfontsUtility_SiteConfigExtension
    LeftAndMain:
      extensions:
       #only needed when needing this in the backend
        - WebfontsUtility_LeftAndMainExtension
    WebfontsUtility:
      google_fonts:
        'Devonshire':
        'Bigelow+Rules':
        'Rambla':
          styles: '400,700,700italic'
      local_fonts:
        'Miso':
          directory: 'miso'
          styles: 'Regular,Bold'
          filetypes: 'eot,woff,woff2,ttf,svg'
          Regular:
            filepattern: 'miso-regular-webfont'
            style: 'font-style: normal;'
          Bold:
            filepattern: 'miso-bold-webfont'
            style: 'font-style: bold;'
        'Sansation':
          directory: 'sansation'
          styles: 'Light,Regular,Bold'
          filetypes: 'eot,woff,ttf,svg'
          Light:
            filepattern: 'sansation_light-webfont'
            style: 'font-style: normal;font-weight: 300;'
          Regular:
            filepattern: 'sansation_regular-webfont'
            style: 'font-style: normal;font-weight: 400;'
          Bold:
            filepattern: 'sansation_bold-webfont'
            style: 'font-style: normal;font-weight: 700;'


The requirements can be loaded inside of your `init` method like this:

	WebfontsUtility::Requirements();
	
	//Use this for debugging
	//echo WebfontsUtility::google_font_collection_link();
	//echo WebfontsUtility::debug();

## Future ideas

At the current state fonts are added inline.
This could be improved by making the module auto generate Scss/CSS/LESS files,
that could in turn be included into a compiled stylesheet.