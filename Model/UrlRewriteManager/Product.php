<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\UrlRewriteManager;

use Hevelop\UrlRewrite\Model\AbstractUrlRewriteManager;
use Hevelop\UrlRewrite\Model\NeedRewriteCategoryProduct;
use Hevelop\UrlRewrite\Model\NeedRewriteCategoryProductFactory;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Hevelop\UrlRewrite\Helper\Data as UrlRewriteHelper;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\Exception\NoSuchEntityException;
use Hevelop\ProductUrlKeyFiller\Model\UrlKeyManager;
use Hevelop\ProductUrlKeyFiller\Helper\ProductAttributes;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogUrlRewrite\Service\V1\StoreViewService;
use Magento\CatalogUrlRewrite\Model\Product\CanonicalUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\Product\CategoriesUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\ObjectRegistryFactory;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Product
 * @package Hevelop\ProductUrlRewriteGenerator\Model\UrlRewriteManager
 */
class Product extends AbstractUrlRewriteManager
{
    /**
     * @var ProductUrlRewriteGenerator
     */
    private $productUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @var UrlKeyManager
     */
    private $urlKeyManager;

    /**
     * @var ProductAttributes
     */
    private $productAttributes;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var StoreViewService
     */
    private $storeViewService;

    /**
     * @var CanonicalUrlRewriteGenerator
     */
    private $canonicalUrlRewriteGenerator;

    /**
     * @var CategoriesUrlRewriteGenerator
     */
    private $categoriesUrlRewriteGenerator;

    /**
     * @var ObjectRegistryFactory
     */
    protected $objectRegistryFactory;

    /**
     * @var NeedRewriteCategoryProductFactory
     */
    protected $needRewriteCategoryProductFactory;

