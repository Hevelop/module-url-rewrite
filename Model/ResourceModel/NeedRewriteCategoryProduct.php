<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\ResourceModel;

/**
 * Class NeedRewriteCategoryProduct
 * @package Hevelop\UrlRewrite\Model\ResourceModel
 */
class NeedRewriteCategoryProduct extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Table with category-product relation for url rewrite generation
     *
     * @var string
     */
    protected $_needRewriteCategoryProductNameTable;

    /**
     * Define need url rewrite category-product table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('url_rewrite_category_product', 'row_id');
        $this->_needRewriteCategoryProductNameTable = $this->getTable('url_rewrite_category_product');
    }

    /**
     * @param $object
     * @param $id
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByRowId($object, $id)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['needRewrite' => $this->getMainTable()]
        )->where(
            'needRewrite.row_id = ?',
            $id
        );

        $data = $connection->fetchRow($select);
        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $this;
    }

}
