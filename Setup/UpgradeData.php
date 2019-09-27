<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * Class UpgradeData
 * @package Hevelop\UrlRewrite\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var
     */
    protected $eavSetupFactory;

    /**
     * UpgradeData constructor.
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $this->addProductAndCategoryNeedUrlRewriteAttribute($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param ModuleDataSetupInterface $installer
     */
    private function addProductAndCategoryNeedUrlRewriteAttribute(ModuleDataSetupInterface $installer)
    {
        $options = [
            'type' => 'static',
            'visible' => false,
            'required' => true,
            'default' => 0
        ];

        $eavSetup = $this->eavSetupFactory->create(['setup' => $installer]);
        $eavSetup->addAttribute(
            'catalog_category',
            'need_url_rewrite',
            $options
        );
    }
}