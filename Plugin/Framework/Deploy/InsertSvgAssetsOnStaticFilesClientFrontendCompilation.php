<?php

namespace MageOS\UIkitTheme\Plugin\Framework\Deploy;

use Magento\Deploy\Package\Package;
use Magento\Deploy\Service\DeployPackage;
use Magento\Deploy\Service\DeployStaticFile;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use MageOS\UIkitTheme\Helper\ThemeResolver;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\RequestInterface;
use Magento\Theme\Model\Theme;

class InsertSvgAssetsOnStaticFilesClientFrontendCompilation
{

    const ICONS_EXTENSION = 'svg';
    const UIKIT_THEME_CODE = 'MageOS/UIkit';
    const PATH_NON_COMPOSER = "/app/design/frontend/";

    /**
     * @var DeployStaticFile
     */
    protected DeployStaticFile $deployStaticFile;

    /**
     * @var Filesystem\DirectoryList
     */
    protected Filesystem\DirectoryList $dir;

    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected Filesystem\Directory\WriteInterface $tmpDir;

    /**
     * @var ThemeCollectionFactory
     */
    protected ThemeCollectionFactory $themeCollectionFactory;

    /**
     * @var ThemeResolver
     */
    protected ThemeResolver $themeResolver;

    /**
     * @var ComponentRegistrar
     */
    protected ComponentRegistrar $componentRegistrar;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var File|null
     */
    protected File $driver;

    /**
     * @param DeployStaticFile $deployStaticFile
     * @param Filesystem\DirectoryList $dir
     * @param Filesystem $filesystem
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param ThemeResolver $themeResolver
     * @param ComponentRegistrar $componentRegistrar
     * @param RequestInterface $request
     * @param File|null $driver
     * @throws FileSystemException
     */
    public function __construct(
        DeployStaticFile $deployStaticFile,
        Filesystem\DirectoryList $dir,
        Filesystem $filesystem,
        ThemeCollectionFactory $themeCollectionFactory,
        ThemeResolver $themeResolver,
        ComponentRegistrar $componentRegistrar,
        RequestInterface $request,
        ?File $driver = null
    )
    {
        $this->deployStaticFile = $deployStaticFile;
        $this->dir = $dir;
        $this->filesystem = $filesystem;
        $this->tmpDir = $filesystem->getDirectoryWrite(DirectoryList::TMP_MATERIALIZATION_DIR);
        $this->themeCollectionFactory = $themeCollectionFactory;
        $this->themeResolver = $themeResolver;
        $this->componentRegistrar = $componentRegistrar;
        $this->request = $request;
        $this->driver = $driver ?: ObjectManager::getInstance()->get(File::class);
    }

    /**
     * @param \Magento\Framework\App\StaticResource $subject
     * @return array
     * @throws FileSystemException
     */
    public function beforeLaunch(
        \Magento\Framework\App\StaticResource $subject
    ): array
    {
        $path = $this->request->get('resource');
        $safePath = $this->driver->getRealPathSafety($path);
        $parts = explode('/', $safePath, 6);
        if (count($parts) < 5) {
            //Checking that path contains all required parts and is not above static folder.
            throw new \InvalidArgumentException("Requested path '$path' is wrong.");
        }
        $currentThemePath = $parts[1] . '/' . $parts[2];
        $themeArrayList = $this->themeResolver->getThemeList($this->themeCollectionFactory->create()->getItems());
        if (isset($themeArrayList[self::UIKIT_THEME_CODE]) && isset($themeArrayList[$currentThemePath])) {
            $currentTheme = $themeArrayList[$currentThemePath];
            if ($currentTheme->getData('is_uikit')) {
                $path = self::PATH_NON_COMPOSER . $currentTheme->getThemePath() . '/';
                if (!file_exists($path)) {
                    $registeredThemes = $this->componentRegistrar->getPaths(ComponentRegistrar::THEME);
                    $path = $registeredThemes['frontend' . '/' . $currentTheme->getThemePath()] . '/';
                    $path = str_replace($this->dir->getRoot() . '/', '', $path);
                }
                $destPath = str_replace(
                    $this->dir->getRoot() . '/',
                    '',
                    $this->tmpDir->getAbsolutePath() . $parts[0] . '/' . $parts[1] . '/' . $parts[2] . '/' . $parts[3] . '/css/images'
                );
                $this->copyFolder(
                    $path . 'web/css/images',
                    $destPath
                );
            }
        }
        return [];
    }

    /**
     * @param $sourcePath
     * @param $destinationPath
     * @return void
     */
    private function copyFolder($sourcePath, $destinationPath)
    {
        $writer = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $reader = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);

        $this->recursiveCopy($reader, $writer, $sourcePath, $destinationPath);
    }

    /**
     * @param $reader
     * @param $writer
     * @param $source
     * @param $dest
     * @return void
     */
    private function recursiveCopy($reader, $writer, $source, $dest)
    {
        if (!$writer->isExist($dest)) {
            $writer->create($dest);
        }

        $items = $reader->read($source);

        foreach ($items as $item) {
            $sourceItem = $source . '/' . str_replace($source, '', $item);
            $destItem = $dest . '/' . str_replace($source, '', $item);

            if ($reader->isDirectory($sourceItem)) {
                $this->recursiveCopy($reader, $writer, $sourceItem, $destItem);
            } else {
                $writer->writeFile($destItem, $reader->readFile($sourceItem));
            }
        }
    }
}
