<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Session;

use Magento\Customer\Controller\Section\Load;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\Session;
use Magento\Framework\Session\Generic as GenericSession;

class SectionLoadControllerPlugin
{
    /**
     * @param GenericSession $genericSession
     * @param RequestInterface $request
     * @param array $additionalSessions
     */
    public function __construct(
        public GenericSession $genericSession,
        public RequestInterface $request,
        public array $additionalSessions
    ) {
    }

    // @codingStandardsIgnoreLine
    public function beforeExecute(Load $subject)
    {
        $hasMessages     = 0;
        $sections        = $this->request->getParam('sections');
        foreach ($this->additionalSessions as $session) {
            if ($session instanceof Session) {
                foreach ($session->getData() as $messageCollection) {
                    // @codingStandardsIgnoreLine
                    $hasMessages += count($messageCollection->getItems());
                }
            }
        }
        if ($hasMessages === 0) {
            $this->genericSession->writeClose();
        }
        if ($hasMessages === 1 && $sections == 'cart,messages') {
            $this->genericSession->writeClose();
        }
    }
}
