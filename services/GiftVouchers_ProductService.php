<?php
namespace Craft;

use Commerce\Helpers\CommerceDbHelper;

class GiftVouchers_ProductService extends BaseApplicationComponent
{

    public function addGiftVoucherToCart(Commerce_ProductModel $product)
    {

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

    public function updateProductWithDiscountCode($productId, $discountCode) 
    {

        // GET THE PRODUCT, UPDATE IT AND SAVE
        $product = craft()->commerce_products->getProductById($productId);
        $product->setContentFromPost(array('discountCode' => $discountCode ));
        if (craft()->commerce_products->saveProduct($product)) {
            GiftVouchersPlugin::log("Product #$product->id updated with coupon code $discountCode", LogLevel::Info);
        }
        return TRUE;

    }

    // SIMPLY A COPY OF CRAFT COMMERCE'S SAVEPRODUCT FUNCTION
    // SO WE CAN BYPASS USER ISSUES
    public function saveProduct(Commerce_ProductModel $product)
    {

        $isNewProduct = !$product->id;

        if (!$product->id) {
            $record = new Commerce_ProductRecord();
        } else {
            $record = Commerce_ProductRecord::model()->findById($product->id);

            if (!$record) {
                throw new Exception(Craft::t('No product exists with the ID “{id}”',
                    ['id' => $product->id]));
            }
        }

        // Fire an 'onBeforeSaveProduct' event
        $event = new Event($this, [
            'product'      => $product,
            'isNewProduct' => $isNewProduct
        ]);

        craft()->commerce_products->onBeforeSaveProduct($event);

        $record->postDate = $product->postDate;
        $record->expiryDate = $product->expiryDate;
        $record->typeId = $product->typeId;
        $record->promotable = $product->promotable;
        $record->freeShipping = $product->freeShipping;
        $record->taxCategoryId = $product->taxCategoryId;
        $record->shippingCategoryId = $product->shippingCategoryId;

        $record->validate();
        $product->addErrors($record->getErrors());

        $productType = craft()->commerce_productTypes->getProductTypeById($product->typeId);

        if(!$productType){
            throw new Exception(Craft::t('No product type exists with the ID “{id}”',
                ['id' => $product->typeId]));
        }

        $taxCategoryIds = array_keys($productType->getTaxCategories());
        if (!in_array($product->taxCategoryId, $taxCategoryIds))
        {
            $record->taxCategoryId = $product->taxCategoryId = $taxCategoryIds[0];
        }

        $shippingCategoryIds = array_keys($productType->getShippingCategories());
        if (!in_array($product->shippingCategoryId, $shippingCategoryIds))
        {
            $record->shippingCategoryId = $product->shippingCategoryId = $shippingCategoryIds[0];
        }

        // Final prep of variants and validation
        $variantsValid = true;
        $defaultVariant = null;
        foreach ($product->getVariants() as $variant) {

            // Use the product type's titleFormat if the title field is not shown
            if (!$productType->hasVariantTitleField && $productType->hasVariants)
            {
                try
                {
                    $variant->getContent()->title = craft()->templates->renderObjectTemplate($productType->titleFormat, $variant);
                }catch(\Exception $e){
                    $variant->getContent()->title = "";
                }
            }

            if(!$productType->hasVariants)
            {
                // Since VariantModel::getTitle() returns the parent products title when the product has
                // no variants, lets save the products title as the variant title anyway.
                $variant->getContent()->title = $product->getTitle();
            }

            // If we have a blank SKU, generate from product type's skuFormat
            if(!$variant->sku){
                try
                {
                    if (!$productType->hasVariants)
                    {
                        $variant->sku = craft()->templates->renderObjectTemplate($productType->skuFormat, $product);
                    }
                    else
                    {
                        $variant->sku = craft()->templates->renderObjectTemplate($productType->skuFormat, $variant);
                    }
                }catch(\Exception $e){
                    CommercePlugin::log("Could not generate SKU format: ".$e->getMessage(), LogLevel::Warning, true);
                    $variant->sku = "";
                }
            }

            // Make the first variant (or the last one that says it isDefault) the default.
            if ($defaultVariant === null || $variant->isDefault)
            {
                $defaultVariant = $variant;
            }

            if (!craft()->commerce_variants->validateVariant($variant)) {
                $variantsValid = false;
                // If we have a title error but hide the title field, put the error onto the sku.
                if($variant->getError('title') && !$productType->hasVariantTitleField && $productType->hasVariants){
                    $variant->addError('sku',Craft::t('Could not generate the variant title from product type’s title format.'));
                }

                if($variant->getError('title') && !$productType->hasVariants){
                    $product->addError('title',Craft::t('Title cannot be blank.'));
                }
            }
        }

        if ($product->hasErrors() || !$variantsValid)
        {
            return false;
        }


        CommerceDbHelper::beginStackedTransaction();
        try {

             $record->defaultVariantId = $product->defaultVariantId = $defaultVariant->getPurchasableId();
             $record->defaultSku = $product->defaultSku = $defaultVariant->getSku();
             $record->defaultPrice = $product->defaultPrice = (float) $defaultVariant->price;
             $record->defaultHeight = $product->defaultHeight = (float) $defaultVariant->height;
             $record->defaultLength = $product->defaultLength = (float) $defaultVariant->length;
             $record->defaultWidth = $product->defaultWidth = (float) $defaultVariant->width;
             $record->defaultWeight = $product->defaultWeight = (float) $defaultVariant->weight;
            
            if ($event->performAction)
            {

                $success = craft()->elements->saveElement($product);

                if ($success)
                {
                    // Now that we have an element ID, save it on the other stuff
                    if ($isNewProduct)
                    {
                        $record->id = $product->id;
                    }

                    $record->save(false);

                    $keepVariantIds = [];
                    $oldVariantIds = craft()->db->createCommand()
                        ->select('id')
                        ->from('commerce_variants')
                        ->where('productId = :productId', [':productId' => $product->id])
                        ->queryColumn();

                    foreach ($product->getVariants() as $variant)
                    {
                        if ($defaultVariant === $variant)
                        {
                            $variant->isDefault = true;
                            $variant->enabled = true; // default must always be enabled.
                        }
                        else
                        {
                            $variant->isDefault = false;
                        }
                        $variant->setProduct($product);

                        craft()->commerce_variants->saveVariant($variant);

                        // Need to manually update the product's default variant ID now that we have a saved ID
                        if ($product->defaultVariantId === null && $defaultVariant === $variant)
                        {
                            $product->defaultVariantId = $variant->id;
                            craft()->db->createCommand()->update('commerce_products', ['defaultVariantId' => $variant->id], ['id' => $product->id]);
                        }

                        $keepVariantIds[] = $variant->id;
                    }

                    foreach (array_diff($oldVariantIds, $keepVariantIds) as $deleteId)
                    {
                        craft()->commerce_variants->deleteVariantById($deleteId);
                    }

                    CommerceDbHelper::commitStackedTransaction();
                }

            }else{
                $success = false;
            }
        } catch (\Exception $e) {
            CommerceDbHelper::rollbackStackedTransaction();
            throw $e;
        }

        if ($success)
        {
            // Fire an 'onSaveProduct' event
            craft()->commerce_products->onSaveProduct(new Event($this, [
                'product'      => $product,
                'isNewProduct' => $isNewProduct
            ]));
        }

        return $success;
    }

}