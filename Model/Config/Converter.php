<?php
namespace MageOS\UIkitTheme\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{

    /**
     * @param $source
     * @return array
     */
    public function convert($source)
    {
        $result = [];
        $xpath = new \DOMXPath($source);
        // Parse paths
        $result['paths'] = [];
        foreach ($xpath->query('/config/paths/path') as $pathNode) {
            $value = trim($pathNode->nodeValue);
            $result['paths'][] = $value;
        }
        // Parse terms
        $result['terms'] = [];
        foreach ($xpath->query('/config/terms/term') as $termNode) {
            $value = trim($termNode->nodeValue);
            $result['terms'][] = $value;
        }
        return $result;
    }
}
