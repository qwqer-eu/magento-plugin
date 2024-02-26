<?php

namespace Qwqer\Express\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Qwqer\Express\Model\Carrier\Express;
use Qwqer\Express\Model\Carrier\ScheduledToDoor;
use Qwqer\Express\Model\Carrier\ScheduledToParcel;
use Qwqer\Express\Service\PublishOrder;
use Qwqer\Express\Logger\Logger;

class OrderPlaceBefore implements ObserverInterface
{
    /**
     * @var PublishOrder
     */
    protected PublishOrder $publishOrder;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param PublishOrder $publishOrder
     * @param Logger $logger
     */
    public function __construct(
        PublishOrder $publishOrder,
        Logger $logger
    ) {
        $this->publishOrder = $publishOrder;
        $this->logger = $logger;
    }

    /**
     * OrderPlaceBefore execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $shippingMethod = $order->getShippingMethod(true);
        if ($shippingMethod
            && ($shippingMethod->getData('carrier_code') == Express::CARRIER_CODE
                || $shippingMethod->getData('carrier_code') == ScheduledToDoor::CARRIER_CODE
                || $shippingMethod->getData('carrier_code') == ScheduledToParcel::CARRIER_CODE
            )
            && $quote->getShippingAddress()->getQwqerAddress()
        ) {
            $placedOrder = $this->publishOrder->execute($order, $quote);
            if ($placedOrder) {
                $order->setQwqerData(json_encode($placedOrder));
                if (!empty($placedOrder['data']['id'])) {
                    $order->addStatusHistoryComment('QWQER Order Id: ' . $placedOrder['data']['id']);
                    $order->setQwqerOrderId($placedOrder['data']['id']);
                }
            }
        }
        return $this;
    }
}
