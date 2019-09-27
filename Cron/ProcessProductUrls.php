<?php

namespace Hevelop\UrlRewrite\Cron;

use Hevelop\UrlRewrite\Model\Processor\CategoryProducts;

class ProcessProductUrls
{
    /**
     * @var CategoryProducts
     */
    private $categoryProducts;

    public function __construct(CategoryProducts $categoryProducts)
    {
        $this->categoryProducts = $categoryProducts;
    }

    public function execute()
    {
        $this->categoryProducts->execute();
    }
}