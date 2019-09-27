<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 * @package Hevelop\UrlRewrite\Helper
 */
class Data extends AbstractHelper
{
    const FLAG_ATTRIBUTE = 'need_url_rewrite';
    const CHUNK_SIZE = 500;

    /**
     * @return string
     */
    public function getFlagAttribute()
    {
        return self::FLAG_ATTRIBUTE;
    }

    /**
     * @return mixed
     */
    public function shouldGenerateProductInCategoryUrl() {
        // Magento 2.3
        $value = $this->scopeConfig->getValue('catalog/seo/generate_category_product_rewrites', ScopeInterface::SCOPE_STORE);
        if (is_null($value)) {
            // Magento 2.0+
            $value = $this->scopeConfig->getValue('catalog/seo/product_use_categories', ScopeInterface::SCOPE_STORE);
        }
        return (bool) $value;
    }
}