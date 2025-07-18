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
        DirectoryList   $directoryList,
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
        foreach (['styles-m', 'styles-l'] as $base) {
            $cssPath = $root . '/pub/static/' . $targetPath . '/css/' . $base . '.css';
            if ($this->file->isExists($cssPath)) {
                $this->purgeCss($cssPath, $usedClasses);
                (new Minify\CSS($cssPath))->minify($cssPath);
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
                if (!in_array($file->getExtension(), ['phtml', 'xml', 'html'], true)) continue;
                if (preg_match_all('/class=["\']([^"\']+)["\']/', file_get_contents($file->getPathname()), $m)) {
                    foreach ($m[1] as $list) {
                        foreach (preg_split('/\s+/', $list) as $cls) {
                            if (str_starts_with($cls, 'uk-')) $found[$cls] = true;
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
        $css = preg_replace_callback('/[^\x00-\x7F]/u', function ($matches) {
            $char = $matches[0];
            $utf16 = mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
            $hex = strtoupper(bin2hex($utf16));
            return '\\' . ltrim($hex, '0') . ' ';
        }, $css);
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
                $newSelectors = [];
                foreach ($selectors as $sel) {
                    $selectorString = $sel->getSelector();
                    if (preg_match_all('/\.uk-[\w-]+/', $selectorString, $matches)) {
                        $allUsed = true;
                        foreach ($matches[0] as $dotClass) {
                            $className = substr($dotClass, 1);
                            if (!in_array($className, $used, true)) {
                                $allUsed = false;
                                break;
                            }
                        }
                        if ($allUsed) {
                            $newSelectors[] = $sel;
                        }
                    } else {
                        $newSelectors[] = $sel;
                    }
                }
                if (empty($newSelectors)) {
                    $list->remove($rule);
                } else {
                    $rule->setSelectors($newSelectors);
                }
            } elseif ($rule instanceof AtRuleBlockList) {
                $this->filterCssList($rule, $used);
                if (!$rule->getContents()) $list->remove($rule);
            }
        }
    }
}
