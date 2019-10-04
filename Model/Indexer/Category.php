<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\Indexer;

use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Hevelop\UrlRewrite\Model\UrlRewriteManager\Category as CategoryManager;
use Hevelop\UrlRewrite\Model\NeedRewriteCategoryProduct;
use Hevelop\UrlRewrite\Model\NeedRewriteCategoryProductFactory;
use Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct\Collection;
use Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct\CollectionFactory;
use Hevelop\UrlRewrite\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;

/**
 * Class Product
 * @package Hevelop\UrlRewrite\Model\Indexer
 */
class Category implements \Magento\Framework\Indexer\ActionInterface
{
    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var NeedRewriteCategoryProductFactory
     */
    private $needRewriteCategoryProductFactory;

    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'catalog_category_need_url_rewrite';

    /**
     * Category constructor.
     * @param CategoryManager $categoryManager
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepository $categoryRepository
     * @param LoggerInterface $logger
     * @param StoreManager $storeManager
     * @param CollectionFactory $collectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Data $dataHelper
     * @param NeedRewriteCategoryProductFactory $needRewriteCategoryProductFactory
     */
    public function __construct(
        CategoryManager $categoryManager,
        CategoryFactory $categoryFactory,
        CategoryRepository $categoryRepository,
        LoggerInterface $logger,
        StoreManager $storeManager,
        CollectionFactory $collectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        Data $dataHelper,
        NeedRewriteCategoryProductFactory $needRewriteCategoryProductFactory
    ) {
        $this->categoryManager = $categoryManager;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->collectionFactory = $collectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dataHelper = $dataHelper;
        $this->needRewriteCategoryProductFactory = $needRewriteCategoryProductFactory;
    }

    /**
     * @param int[] $ids
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($ids)
    {
        if (empty($ids)) {
            return;
        }
        $ids = array_unique($ids);
        foreach ($ids as $categoryId) {
            $generated = $this->generateCategoryUrl($categoryId);
            if ($generated && $this->dataHelper->shouldGenerateProductInCategoryUrl()) {
                $this->generateProductsOperations($categoryId);
            }
        }
    }

    /**
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function executeFull()
    {
        $this->execute(null);
    }


    /**
     * @param array $ids
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @param int $id
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }

    private function generateCategoryUrl($categoryId)
    {
        try {
            $category = $this->categoryFactory->create();
            $category->load($categoryId);
            if (!$category->getId()) {
                throw NoSuchEntityException::singleField('id', $categoryId);
            }
            if ($this->categoryManager->entityNeedRewrite($category)) {
                foreach ($this->storeManager->getStores() as $store) {
                    $category->setStoreId($store->getId());
                    $category->setSaveRewritesHistory(true);
                    $this->categoryManager->generateAllUrlRewrites($category);
                }
                $this->categoryManager->setEntityAsProcessed($category);
                $this->categoryRepository->save($category);

                return true;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->error(sprintf(
                "Unable to generate urls for the category with id %s: %s",
                $categoryId,
                $e->getMessage()
            ));
        }

        return false;
    }

    private function generateProductsOperations($categoryId)
    {
        try {
            /** @var Collection $operations */
            $operations = $this->collectionFactory->create();
            $operations->addCategoryFilter($categoryId);
            // TODO: what if existing operations involve less products then current?
            if ($operations->count() === 0) {
                $category = $this->categoryFactory->create();
                $category->load($categoryId);
                if (!$category->getId()) {
                    throw NoSuchEntityException::singleField('id', $categoryId);
                }
                $productCollectionTotal = $this->productCollectionFactory
                    ->create()
                    ->addCategoryFilter($category);
                $totalProducts = $productCollectionTotal->count();
                $chunks = ceil($totalProducts / Data::CHUNK_SIZE);
                for ($i = 0; $i < $chunks; $i++) {
                    $productIds = $this->productCollectionFactory
                        ->create()
                        ->addCategoryFilter($category)
                        ->getAllIds(Data::CHUNK_SIZE, $i);

                    /** @var NeedRewriteCategoryProduct $needRewriteCategoryProduct */
                    $needRewriteCategoryProduct = $this->needRewriteCategoryProductFactory->create();
                    $needRewriteCategoryProduct
                        ->setCategoryId($category->getId())
                        ->setProductIds(implode(',', $productIds));
                    $needRewriteCategoryProduct->save();
                }
            }
        } catch (LocalizedException $e) {
            $this->logger->error(sprintf(
                "Unable to generate products urls for the category with id %s: %s",
                $categoryId,
                $e->getMessage()
            ));
        }
    }
}
