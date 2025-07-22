<?php
namespace MageOS\UIkitTheme\Helper;

class ThemeResolver extends \Magento\Framework\App\Helper\AbstractHelper
{

    const UIKIT_THEME_COMPOSER_PATH = "theme-frontend-uikit";
    const UIKIT_THEME_CODE = "MageOS/UIkit";

    /**
     * @return array
     */
    public function getThemeList($themeList) {
        $resultList = [];
        $uikitTheme = null;
        foreach ($themeList as $theme) {
            if ($theme->getCode() === self::UIKIT_THEME_CODE) {
                $uikitTheme = $theme;
            }
        }
        foreach ($themeList as $theme) {
            if ($theme->getCode() !== self::UIKIT_THEME_CODE) {
                if ($uikitTheme !== null) {
                    if ($theme->getParentId() !== null) {
                        if ($this->isUIkitChild($themeList, $theme, $uikitTheme->getId())) {
                            $theme->setData("is_uikit", true);
                        } else {
                            $theme->setData("is_uikit", false);
                        }
                    }
                } else {
                    $theme->setData("is_uikit", false);
                }
            } else {
                $theme->setData("is_uikit", true);
            }
            $resultList[$theme->getCode()] = $theme;
        }
        return $resultList;
    }

    /**
     * @param $themeList
     * @param $theme
     * @param $uikitThemeId
     * @return bool
     */
    public function isUIkitChild($themeList, $theme, $uikitThemeId) {
        $themeParentId = $theme->getParentId();
        if ($themeParentId !== null) {
            if ($themeParentId === $uikitThemeId) {
                return true;
            }
            foreach ($themeList as $theme) {
                if ($theme->getThemeId() === $themeParentId) {
                    return $this->isUIkitChild($themeList, $theme, $uikitThemeId);
                }
            }
        }
        return false;
    }
}
