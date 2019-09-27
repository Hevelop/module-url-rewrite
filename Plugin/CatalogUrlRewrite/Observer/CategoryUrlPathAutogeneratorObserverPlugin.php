<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite\Observer;

use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Service\V1\StoreViewService;
use Magento\CatalogUrlRewrite\Observer\CategoryUrlPathAutogeneratorObserver;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Category;
use Hevelop\UrlRewrite\Model\UrlRewriteManager\Category as CategoryUrlRewriteManager;

/**
 * Class CategoryUrlPathAutogeneratorObserverPlugin
 * @package Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite\Observer
 */
class CategoryUrlPathAutogeneratorObserverPlugin
{

    /**
     * @var CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var StoreViewService
     */
    protected $storeViewService;

    /**
     * @var CategoryUrlRewriteManager
     */
    protected $categoryUrlRewriteManager;

    /**
     * CategoryUrlPathAutogeneratorObserverPlugin constructor.
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param StoreViewService $storeViewService
     * @param CategoryUrlRewriteManager $categoryUrlRewriteManager
     */
    public function __construct(
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        StoreViewService $storeViewService,
        CategoryUrlRewriteManager $categoryUrlRewriteManager
    ) {
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->storeViewService = $storeViewService;
        $this->categoryUrlRewriteManager = $categoryUrlRewriteManager;
    }

    /**
     * @param CategoryUrlPathAutogeneratorObserver $subject
     * @param callable $proceed
     * @param Observer|null $observer
     * @throws \Exception
     */
    public function aroundExecute(
        CategoryUrlPathAutogeneratorObserver $subject,
        callable $proceed,
        Observer $observer = null
    ) {
        /** @var Category $category */
        $proceed($observer);
        $category = $observer->getEvent()->getCategory();
        if ($category->getUrlKey() !== false && $category->dataHasChangedFor('url_key')) {
            if (!$this->categoryUrlRewriteManager->entityNeedRewrite($category)) {
                $this->categoryUrlRewriteManager->addFlagOnCategory($category);
            }
            if (!$category->isObjectNew()) {
                $this->categoryUrlRewriteManager->addFlagOnCategoryChildren($category);
            }
        }
    }
}