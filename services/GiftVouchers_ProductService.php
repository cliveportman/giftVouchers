<?php
namespace Craft;

class GiftVouchers_ProductService extends BaseApplicationComponent
{

    public function addGiftVoucherToCart(Commerce_ProductModel $product)
    {

        // TEST FOR PRESENCE OF PRODUCTSCREATOR USER AND LOG THEM OUT
        $user = craft()->userSession->getUser();
        if ($user) {
            if ($user->id == 36) craft()->userSession->logout();
        }

        // TEST FOR EXISTENCE OF CART AND CREATE ONE IF NECESSARY
        $cart = craft()->giftVouchers_product->createCart();

        // ADD THE PRODUCT TO THE CART
        craft()->giftVouchers_product->addProductToCart($cart, $product);

        return TRUE;

    }

    public function createCart() 
    {

        // CHECK FOR A CART AND IF THERE ISN'T ONE, CREATE ONE
        $cart = craft()->commerce_cart->getCart();     
        if (!$cart->id) {
            if (!craft()->commerce_orders->saveOrder($cart)) {
                $error = Craft::t('Error creating empty cart: ') . print_r($order->getAllErrors(), true);
                GiftVouchersPlugin::log($error, LogLevel::Error);
                throw new Exception($error);
            }
        }

        return $cart;

    }

    public function addProductToCart(Commerce_OrderModel $cart, Commerce_ProductModel $product) 
    {

        if ($product->type != 'giftVoucher') return TRUE;

        // GET THE DEFAULT VARIANT
        $variant = $product->defaultVariant;

        // SET THE OPTIONS AS BLANK
        $options = isset($product->options) ? $product->options : [];

        // CREATE THE LINE ITEM
        $lineItem = craft()->commerce_lineItems->createLineItem($variant->purchasableId, $cart, $options, 1);
        
        // COPIES THE RECIPIENT FIELD TO THE LINEITEM NOTE
        $lineItem->note = "For $product->recipientName";

        // SAVE THE LINE ITEM
        if (!craft()->commerce_lineItems->saveLineItem($lineItem)) {
            $error = Craft::t('Error on saving line item: ') . print_r($lineItem, true);
            GiftVouchersPlugin::log($error, LogLevel::Error);
            throw new Exception($error);
        }

        // SAVE THE ORDER
        if (!craft()->commerce_orders->saveOrder($cart)) {
            $error = Craft::t('Error on saving cart: ') . print_r($cart->getAllErrors(), true);
            GiftVouchersPlugin::log($error, LogLevel::Error);
            throw new Exception($error);
        }
        GiftVouchersPlugin::log("Cart #$cart->shortNumber saved.", LogLevel::Info);

        // NO NEED TO RETURN ANYTHING BUT BE SURE TO HANDLE FAILURES WITHIN THIS FUNCTION
        return TRUE;

    }

}