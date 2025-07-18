<?php

namespace MageOS\UIkitTheme\Plugin\Framework\Deploy;

use Magento\Deploy\Package\Package;
use Magento\Deploy\Service\DeployPackage;
use Magento\Deploy\Service\DeployStaticFile;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

class InsertSvgAssetsOnStaticFilesCompilation
{

    const ICONS_EXTENSION = 'svg';
    const UIKIT_THEME_VENDOR_NAME = 'UIkit';
    const PATH_COMPOSER = "/vendor/mage-os/module-theme-uikit/";
    const PATH_NON_COMPOSER = "/app/design/frontend/Mage-OS/UIkit/";

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
     * InsertSvgAssetsOnStaticFilesCompilation constructor.
     * @param DeployStaticFile $deployStaticFile
     * @param Filesystem\DirectoryList $dir
     * @param Filesystem $filesystem
     * @throws FileSystemException
     */
    public function __construct(
        DeployStaticFile $deployStaticFile,
        Filesystem\DirectoryList                 $dir,
        Filesystem                               $filesystem
    )
    {
        $this->deployStaticFile = $deployStaticFile;
        $this->dir = $dir;
        $this->tmpDir = $filesystem->getDirectoryWrite(DirectoryList::TMP_MATERIALIZATION_DIR);
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
        foreach ($package->getFiles() as $file) {
            $fileId = $file->getDeployedFileId();
            $ext = strtolower(pathinfo($fileId, PATHINFO_EXTENSION));
            if ($ext === self::ICONS_EXTENSION) {
                if (str_contains($file->getOrigPackage()->getTheme(), self::UIKIT_THEME_VENDOR_NAME)) {
                    $path = $this->dir->getRoot() . self::PATH_COMPOSER;
                    if (!file_exists($path)) {
                        $path = $this->dir->getRoot() . self::PATH_NON_COMPOSER;
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
        return [$package, $options, $skipLogging];
    }
}
