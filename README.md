# MageOS UIkit Theme Module

**Set of utilities for the Mage-OS/Magento UIkit theme**

See also [mage-os-lab/theme-frontend-uikit](https://github.com/mage-os-lab/theme-frontend-uikit)

---

## Overview

The **UIkit Theme** module allows svg assets compilation inside static contents, removes unused css UIkit3 classes ( "uk-" prefixed classes) and preload fonts.

## How it works

### Fonts preload

Use "head.asset.preload" layout block to preload fonts and assets inside default_head_blocks.xml files
```
...
<body>
    <referenceBlock name="head.asset.preload">
        <arguments>
            <argument name="assets" xsi:type="array">
                <item name="uikit-icons" xsi:type="array">
                    <item name="path" xsi:type="string">js/uikit/uikit-icons.min.js</item>
                    <item name="type" xsi:type="array">
                        <item name="name" xsi:type="string">type</item>
                        <item name="value" xsi:type="string">script</item>
                    </item>
                    <item name="crossorigin" xsi:type="array">
                        <item name="name" xsi:type="string">crossorigin</item>
                        <item name="value" xsi:type="string">false</item>
                    </item>
                </item>
                <item name="montserrat-light" xsi:type="array">
                    <item name="path" xsi:type="string">fonts/Montserrat/Montserrat-Light.woff2</item>
                    <item name="type" xsi:type="array">
                        <item name="name" xsi:type="string">type</item>
                        <item name="value" xsi:type="string">font/woff2</item>
                    </item>
                    <item name="crossorigin" xsi:type="array">
                        <item name="name" xsi:type="string">crossorigin</item>
                        <item name="value" xsi:type="string">false</item>
                    </item>
                </item>
                ...
            </argument>
        </arguments>
    </referenceBlock>
</body>
```

### UIkit CSS classes safelist

To compile UIkit css classes inside the css files you need to specify file paths to scan into uikit_whitelist.xml files:
```
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:MageOS_Theme:etc/uikit_whitelist.xsd">
    <paths>
        <path name="mage-os_app" xsi:type="string" >app/design/frontend/Mage-OS/UIkit</path>
        <path name="mage-os_vendor" xsi:type="string" >vendor/mage-os/theme-frontend-uikit</path>
    </paths>
</config>
```
Or compile a safelist of classes if any phtml/html/xml file expose them
```
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:MageOS_Theme:etc/uikit_whitelist.xsd">
    <terms>
        <term name="button" xsi:type="string" >uk-button</term>
        <term name="button-primary" xsi:type="string" >uk-button-primary</term>
    </terms>
</config>
```

## Installation

1. Install it into your Mage-OS/Magento 2 project with composer:
```
composer require mage-os/module-theme-uikit
```

2. Enable module
```
bin/magento setup:upgrade
```

Make sure to also install [mage-os-lab/theme-frontend-uikit](https://github.com/mage-os-lab/theme-frontend-uikit)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
