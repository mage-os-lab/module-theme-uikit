<?php
namespace MageOS\UIkitTheme\Model\Config;

use Magento\Framework\Component\DirSearch;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Config\FileIteratorFactory;
use Magento\Framework\Module\Dir\Reader;

class FileResolver implements \Magento\Framework\Config\FileResolverInterface
{
    /**
     * Module configuration file reader
     *
     * @var Reader
     */
    protected Reader $_moduleReader;

    /**
     * @var FileIteratorFactory
     */
    protected FileIteratorFactory $iteratorFactory;

    /**
     * @var DirSearch
     */
    private DirSearch $componentDirSearch;

    /**
     * @param Reader $moduleReader
     * @param FileIteratorFactory $iteratorFactory
     * @param DirSearch $componentDirSearch
     */
    public function __construct(
        Reader $moduleReader,
        FileIteratorFactory $iteratorFactory,
        DirSearch $componentDirSearch
    ) {
        $this->iteratorFactory = $iteratorFactory;
        $this->_moduleReader = $moduleReader;
        $this->componentDirSearch = $componentDirSearch;
    }

    /**
     * {@inheritdoc}
     */
    public function get($filename, $scope)
    {
        switch ($scope) {
            case 'global':
                $iterator = $this->_moduleReader->getConfigurationFiles($filename);
                break;
            case 'design':
                $themePaths = $this->componentDirSearch->collectFiles(ComponentRegistrar::THEME, 'etc/' . $filename);
                $iterator = $this->iteratorFactory->create($themePaths);
                break;
            default:
                $iterator = $this->iteratorFactory->create([]);
                break;
        }
        return $iterator;
    }
}
