<?php
namespace Craft;

class GiftVouchers_DiscountService extends BaseApplicationComponent
{

    public function createDiscounts(Commerce_OrderModel $order)
    {

        // LOOP THROUGH THE ORDER'S LINE ITEMS CHECKING FOR GIFT VOUCHERS
        foreach ($order->getLineItems() as $lineItem) {

            if ($lineItem->purchasableId) {

                $product = craft()->commerce_products->getProductById($lineItem->purchasable->productId);
                if ($product->type == 'giftVoucher') {

                    // CREATE THE DISCOUNT
                    $discount = new Commerce_DiscountModel;
                    $discount->name = "$" . number_format($product->defaultVariant->price, 2) . " gift voucher";
                    $discount->description = "";
                    $discount->code = uniqid('gv');
                    $discount->dateFrom = NULL;
                    $discount->dateTo = NULL;
                    $discount->enabled = 1;
                    $discount->purchaseTotal = 0;
                    $discount->purchaseQty = 0;
                    $discount->maxPurchaseQty = 0;
                    $discount->baseDiscount = -$product->defaultVariant->price; // SHOULD BE NEGATIVE
                    $discount->perItemDiscount = 0;
                    $discount->percentDiscount = 0;
                    $discount->freeShipping = 0;
                    $discount->excludeOnSale = 0;
                    $discount->perUserLimit = 0;
                    $discount->perEmailLimit = 0;
                    $discount->totalUseLimit = 0;

                    // REQUIRED EVEN IF EMPTY
                    $groups = [];
                    $productTypes = [];
                    $products = [];
                    
                    if (craft()->commerce_discounts->saveDiscount($discount, $groups, $productTypes, $products))
                    {
                        GiftVouchersPlugin::log("$discount->name created for product #$product->id with coupon code $discount->code", LogLevel::Info);                        
                    } else {
                        GiftVouchersPlugin::log("$discount->name could not be created for product #$product->id", LogLevel::Error);
                    }
                    
                    craft()->giftVouchers_product->updateProductWithDiscountCode($product->id, $discount->code);

                }

            }

        }

        return TRUE;

    }

    public function updateDiscount(Commerce_OrderModel $order)
    {

        $discount = craft()->commerce_discounts->getDiscountByCode($order->couponCode);

        // REQUIRED EVEN IF EMPTY FOR SAVING THE DISCOUNT LATER
        $groups = [];
        $productTypes = [];
        $products = [];

        if ($discount) {

            // CHECK THE DISCOUNT IS FROM A GIFT VOUCHER
            if(substr($order->couponCode, 0, 2) === "gv") {

                $totalPrice = $order->itemTotal + $order->totalTax + $order->totalShippingCost;
                $currentDiscount = abs($discount->baseDiscount);
                $discountRemaining = $discount->baseDiscount + $totalPrice;

                // IF THERE IS ANY DISCOUNT REMAINING
                if($discountRemaining < 0)
                {

                    // UPDATE THE DISCOUNT AND SAVE IT
                    $discount->baseDiscount = $discountRemaining;
                    if (craft()->commerce_discounts->saveDiscount($discount, $groups, $productTypes, $products))
                    {
                        GiftVouchersPlugin::log("Discount with code $order->couponCode updated from $currentDiscount to " . abs($discountRemaining), LogLevel::Info);                       
                    } else {
                        GiftVouchersPlugin::log("Discount with code $order->couponCode could not be updated", LogLevel::Error);
                    }

                } else {

                    // OR DISABLE THE DISCOUNT AND SAVE IT
                    $discount->baseDiscount = 0;
                    $discount->enabled = 0;

                    if (craft()->commerce_discounts->saveDiscount($discount, $groups, $productTypes, $products))
                    {
                        GiftVouchersPlugin::log("Discount with code $order->couponCode disabled", LogLevel::Info);                        
                    } else {
                        GiftVouchersPlugin::log("Discount with code $order->couponCode could not be disabled", LogLevel::Error);
                    }

                }
            }
        }

        return TRUE;

    }

}