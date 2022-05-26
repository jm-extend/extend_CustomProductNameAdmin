<?php
/*
 * Custom Extend Module to change the SKU of the warranty product before the order is placed
 * the SKU was previously WARRANTY-1, now we build a custom sku based on the API response.
 * in this example, EXTEND-<WARRANTY_ID_FROM_API>
 *
 * */

namespace Extend\CustomProductNameAdmin\Observer\Frontend\Sales;

class OrderPlaceBefore implements \Magento\Framework\Event\ObserverInterface
{
    protected \Magento\Catalog\Model\ProductRepository $_productRepository;
    private \Magento\Framework\Data\Form\FormKey $formKey;
    protected \Magento\Checkout\Model\Cart $_cart;

    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart
    ){
        $this->_productRepository = $productRepository;
        $this->formKey = $formKey;
        $this->_cart = $cart;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();
        $items = $order->getItems();
        foreach($items as $item){
            if ($item->getSku() == 'WARRANTY-1'){
                $productOptions = $item->getProductOptions();

                $term = $productOptions['warranty_term']; //in months
                $yearValue =  floor($term/12) > 1 ? 'years' : 'year';
                $termValue =  floor($term/12) .' '. $yearValue;
                $product = $productOptions['associated_product']; //sku covered
                $productName = self::getProductNameFromSkuFromCart($product);

                $item->setName($item->getName().' for '.$productName.' ('.$termValue.')');
                $order->save();
            }
        }
    }

    public function getProductNameFromSkuFromCart($sku): string
    {
        // get quote items collection and filter on sku
        $itemsCollection = $this->_cart->getQuote()->getItemsCollection();
        $itemCollectionBySku  = $itemsCollection->addFieldToFilter('sku', $sku);
        $filteredItem = $itemCollectionBySku->getFirstItem();
        foreach($itemsCollection as $items){
            if ($items->getSku() == $sku){
                $itemName = $items->getName();
            }
        }
        if ($itemName){
            return $itemName;
        }else{
            return $sku;
        }
    }
}
