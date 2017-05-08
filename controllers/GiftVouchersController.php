<?php
namespace Craft;

use Commerce\Helpers\CommerceDbHelper;
use Commerce\Helpers\CommerceProductHelper;

class GiftVouchersController extends BaseController
{

    protected $allowAnonymous = true;

    /**
     * Save a new or existing product.
     */
    public function actionSaveProduct()
    {
        $this->requirePostRequest();

        // CHECK THE PRODUCT IS THE CORRECT TYPE ID
        $settings = craft()->plugins->getPlugin('giftvouchers')->getSettings();
        if ( craft()->request->getPost('typeId') != $settings->productTypeId ) {
            throw new Exception("Incorrect product ID");
        }

        $product = $this->_setProductFromPost();
        CommerceProductHelper::populateProductVariantModels($product, craft()->request->getPost('variants'));

        //$this->enforceProductPermissions($product);


        $existingProduct = (bool)$product->id;

        CommerceDbHelper::beginStackedTransaction();

        if (craft()->giftVouchers_product->saveProduct($product))
        {

            CommerceDbHelper::commitStackedTransaction();

            craft()->userSession->setNotice(Craft::t('Product saved.'));

            $this->redirectToPostedUrl($product);
        }

        CommerceDbHelper::rollbackStackedTransaction();
        // Since Product may have been ok to save and an ID assigned,
        // but child model validation failed and the transaction rolled back.
        // Since action failed, lets remove the ID that was no persisted.
        if (!$existingProduct)
        {
            $product->id = null;
        }


        craft()->userSession->setError(Craft::t('Couldn’t save product.'));
        craft()->urlManager->setRouteVariables([
            'product' => $product
        ]);
    }

    private function _setProductFromPost()
    {
        $productId = craft()->request->getPost('productId');
        $locale = craft()->request->getPost('locale');

        if ($productId)
        {
            $product = craft()->commerce_products->getProductById($productId, $locale);

            if (!$product)
            {
                throw new Exception(Craft::t('No product with the ID “{id}”',
                    ['id' => $productId]));
            }
        }
        else
        {
            $product = new Commerce_ProductModel();
        }

        CommerceProductHelper::populateProductModel($product, craft()->request->getPost());

        $product->localeEnabled = (bool)craft()->request->getPost('localeEnabled', $product->localeEnabled);
        $product->getContent()->title = craft()->request->getPost('title', $product->title);
        $product->setContentFromPost('fields');

        return $product;
    }

}