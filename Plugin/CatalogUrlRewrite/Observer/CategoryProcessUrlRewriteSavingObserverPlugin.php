<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\CatalogUrlRewrite\Observer\CategoryProcessUrlRewriteSavingObserver;
use Magento\Framework\Event\Observer;
use Magento\Catalog\Model\Category;
use Hevelop\UrlRewrite\Model\UrlRewriteManager\Category as CategoryUrlRewriteManager;
use Hevelop\UrlRewrite\Model\UrlRewriteManager\Product as ProductUrlRewriteManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class CategoryProcessUrlRewriteSavingObserverPlugin
 * @package Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite\Observer
 */
class CategoryProcessUrlRewriteSavingObserverPlugin
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CategoryUrlRewriteManager
     */
    protected $categoryUrlRewriteManager;

    /**
     * @var ProductUrlRewriteManager
     */
    protected $productUrlRewriteManager;

    /**
     * CategoryProcessUrlRewriteSavingObserverPlugin constructor.
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryUrlRewriteManager $categoryUrlRewriteManager
     * @param ProductUrlRewriteManager $productUrlRewriteManager
     */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        CategoryUrlRewriteManager $categoryUrlRewriteManager,
        ProductUrlRewriteManager $productUrlRewriteManager
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->categoryUrlRewriteManager = $categoryUrlRewriteManager;
        $this->productUrlRewriteManager = $productUrlRewriteManager;
    }

    /**
     * @param CategoryProcessUrlRewriteSavingObserver $subject
     * @param callable $proceed
     * @param Observer|null $observer
     * @throws \Exception
     */
    public function aroundExecute(
        CategoryProcessUrlRewriteSavingObserver $subject,
        callable $proceed,
        Observer $observer = null
    ) {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        if ($category->getParentId() == Category::TREE_ROOT_ID) {
            return;
        }
        if ($category->dataHasChangedFor('url_key') || $category->getIsChangedProductList()) {
            $this->categoryUrlRewriteManager->addFlagOnCategory($category);
            $this->productUrlRewriteManager->addFlagOnCategoryAffectedProducts($category);
        }
    }
}