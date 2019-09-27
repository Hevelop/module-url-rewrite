<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\UrlRewriteManager;

use Magento\Catalog\Model\Category as CatalogCategory;
use Hevelop\UrlRewrite\Model\AbstractUrlRewriteManager;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\Category\CanonicalUrlRewriteGenerator;
use Magento\CatalogUrlRewrite\Model\Category\CurrentUrlRewritesRegenerator;
use Magento\Catalog\Model\ResourceModel\Category as ResourceModelCategory;
use Magento\Framework\DataObject;
use Magento\Eav\Model\Config as EavConfig;
use Magento\CatalogUrlRewrite\Model\Category\ChildrenCategoriesProvider;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Hevelop\UrlRewrite\Helper\Data as UrlRewriteHelper;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Category
 * @package Hevelop\UrlRewrite\Model\UrlRewriteManager
 */
class Category extends AbstractUrlRewriteManager
{
    /**
     * @var CategoryUrlRewriteGenerator
     */
    private $categoryUrlRewriteGenerator;

    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @var CategoryUrlPathGenerator
     */
    private $categoryUrlPathGenerator;

    /**
     * @var CanonicalUrlRewriteGenerator
     */
    private $canonicalUrlRewriteGenerator;

    /**
     * @var CurrentUrlRewritesRegenerator
     */
    private $currentUrlRewritesRegenerator;

    /**
     * @var ResourceModelCategory
     */
    private $categoryResourceModel;

    /**
     * @var ChildrenCategoriesProvider
     */
    protected $childrenCategoriesProvider;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * Category constructor.
     * @param CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator
     * @param UrlPersistInterface $urlPersist
     * @param EavConfig $eavConfig
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator
     * @param CurrentUrlRewritesRegenerator $currentUrlRewritesRegenerator
     * @param ResourceModelCategory $categoryResourceModel
     * @param ChildrenCategoriesProvider $childrenCategoriesProvider
     * @param CategoryRepository $categoryRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        EavConfig $eavConfig,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator,
        CurrentUrlRewritesRegenerator $currentUrlRewritesRegenerator,
        ResourceModelCategory $categoryResourceModel,
        ChildrenCategoriesProvider $childrenCategoriesProvider,
        CategoryRepository $categoryRepository,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->canonicalUrlRewriteGenerator = $canonicalUrlRewriteGenerator;
        $this->currentUrlRewritesRegenerator = $currentUrlRewritesRegenerator;
        $this->categoryResourceModel = $categoryResourceModel;
        $this->childrenCategoriesProvider = $childrenCategoriesProvider;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct(
            $urlPersist,
            $eavConfig
        );
    }

    /**
     * @param $category
     * @param bool $autoFix
     * @param bool $canonicalOnly
     * @return bool|mixed|void
     * @throws LocalizedException
     */
    public function generateAllUrlRewrites(
        $category,
        $autoFix = false,
        $canonicalOnly = false
    ) {
        $urlKey = $category->getUrlKey();
        $storeId = $category->getStore()->getId();
        $category->setUrlKey($urlKey);
        try {
            if ($canonicalOnly) {
                $this->buildCanonical($category);
            } else {
                $this->buildAll($category);
            }
        } catch (\Exception $e) {
            if ($autoFix === true) {
                if (!$this->tryToFixUrlAlreadyExist($category, $storeId, null)) {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * @param CatalogCategory $category
     * @param $storeId
     * @param null $categoryData
     * @return bool|mixed
     */
    protected function tryToFixUrlAlreadyExist($category, $storeId, $categoryData = null, $canonicalOnly = false)
    {
        $urlKey = $category->getUrlKey() . '-' . $storeId;
        $category->setUrlKey($urlKey);
        $categoryData = [
            'url_key' => $urlKey,
            'url_path' => $this->categoryUrlPathGenerator->getUrlPath($category)
        ];
        try {
            $this->updateCategoryAttributes($category->getRowId(), $categoryData, $storeId);
        } catch (\Exception $e) {
            return false;
        }
        try {
            $this->buildAll($category);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param $category
     * @param null $storeId
     * @return mixed|void
     * @throws AlreadyExistsException
     */
    public function buildAll($category, $storeId = null)
    {
        $storeId = $category->getStoreId();
        $rootCategoryId = $category->getEntityId();
        $urls = array_merge(
            $this->canonicalUrlRewriteGenerator->generate($storeId, $category),
            $this->currentUrlRewritesRegenerator->generate($storeId, $category)
        );
        $this->urlPersist->replace($urls);
    }

    /**
     * @param $category
     * @param null $storeId
     * @param null $categoryData
     * @return mixed|void
     * @throws AlreadyExistsException
     */
    public function buildCanonical($category, $storeId = null, $categoryData = null)
    {
        $storeId = $category->getStoreId();
        $urls = $this->canonicalUrlRewriteGenerator->generate($storeId, $category);
        $this->urlPersist->replace($urls);
    }

    /**
     * @param $rowId
     * @param array $attributesData
     * @param int $storeId
     * @throws \Exception
     */
    public function updateCategoryAttributes($rowId, array $attributesData, $storeId = 0)
    {
        foreach ($attributesData as $attributeCode => $value) {
            $attribute = $this->getAttribute('catalog_category', $attributeCode);
            if ($attribute && $attribute->getId() && !$attribute->isValueEmpty($value)) {
                $object = new DataObject();
                $object->setData('row_id', $rowId);
                $object->setData('store_id', $storeId);
                $object->setData($attributeCode, $value);
                $this->categoryResourceModel->saveAttribute($object, $attributeCode);
            }
        }
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @throws \Exception
     */
    public function addFlagOnCategoryChildren($category)
    {
        $childrenIds = $this->childrenCategoriesProvider->getChildrenIds($category, true);
        $categories = $this->categoryCollectionFactory->create()
            ->addIdFilter($childrenIds)
            ->addAttributeToSelect('name');
        foreach ($categories as $c) {
            if (!$this->entityNeedRewrite($c)) {
                $this->addFlagOnCategory($c);
                $this->categoryRepository->save($c);
            }
        }
    }

    /**
     * @param $category
     * @param int $value
     * @return mixed
     */
    public function addFlagOnCategory($category, $value = 1)
    {
        $category->setData(
            UrlRewriteHelper::FLAG_ATTRIBUTE,
            $value
        );
        return $category;
    }
}