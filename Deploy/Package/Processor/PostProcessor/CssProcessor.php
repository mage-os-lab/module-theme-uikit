<?php
declare(strict_types=1);

namespace MageOS\UIkitTheme\Deploy\Package\Processor\PostProcessor;

use Magento\Deploy\Package\Processor\ProcessorInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Deploy\Package\Package;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\CSSList\CSSList;
use Sabberworm\CSS\Parsing\SourceException;
use Sabberworm\CSS\RuleSet\RuleSet;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use MatthiasMullie\Minify;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use MageOS\UIkitTheme\Model\UIkitWhitelist;

/**
 * Removes every UIkit CSS rule that is NOT referenced in any template found into phtml/xml/html
 * files for the corresponding paths specified inside configuration or specific terms in whitelist.
 */
class CssProcessor implements ProcessorInterface
{
    private $componentsWhitelist = [
        'uk-accordion' => [
            'terms' => [
                'uk-accordion',
                'uk-open',
                'uk-toggable-leave'
            ]
        ],
        'uk-alert' => [
            'terms' => [
                'uk-alert',
                'uk-alert-close',
                'uk-icon',
                'uk-close'
            ]
        ],
        'uk-close' => [
            'terms' => [
                'uk-icon',
                'uk-close'
            ]
        ],
        'uk-countdown' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/countdown.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/countdown.js',
            ],
            'terms' => [
                'uk-countdown'
            ]
        ],
        'uk-drop' => [
            'terms' => [
                'uk-open',
                'uk-drop'
            ]
        ],
        'uk-dropdown' => [
            'terms' => [
                'uk-drop',
                'uk-dropdown'
            ]
        ],
        'uk-dropnav' => [
            'terms' => [
                'uk-dropnav',
                'uk-drop',
                'uk-dropdown'
            ]
        ],
        'uk-drop-parent-icon' => [
            'terms' => [
                'uk-icon',
                'uk-drop-parent-icon'
            ]
        ],
        'uk-filter' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/filter.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/filter.js',
            ],
            'terms' => [
                'uk-filter'
            ]
        ],
        'uk-form-custom' => [
            'terms' => [
                'uk-form-custom'
            ]
        ],
        'uk-grid' => [
            'terms' => [
                'uk-grid',
                'uk-first-column'
            ]
        ],
        'uk-icon' => [
            'terms' => [
                'uk-icon'
            ]
        ],
        'uk-leader' => [
            'terms' => [
                'uk-leader-fill'
            ]
        ],
        'uk-lightbox' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/internal/lightbox-animations.js',
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/lightbox.js',
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/lightbox-panel.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/internal/lightbox-animations.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/lightbox.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/lightbox-panel.js',
            ]
        ],
        'uk-modal' => [
            'terms' => [
                'uk-modal',
                'uk-open',
                'uk-modal-dialog',
                'uk-modal-body',
                'uk-modal-close-default',
                'uk-modal-close-outside',
                'uk-icon',
                'uk-close',
                'uk-modal-title'
            ]
        ],
        'uk-navbar' => [
            'terms' => [
                'uk-navbar'
            ]
        ],
        'uk-notification' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/notification.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/notification.js',
            ]
        ],
        'uk-offcanvas' => [
            'terms' => [
                'uk-offcanvas',
                'uk-offcanvas-bar-animation',
                'uk-offcanvas-slide'
            ]
        ],
        'uk-parallax' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/parallax.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/parallax.js',
            ]
        ],
        'uk-scrollspy' => [
            'terms' => [
                'uk-scrollspy-inview'
            ]
        ],
        'uk-slidenav-next' => [
            'terms' => [
                'uk-icon',
                'uk-slidenav-next',
                'uk-slidenav'
            ]
        ],
        'uk-slidenav-previous' => [
            'terms' => [
                'uk-icon',
                'uk-slidenav-next',
                'uk-slidenav'
            ]
        ],
        'uk-slider' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/slider.js',
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/slider-parallax.js',
                'app/design/theme-frontend-uikit/web/js/uikit/components/internal/slider-preload.js',
                'app/design/theme-frontend-uikit/web/js/uikit/components/internal/slider-transitioner.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/slider.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/slider-parallax.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/internal/slider-preload.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/internal/slider-transitioner.js',
            ]
        ],
        'uk-slideshow' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/slideshow.js',
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/internal/slideshow-animations.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/slideshow.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/internal/slideshow-animations.js',
            ]
        ],
        'uk-sortable' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/sortable.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/sortable.js',
            ]
        ],
        'uk-spinner' => [
            'terms' => [
                'uk-icon',
                'uk-spinner'
            ]
        ],
        'uk-sticky' => [
            'terms' => [
                'uk-sticky',
                'uk-sticky-placeholder'
            ]
        ],
        'uk-svg' => [
            'terms' => [
                'uk-svg'
            ]
        ],
        'uk-tab' => [
            'terms' => [
                'uk-tab'
            ]
        ],
        'uk-tooltip' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/tooltip.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/tooltip.js',
            ],
            'terms' => [
                'uk-tooltip'
            ]
        ],
        'uk-upload' => [
            'paths' => [
                'app/design/frontend/Mage-OS/UIkit/web/js/uikit/components/upload.js',
                'vendor/mage-os/theme-frontend-uikit/web/js/uikit/components/upload.js',
            ]
        ]
    ];

    /**
     * @var DirectoryList
     */
    private DirectoryList $directoryList;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var UIkitWhitelist
     */
    private UIkitWhitelist $uikitWhitelist;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param UIkitWhitelist $uikitWhitelist
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        UIkitWhitelist $uikitWhitelist
    )
    {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->uikitWhitelist = $uikitWhitelist;
    }

    /**
     * @param Package $package
     * @param array $options
     * @return void
     * @throws FileSystemException|SourceException
     */
    public function process(Package $package, array $options): void
    {
        $targetPath = $package->getPath();
        $root = $this->directoryList->getRoot();
        $whitelistConfiguration = $this->uikitWhitelist->collect();
        $templateDirs = [];
        $usedClasses = [];
        foreach ($whitelistConfiguration as $key => $configData) {
            if ($key === "paths") {
                foreach($configData as $name => $path) {
                    $templateDirs[] = $root . '/' . $path;
                }
            }
            if ($key === "terms") {
                foreach($configData as $name => $term) {
                    $usedClasses[] = $term;
                }
            }
        }
        $usedClasses = array_merge($usedClasses, $this->extractUsedClasses($templateDirs));
        foreach ($usedClasses as $key => $class) {
            if (str_contains($class, '@')) {
                $usedClasses[$key] = substr($class, 0, strpos($class, '@'));
            }
            if (str_contains($class, '>')) {
                $usedClasses[$key] = substr($class, 0, strpos($class, '>'));
            }
        }
        foreach (['styles-m','styles-l'] as $base) {
            $cssPath = $root . '/pub/static/' . $targetPath . '/css/' . $base . '.css';
            if ($this->file->isExists($cssPath)) {
                $this->purgeCss($cssPath, $usedClasses);
            }
        }
    }

    /**
     * @param array $paths
     * @return array
     */
    private function extractUsedClasses(array $paths): array
    {
        $found = [];
        foreach ($paths as $p) {
            if (!is_dir($p)) continue;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($p, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!in_array($file->getExtension(), ['phtml', 'xml', 'html'], true)) {
                    continue;
                }
                $content = file_get_contents($file->getPathname());

                if (preg_match_all('/class=["\']([^"\']+)["\']/', $content, $m)) {
                    foreach ($m[1] as $list) {
                        foreach (preg_split('/\s+/', $list) as $cls) {
                            if (str_starts_with($cls, 'uk-')) {
                                $found[$cls] = true;
                            }
                        }
                    }
                }
                if (preg_match_all('/\s(uk-[\w-]+)(?:=["\'][^"\']*["\'])?/', $content, $m2)) {
                    if (!empty($m2[1])) {
                        foreach ($m2[1] as $ukAttribute) {
                            if (str_contains($ukAttribute, "=")) {
                                $ukAttribute = substr($ukAttribute, 0, strpos($ukAttribute, '='));
                            }
                            if (isset($this->componentsWhitelist[$ukAttribute])) {
                                foreach ($this->componentsWhitelist[$ukAttribute] as $section => $values) {
                                    if ($section === "terms") {
                                        foreach ($values as $cls) {
                                            $found[$cls] = true;
                                        }
                                    }
                                    if ($section === "paths") {
                                        foreach ($values as $path) {
                                            $filePath = $this->directoryList->getRoot() . '/' . $path;
                                            if ($this->file->isExists($filePath)) {
                                                $content = file_get_contents($filePath);
                                                if (preg_match_all('/["\']([^"\']*uk-[^"\']*)["\']/', $content, $matches)) {
                                                    foreach ($matches[1] as $attrValue) {
                                                        foreach (preg_split('/\s+/', $attrValue) as $item) {
                                                            if (str_starts_with($item, 'uk-')) {
                                                                $found[$item] = true;
                                                            }
                                                        }
                                                    }
                                                }
                                                if (preg_match_all('/\s(uk-[\w-]+)(?:=(["\'][^"\']*["\'])|\s|>)/', $content, $matches2)) {
                                                    foreach ($matches2[1] as $attrName) {
                                                        $found[$attrName] = true;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
           }
        }
        return array_keys($found);
    }

    /**
     * @param string $file
     * @param array $used
     * @return void
     * @throws SourceException
     */
    private function purgeCss(string $file, array $used): void
    {
        $settings = \Sabberworm\CSS\Settings::create()
            ->withMultibyteSupport(false);
        $doc = (new Parser(file_get_contents($file), $settings))->parse();
        $this->filterCssList($doc, $used);
        $format  = OutputFormat::createCompact();
        $css = $doc->render($format);
        file_put_contents($file, $css);
    }

    /**
     * @param CSSList $list
     * @param array $used
     * @return void
     */
    private function filterCssList(CSSList $list, array $used): void
    {
        foreach ($list->getContents() as $rule) {
            if ($rule instanceof RuleSet) {
                if (!method_exists($rule, 'getSelectors')) {
                    continue;
                }
                $selectors = $rule->getSelectors();
                $selectorsToKeep = [];
                $keepRule = false;
                $hasRoundBracketOpened = false;
                foreach ($selectors as $sel) {
                    $selectorString = $sel->getSelector();
                    if (preg_match_all('/\.uk-[\w-]+/', $selectorString, $matches)) {
                        if (!empty($matches[1])) {
                            $keepRule = true;
                        }
                        foreach ($matches[0] as $dotClass) {
                            $className = substr($dotClass, 1);
                            if (in_array($className, $used, true) || $hasRoundBracketOpened && str_contains($sel->getSelector(), ')')) {
                                $keepRule = true;
                                $selectorsToKeep[] = $sel;
                                if (substr_count($sel->getSelector(), '(') > substr_count($sel->getSelector(), ')')) {
                                    $hasRoundBracketOpened = true;
                                }
                                if (substr_count($sel->getSelector(), '(') < substr_count($sel->getSelector(), ')')) {
                                    $hasRoundBracketOpened = false;
                                }
                                break;
                            }
                        }
                    } else {
                        $selectorsToKeep = $selectors;
                        $keepRule = true;
                    }
                }
                if ($keepRule) {
                    $rule->setSelectors($selectorsToKeep);
                } else {
                    $list->remove($rule);
                }
            } elseif ($rule instanceof AtRuleBlockList) {
                $this->filterCssList($rule, $used);
                if (!$rule->getContents()) {
                    $list->remove($rule);
                }
            }
        }
    }
}
