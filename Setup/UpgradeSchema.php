<?php
/**
 * @author piazzaitalia_hevelop_team
 * @copyright Copyright (c) 2019 Hevelop (https://www.hevelop.com)
 * @package piazzaitalia
 */

namespace Hevelop\UrlRewrite\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * @inheritdoc
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $this->updateProductAndCategoryEntitiesColumns($setup);
        }
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addCategoryProductsNeedRewriteTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    private function updateProductAndCategoryEntitiesColumns(SchemaSetupInterface $installer)
    {
        $tables = [
            $installer->getTable('catalog_category_entity'),
        ];

        $columns = [
            'need_url_rewrite' => 'Need Url Rewrite Entry Update?'
        ];

        foreach ($tables as $tableName) {
            foreach ($columns as $column => $comment) {
                $installer->getConnection()->addColumn(
                    $tableName,
                    $column,
                    [
                        'type' => Table::TYPE_SMALLINT,
                        'nullable' => true,
                        'default' => 0,
                        'comment' => $comment
                    ]
                );
            }
        }
    }

    /**
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    private function addCategoryProductsNeedRewriteTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getConnection()
            ->newTable($installer->getTable('url_rewrite_category_product'))
            ->addColumn(
                'row_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Row ID'
            )
            ->addColumn(
                'category_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => false],
                'Category ID'
            )
            ->addColumn(
                'product_ids',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'primary' => false],
                'Affected Product IDs'
            )
            ->setComment('Category/Product need rewrite Table');
        $installer->getConnection()->createTable($table);
    }
}
