<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct;

/**
 * Class Collection
 * @package Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_needRewriteCategoryProductNameTable;

    protected function _construct()
    {
        $this->_init('Hevelop\UrlRewrite\Model\NeedRewriteCategoryProduct', 'Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct');
        $this->_needRewriteCategoryProductNameTable = $this->getTable('url_rewrite_category_product');
        $this->addOrder('category_id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
    }

    /**
     * @param $categoryId
     * @return $this
     */
    public function addCategoryFilter($categoryId)
    {
        if (!empty($categoryId)) {
            if (is_array($categoryId)) {
                $this->addFieldToFilter('main_table.category_id', ['in' => $categoryId]);
            } else {
                $this->addFieldToFilter('main_table.category_id', $categoryId);
            }
        }
        return $this;
    }
}
