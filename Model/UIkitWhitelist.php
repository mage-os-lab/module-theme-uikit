<?php
namespace MageOS\UIkitTheme\Model;

use MageOS\UIkitTheme\Model\Config\Reader;

/**
 * Collects policies defined in csp_whitelist.xml configs.
 */
class UIkitWhitelist
{
    /**
     * @var Reader
     */
    private Reader $configReader;

    /**
     * @param Reader $configReader
     */
    public function __construct(Reader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * @inheritDoc
     */
    public function collect(): array
    {
        return $this->configReader->read();
    }
}
