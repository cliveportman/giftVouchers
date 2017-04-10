<?php
namespace Craft;

class GiftVouchersController extends BaseController
{

    protected $allowAnonymous = true;

    public function actionSaveProduct()
    {

        $this->requirePostRequest();

        // CHECK TO SEE IF LOGGED IN
        $user = craft()->userSession->getUser();

        // IF NOT, THEN LOGIN AS THE PRODUCTSCREATOR USER
        // BUT REMEMBER TO LOGOUT IMMEDIATELY AFTER WITHIN THE SERVICE
        if (!$user) {
            if (!craft()->userSession->login('productscreator', '1amspecial')) {
                throw new Exception("You do not have permission for this.");
            }
        }

        // THEN FORWARD THE REQUEST ON TO THE COMMERCE PLUGIN
        $forward = $this->forward('commerce/products/saveProduct', false);
    }

}