<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommItemCategories;
use \Ls\Omni\Client\Ecommerce\Entity\ReplItemCategoryResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplItemCategory;

class ReplEcommItemCategoriesTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommItemCategories();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommItemCategories($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplItemCategoryResponse::class, $result);
        $this->assertNotNull($result->getItemCategories());
        $this->assertInstanceOf(ArrayOfReplItemCategory::class, $result->getItemCategories());
    }
}