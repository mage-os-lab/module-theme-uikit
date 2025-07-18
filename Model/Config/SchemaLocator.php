<?php
namespace MageOS\UIkitTheme\Model\Config;

class SchemaLocator implements \Magento\Framework\Config\SchemaLocatorInterface
{
    /**
     * Path to corresponding XSD file with validation rules for merged config
     *
     * @var ?string
     */
    protected ?string $_schema = null;

    /**
     * @param \Magento\Framework\Module\Dir\Reader $moduleReader
     */
    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $etcDir = $moduleReader->getModuleDir(\Magento\Framework\Module\Dir::MODULE_ETC_DIR, 'MageOS_UIkitTheme');
        $this->_schema = $etcDir . '/uikit_whitelist.xsd';
    }

    /**
     * Get path to merged config schema
     *
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return $this->_schema;
    }

    /**
     * @return null
     */
    public function getPerFileSchema()
    {
        return null;
    }
}
