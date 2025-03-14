<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Controller\Adminhtml\Grids;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

abstract class AbstractGrid extends AbstractBackendController
{
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resource   = 'Magento_Backend::admin';
        $this->uri        = $this->getUri();
        $this->httpMethod = HttpRequest::METHOD_GET;
        parent::setUp();
    }

    public function prepare()
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);
        return $this->getResponse()->getBody();
    }

    public function assertPageName($pageName)
    {
        $body = $this->prepare();
        $this->assertStringContainsString(
            sprintf('<h1 class="page-title">%s</h1>', (string)$pageName),
            $body
        );
    }

    abstract public function getUri();
}
