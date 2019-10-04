<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Plugin\CatalogUrlRewrite\Observer;

use Hevelop\UrlRewrite\Helper\Data;
use Magento\Catalog\Model\CategoryRepository;
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
     * @var Data
     */
    private $dataHelper;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * CategoryProcessUrlRewriteSavingObserverPlugin constructor.
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryUrlRewriteManager $categoryUrlRewriteManager
     * @param ProductUrlRewriteManager $productUrlRewriteManager
     * @param Data $dataHelper
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        RequestInterface $request,
        ScopeConfigInterface $scopeConfig,
        CategoryUrlRewriteManager $categoryUrlRewriteManager,
        ProductUrlRewriteManager $productUrlRewriteManager,
        Data $dataHelper,
        CategoryRepository $categoryRepository
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->categoryUrlRewriteManager = $categoryUrlRewriteManager;
        $this->productUrlRewriteManager = $productUrlRewriteManager;
        $this->dataHelper = $dataHelper;
        $this->categoryRepository = $categoryRepository;
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
        if ($category->dataHasChangedFor('url_key') && $category->getIsChangedProductList()) {
            if ($this->dataHelper->shouldGenerateProductInCategoryUrl()) {
                $this->categoryUrlRewriteManager->addFlagOnCategory($category);
                // observer runs after resource save, must save category here
                $this->categoryRepository->save($category);
                $this->productUrlRewriteManager->addFlagOnCategoryAffectedProducts($category);
            }
        }
    }
}
