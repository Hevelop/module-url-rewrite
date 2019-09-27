<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Model\Indexer\Mview;

use Magento\Framework\Mview\ActionInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Hevelop\UrlRewrite\Model\Indexer\Category as UrlRewriteCategory;

/**
 * Class Action
 * @package Hevelop\UrlRewrite\Model\Indexer\Mview
 */
class Action implements ActionInterface
{
    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @param IndexerInterfaceFactory $indexerFactory
     */
    public function __construct(
        IndexerInterfaceFactory $indexerFactory
    ) {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids)
    {
        /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
        $indexer = $this->indexerFactory->create()->load(UrlRewriteCategory::INDEXER_ID);
        if ($indexer->isScheduled() && !$indexer->isWorking()) {
            $indexer->reindexList($ids);
        }
    }
}