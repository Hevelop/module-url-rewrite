<?php

namespace Hevelop\UrlRewrite\Model\Processor;

use Hevelop\UrlRewrite\Model\UrlRewriteManager\Product as ProductManager;
use Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct\CollectionFactory;
use Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct\Collection;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

class CategoryProducts
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductManager
     */
    private $productManager;

    public function __construct(
        CollectionFactory $collectionFactory,
        ProductFactory $productFactory,
        ProductManager $productManager,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productFactory = $productFactory;
        $this->productManager = $productManager;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        $storeIds = array_map(function ($s) {
            return $s->getStoreId();
        }, $this->storeManager->getStores());

        /** @var Collection $operations */
        $operations = $this->collectionFactory->create();
        $operations->setPageSize(10);
        foreach ($operations as $operation) {
            $products = $operation->getProductIds();
            if (empty($products)) {
                $operation->delete();
                continue;
            }
            foreach ($products as $productId) {
                $product = $this->productFactory->create();
                $product->load($productId);
                if (!$product->getId()) {
                    continue;
                }
                $product->setStoreIds($storeIds);
                $product->setSaveRewritesHistory(true);
                $this->productManager->generateAllUrlRewrites($product);
            }
            $operation->delete();
        }
    }
}