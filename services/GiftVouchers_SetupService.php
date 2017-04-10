<?php
namespace Craft;

class GiftVouchers_SetupService extends BaseApplicationComponent
{

    public function createProductType()
    {

        // CHECK TO SEE IF THE PRODUCT TYPE ALREADY EXISTS
        if (craft()->commerce_productTypes->getProductTypeByHandle('giftVoucher')) 
        {

            GiftVouchersPlugin::log('The giftVoucher product type already exists.', LogLevel::Warning);

        } else {

            // CREATE THE PRODUCT TYPE
            $productType = new Commerce_ProductTypeModel;
            $productType->name = "Gift Voucher";
            $productType->handle = "giftVoucher";
            $productType->hasDimensions = FALSE;
            $productType->hasUrls = FALSE;
            $productType->hasVariants = FALSE;
            $productType->titleFormat = '${ price } Gift Voucher';
            $productType->skuFormat = "{ slug }";
            $productType->descriptionFormat = "{ title }";
            $productType->template = FALSE;

            // VALIDATE THE MODEL
            if ($productType->validate())
            {

                // SAVE THE PRODUCT TYPE
                if (craft()->commerce_productTypes->saveProductType($productType))
                {

                    GiftVouchersPlugin::log('"Gift Voucher" product type created.', LogLevel::Info);

                } else {

                    GiftVouchersPlugin::log('There was a problem creating the product type.', LogLevel::Error);

                }

            } else {

                GiftVouchersPlugin::log('"Gift Voucher" product type failed validation.', LogLevel::Error);

            }

            /*
            ********************************
            I'D LOVE TO CREATE THE BALANCE AND DISCOUNT FIELDS HERE
            THEN CREATE A LAYOUT AND ADD THAT TO THE PRODUCT TYPE
            BUT DON'T HAVE TIME. CREATE MANUALLY INSTEAD.
            ********************************
            */

        }

        return TRUE;

    }
}