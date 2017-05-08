<?php
namespace Craft;

class GiftVouchersPlugin extends BasePlugin
{

	public function getName()
	{
		return 'Gift Vouchers';
	}

	public function getDescription()
	{
		return '';
	}

	public function getVersion()
	{
		return '1.0.0';
	}

	public function getDeveloper()
	{
		return 'Clive Portman';
	}

	public function getDeveloperUrl()
	{
		return 'https://www.theportman.co/';
	}

	public function onAfterInstall()
	{
		craft()->giftVouchers_setup->createProductType();
	}

	protected function defineSettings()
	{
		return array(
			'productTypeId' => array(AttributeType::String, 'required' => true, 'label' => 'Product type ID')
		);
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('giftvouchers/_settings', array(
			'settings' => $this->getSettings()
		));
    }

	public function init()
	{
        parent::init();

        craft()->on('commerce_products.onSaveProduct', function (Event $event) {
            if ($event->params['isNewProduct']) {
            	$product = $event->params['product'];
            	craft()->giftVouchers_product->addGiftVoucherToCart($product);
            }
        });
        
        craft()->on('commerce_orders.onBeforeOrderComplete', function (Event $event) {
            $order = $event->params['order'];
            craft()->giftVouchers_discount->createDiscounts($order);
        });
        
        craft()->on('commerce_orders.onOrderComplete', function (Event $event) {
            $order = $event->params['order'];
            craft()->giftVouchers_discount->updateDiscount($order);
        });
        
	}

}
