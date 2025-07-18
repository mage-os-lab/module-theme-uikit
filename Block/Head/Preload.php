<?php

namespace MageOS\UIkitTheme\Block\Head;

use Magento\Framework\View\Element\Context;

/**
 * Class Preload
 * @package MageOS\UIkitTheme\Block\Head
 */
class Preload extends \Magento\Framework\View\Element\AbstractBlock
{
    const PATTERN_ATTRS = ':attributes:';
    const PATTERN_URL   = ':path:';

    /**
     * Preload constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Produce and return block's html output
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        $html = '';
        $assets = $this->getAssets();

        if (empty($assets)) {
            return "\n<!-- Assets Preload: No assets provided -->\n";
        }

        if (!$this->hasLinkTemplate()) {
            return "\n<!-- Assets Preload: No template defined -->\n";
        }

        foreach ($assets as $asset) {
            $attributesHtml = '';
            if (!empty($asset['type'])) {
                $attributesHtml .= sprintf('%s="%s"', $asset['type']['name'], $asset['type']['value']);
            }
            if (!empty($asset['crossorigin'])) {
                $attributesHtml .= sprintf('%s="%s"', $asset['crossorigin']['name'], $asset['crossorigin']['value']);
            }
            if (!empty($asset['attribute'])) {
                $attributesHtml .= sprintf('%s="%s"', $asset['attribute']['name'], $asset['attribute']['value']);
            }
            $assetUrl = $this->_assetRepo->getUrl($asset['path']);
            $html .= $this->renderLinkTemplate($assetUrl, $attributesHtml);
        }

        return $html;
    }

    /**
     * @param $assetUrl
     * @param $additionalAttributes
     * @return string|string[]
     */
    private function renderLinkTemplate($assetUrl, $additionalAttributes): array|string
    {
        return str_replace(
            [self::PATTERN_URL, self::PATTERN_ATTRS],
            [$assetUrl, $additionalAttributes],
            $this->getLinkTemplate()
        );
    }
}
