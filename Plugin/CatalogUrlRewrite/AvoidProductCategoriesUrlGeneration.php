<?php

namespace Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite;

use Magento\Catalog\Model\Product;
use Magento\CatalogUrlRewrite\Model\ObjectRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\CatalogUrlRewrite\Model\Product\CategoriesUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\Category\CurrentUrlRewritesRegenerator;
use Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory;
use Hevelop\UrlRewrite\Helper\Data;

class AvoidProductCategoriesUrlGeneration
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @var ObjectRegistryFactory
     */
    private $objectRegistryFactory;

    public function __construct(
        Data $data,
        ObjectRegistryFactory $objectRegistryFactory
    ) {
        $this->data = $data;
        $this->objectRegistryFactory = $objectRegistryFactory;
    }

    /**
     * @param CategoriesUrlRewriteGenerator|CurrentUrlRewritesRegenerator $subject
     * @param int $storeId
     * @param Product $product
     * @param ObjectRegistry $productCategories
     * @return array
     */
    public function beforeGenerate($subject, $storeId, Product $product, ObjectRegistry $productCategories)
    {
        if (!$this->data->shouldGenerateProductInCategoryUrl()) {
            return [$storeId, $product, $this->objectRegistryFactory->create(['entities' => []])];
        }
        return [$storeId, $product, $productCategories];
    }
}