<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model;

use Magento\UrlRewrite\Model\UrlPersistInterface;
use Hevelop\UrlRewrite\Helper\Data as UrlRewriteHelper;
use Magento\Catalog\Model\AbstractModel;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class AbstractUrlRewriteManager
 * @package Hevelop\UrlRewrite\Model
 */
abstract class AbstractUrlRewriteManager
{
    /**
     * @var UrlPersistInterface
     */
    protected $urlPersist;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * AbstractUrlRewriteManager constructor.
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteHelper $urlRewriteHelper
     * @param EavConfig $eavConfig
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        EavConfig $eavConfig
    ) {
        $this->urlPersist = $urlPersist;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param $object
     * @param bool $autoFix
     * @param bool $canonicalOnly
     * @return mixed
     */
    abstract public function generateAllUrlRewrites(
        $object,
        $autoFix = false,
        $canonicalOnly = false
    );

    /**
     * @param $object
     * @param $storeId
     * @param null $objectData
     * @return mixed
     */
    abstract protected function tryToFixUrlAlreadyExist(
        $object,
        $storeId,
        $objectData = null
    );

    /**
     * @param $object
     * @param null $storeId
     * @param $objectData
     * @return mixed
     */
    abstract public function buildAll($object, $storeId = null);

    /**
     * @param $object
     * @param null $storeId
     * @param null $objectData
     * @return mixed
     */
    abstract public function buildCanonical($object, $storeId = null, $objectData = null);

    /**
     * @param array $data
     */
    public function deleteByData(array $data)
    {
        $this->urlPersist->deleteByData($data);
    }

    /**
     * @param $object
     * @return int|mixed
     * @throws LocalizedException
     */
    public function entityNeedRewrite($object)
    {
        $needRewriteEntities = 0;
        if (
            $object instanceof AbstractModel &&
            $this->eavConfig->getAttribute(
                $object->getResource()->getEntityType(),
                UrlRewriteHelper::FLAG_ATTRIBUTE
            )
        ) {
            $needRewriteEntities = $object->getData(UrlRewriteHelper::FLAG_ATTRIBUTE);
        }
        return $needRewriteEntities;
    }

    /**
     * @param $object
     * @throws LocalizedException
     */
    public function setEntityAsProcessed($object)
    {
        $object->setData(UrlRewriteHelper::FLAG_ATTRIBUTE, 0);
    }
}