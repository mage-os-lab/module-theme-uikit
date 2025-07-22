<?php

namespace MageOS\UIkitTheme\Plugin\Framework\Deploy;

use Magento\Deploy\Package\Package;
use Magento\Deploy\Service\DeployPackage;
use Magento\Deploy\Service\DeployStaticFile;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory as ThemeCollectionFactory;
use MageOS\UIkitTheme\Helper\ThemeResolver;
use Magento\Framework\Component\ComponentRegistrar;

class InsertSvgAssetsOnStaticFilesCompilation
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
     * @param DeployStaticFile $deployStaticFile
     * @param Filesystem\DirectoryList $dir
     * @param Filesystem $filesystem
     * @param ThemeCollectionFactory $themeCollectionFactory
     * @param ThemeResolver $themeResolver
     * @throws FileSystemException
     */
    public function __construct(
        DeployStaticFile $deployStaticFile,
        Filesystem\DirectoryList $dir,
        Filesystem $filesystem,
        ThemeCollectionFactory $themeCollectionFactory,
        ThemeResolver $themeResolver,
        ComponentRegistrar $componentRegistrar
    )
    {
        $this->deployStaticFile = $deployStaticFile;
        $this->dir = $dir;
        $this->tmpDir = $filesystem->getDirectoryWrite(DirectoryList::TMP_MATERIALIZATION_DIR);
        $this->themeCollectionFactory = $themeCollectionFactory;
        $this->themeResolver = $themeResolver;
        $this->componentRegistrar = $componentRegistrar;
    }

    /**
     * @param DeployPackage $subject
     * @param Package $package
     * @param array $options
     * @param bool $skipLogging
     * @return array
     * @throws FileSystemException
     */
    public function beforeDeployEmulated(
        DeployPackage $subject,
        Package $package,
        array $options,
        bool $skipLogging = false
    ): array
    {
        $themeArrayList = $this->themeResolver->getThemeList($this->themeCollectionFactory->create()->getItems());
        foreach ($package->getFiles() as $file) {
            $fileId = $file->getDeployedFileId();
            $ext = strtolower(pathinfo($fileId, PATHINFO_EXTENSION));
            if ($ext === self::ICONS_EXTENSION) {

                if (isset($themeArrayList[$file->getOrigPackage()->getTheme()])) {
                    $currentTheme = $themeArrayList[$file->getOrigPackage()->getTheme()];
                    if ($currentTheme->getData('is_uikit')) {
                        $path = $this->dir->getRoot() . self::PATH_NON_COMPOSER . $currentTheme->getThemePath() . '/';
                        if (!file_exists($path)) {
                            $registeredThemes = $this->componentRegistrar->getPaths(ComponentRegistrar::THEME);
                            $path = $registeredThemes['frontend' . '/' . $currentTheme->getThemePath()] . '/';
                        }
                        $this->tmpDir->writeFile(
                            $package->getPath() . '/' . $fileId,
                            file_get_contents(
                                $path .
                                \Magento\Framework\UrlInterface::URL_TYPE_WEB . '/' .
                                $file->getDeployedFileName()
                            )
                        );
                    }
                }
            }
        }
        return [$package, $options, $skipLogging];
    }
}
