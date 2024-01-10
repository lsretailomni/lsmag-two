<?php

namespace Ls\Omni\Plugin\Session;

use Magento\Customer\Controller\Section\Load;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\Session;
use Magento\Framework\Session\Generic as GenericSession;

/**
 * Class SectionLoadControllerPlugin
 * @package Ls\Omni\Plugin\Session
 */
class SectionLoadControllerPlugin
{
    /**
     * @var GenericSession
     */
    public $genericSession;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var array
     */
    public $additionalSessions;

    /**
     * SectionLoadControllerPlugin constructor.
     * @param GenericSession $genericSession
     * @param RequestInterface $request
     * @param array $additionalSessions
     */
    public function __construct(
        GenericSession $genericSession,
        RequestInterface $request,
        array $additionalSessions
    ) {

        $this->genericSession     = $genericSession;
        $this->request            = $request;
        $this->additionalSessions = $additionalSessions;
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
