<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model;

/**
 * Class NeedRewriteCategoryProduct
 * @package Hevelop\UrlRewrite\Model
 */
class NeedRewriteCategoryProduct extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Hevelop\UrlRewrite\Model\ResourceModel\NeedRewriteCategoryProduct');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProductIds()
    {
        try {
            $productIds = explode(',', $this->getData('product_ids'));
            return $productIds;
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $id
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByRowId($id)
    {
        $this->_getResource()->loadById($this, $id);
        return $this;
    }
}
