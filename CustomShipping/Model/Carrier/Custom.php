<?php

namespace Mobilyte\CustomShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;

class Custom extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
\Magento\Shipping\Model\Carrier\CarrierInterface
{

    /**
     * @var string
     */
    protected $_code = 'custom';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory, \Psr\Log\LoggerInterface $logger, \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory, \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory, array $data = []
    )
    {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @param RateRequest $request
     * @return Result|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $freeBoxes = 0;
        $get_total_shipping = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {

	// $product1 = array('a' => 'Name', 'b' => 'Age');
   // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
   // $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
   // $shippingCode = $product->getData('shipping_code');


   // $productName = $item->getName();
   $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
   $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());
   $flat_rate_shipping_price = $product->getData('flat_rate_shipping_price');
   
   if($flat_rate_shipping_price > 0)
   $get_total_shipping = $get_total_shipping + $flat_rate_shipping_price;

   // $fp = fopen("/var/www/html/retrofit/trunk/var/atestlog.txt", "a");
   // // fwrite($fp, '\n'.$productName);
   // // fwrite($fp, '\n'.$item->getId());
   // fwrite($fp, 'getProductId');
   // fwrite($fp, '\n'. $item->getProductId());
   // fwrite($fp, '\n shippingCodePrice : ' . @$shippingCodePrice);
   // // fwrite($fp, '\n'.print_r($product, true));
   // fclose($fp);





                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeBoxes += $item->getQty() * $child->getQty();
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }

        $this->setFreeBoxes($freeBoxes);

        /** @var Result $result */
        $result = $this->_rateResultFactory->create();
        if ($this->getConfigData('type') == 'O') {
            // per order
            $shippingPrice = $this->getConfigData('price');
        } elseif ($this->getConfigData('type') == 'I') {
            // per item
            // $shippingPrice = $request->getPackageQty() * $flat_rate_shipping_price - $this->getFreeBoxes() * $this->getConfigData('price');
            $shippingPrice = $get_total_shipping;
            // $shippingPrice = $product->getId();
             // - $this->getFreeBoxes() * $this->getConfigData('price');
        } else {
            $shippingPrice = false;
        }

        // $shippingPrice = $this->getFinalPriceWithHandlingFee($shippingPrice);

        if ($shippingPrice !== false) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier('custom');
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod('custom');
            $method->setMethodTitle($this->getConfigData('name'));

            if ($request->getFreeShipping() === true || $request->getPackageQty() == $this->getFreeBoxes()) {
                $shippingPrice = '0.00';
            }

            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);

            $result->append($method);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['custom' => $this->getConfigData('name')];
    }

}