    /**
     * Product constructor.
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteHelper $urlRewriteHelper
     * @param EavConfig $eavConfig
     * @param ProductUrlRewriteGenerator $productUrlRewriteGenerator
     * @param UrlKeyManager $urlKeyManager
     * @param ProductAttributes $productAttributes
     * @param CategoryFactory $categoryFactory
     * @param StoreViewService $storeViewService
     * @param CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator
     * @param CategoriesUrlRewriteGenerator $categoriesUrlRewriteGenerator
     * @param ObjectRegistryFactory $objectRegistryFactory
     * @param NeedRewriteCategoryProductFactory $needRewriteCategoryProductFactory
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        EavConfig $eavConfig,
        ProductUrlRewriteGenerator $productUrlRewriteGenerator,
        UrlKeyManager $urlKeyManager,
        ProductAttributes $productAttributes,
        CategoryFactory $categoryFactory,
        StoreViewService $storeViewService,
        CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator,
        CategoriesUrlRewriteGenerator $categoriesUrlRewriteGenerator,
        ObjectRegistryFactory $objectRegistryFactory,
        NeedRewriteCategoryProductFactory $needRewriteCategoryProductFactory
    ) {
        $this->productUrlRewriteGenerator = $productUrlRewriteGenerator;
        $this->urlKeyManager = $urlKeyManager;
        $this->productAttributes = $productAttributes;
        $this->categoryFactory = $categoryFactory;
        $this->storeViewService = $storeViewService;
        $this->canonicalUrlRewriteGenerator = $canonicalUrlRewriteGenerator;
        $this->categoriesUrlRewriteGenerator = $categoriesUrlRewriteGenerator;
        $this->objectRegistryFactory = $objectRegistryFactory;
        $this->needRewriteCategoryProductFactory = $needRewriteCategoryProductFactory;
        parent::__construct(
            $urlPersist,
            $eavConfig
        );
    }

    /**
     * @param CatalogProduct $product
     * @param array $storeIds
     * @param bool $autoFix
     * @param bool $removeRedirect
     * @return mixed|void
     * @throws LocalizedException
     */
    public function generateAllUrlRewrites(
        $product,
        $storeIds = [],
        $autoFix = false,
        $removeRedirect = false
    ) {
        foreach ($product->getStoreIds() as $storeId) {
            if (!empty($storeIds) && !in_array($storeId, $storeIds)) {
                continue;
            }

            $productCategories = $product->getCategoryCollection();

            $productCategories->clear();
            $productCategories->setStoreId($storeId);
            $productCategories->load();

            $urlKey = $this->productAttributes->getAttributeRawValue(
                $product->getId(),
                'url_key',
                $storeId
            );
            $product->setUrlKey($urlKey);
            try {
                if ($removeRedirect) {
                    $this->buildCanonical($product, $storeId, $productCategories);
                } else {
                    $this->buildAll($product, $storeId);
                }
            } catch (\Exception $e) {
                if ($autoFix === true) {
                    if (!$this->tryToFixUrlAlreadyExist($product, $storeId, $productCategories)) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
        }
    }

    /**
     * @param CatalogProduct $product
     * @param $storeId
     * @param null $productCategories
     * @return bool|mixed
     */
    protected function tryToFixUrlAlreadyExist($product, $storeId, $productCategories = null)
    {
        try {
            $this->urlKeyManager->updateUrlKey($product, false, [$storeId]);
        } catch (\Exception $e) {
            return false;
        }

        // refresh url_key
        $urlKey = $this->productAttributes->getAttributeRawValue(
            $product->getId(),
            'url_key',
            $storeId
        );
        $product->setUrlKey($urlKey);
        try {
            $this->buildAll($product, $storeId);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param $product
     * @param null $storeId
     * @return mixed|void
     * @throws AlreadyExistsException
     */
    public function buildAll($product, $storeId = null)
    {
        $urls = $this->productUrlRewriteGenerator->generate(
            $product
        );
        $this->urlPersist->replace($urls);
    }

    /**
     * @param CatalogProduct $product
     * @param null $storeId
     * @param null $productCategories
     * @return mixed|void
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function buildCanonical($product, $storeId = null, $productCategories = null)
    {
        $categories = [];
        if ($productCategories) {
            foreach ($productCategories as $category) {
                $categories[] = $this->getCategoryWithOverriddenUrlKey($storeId, $category);
            }
        }
        $productCategories = $this->objectRegistryFactory->create(['entities' => $categories]);
        $urls = array_merge(
            $this->canonicalUrlRewriteGenerator->generate($storeId, $product),
            $this->categoriesUrlRewriteGenerator->generate($storeId, $product, $productCategories)
        );
        $this->urlPersist->replace($urls);
    }

    /**
     * Checks if URL key has been changed for provided category and returns reloaded category,
     * in other case - returns provided category.
     *
     * @param $storeId
     * @param Category $category
     * @return CategoryInterface|Category
     * @throws NoSuchEntityException
     */
    private function getCategoryWithOverriddenUrlKey($storeId, Category $category)
    {
        $isUrlKeyOverridden = $this->storeViewService->doesEntityHaveOverriddenUrlKeyForStore(
            $storeId,
            $category->getEntityId(),
            Category::ENTITY
        );

        if (!$isUrlKeyOverridden) {
            return $category;
        }
        $category->setStoreId($storeId);
        return $category;
    }

    /**
     * @param Category $category
     * @throws \Exception
     */
    public function addFlagOnCategoryAffectedProducts(Category $category)
    {
        $affectedProductIds = $category->getAffectedProductIds();
        if (count($affectedProductIds)) {
            try {
                $affectedProductIdsChunked = array_chunk($affectedProductIds, UrlRewriteHelper::CHUNK_SIZE);
                foreach ($affectedProductIdsChunked as $chunk) {
                    /** @var NeedRewriteCategoryProduct $needRewriteCategoryProduct */
                    $needRewriteCategoryProduct = $this->needRewriteCategoryProductFactory->create();
                    $needRewriteCategoryProduct
                        ->setCategoryId($category->getId())
                        ->setProductIds(
                            implode(',', $chunk)
                        );
                    $needRewriteCategoryProduct->save();
                }
            } catch (\Exception $e) {
                throw new \Exception(__($e->getMessage()));
            }
        }
    }
}