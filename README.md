# giftVouchers
Adds gift vouchers to Craft Commerce, utilising discounts.

## Setup
Installing the plugin will create a product type called `giftVouchers` but you'll need to create and add a couple of custom fields before using the cart/checkout:
- recipientName (plain text)
- discountCode (plain text)
- add them to the product type's fields within Craft Commerce.

Rather than pre-create products for purchase, we create them on the frontend as and when they're needed. This prevents say, three of the same gift voucher being added to the cart, and simplifies adding recipient names to the vouchers. 

## Gift voucher format
So they can easily be identified from other discounts in the store, we're prepending `gv` to the beginning of the coupon code. 

## Templating
Somewhere, you'll need a page that contains the `create discount form`. The cart should work with the standard `promo code form` but you might want to inform users that any unused credit will be calculated after their order has been completed. If you're providing users with an account area, you'll probably want to display a list of purchased gift vouchers with remaining credit.

### Create discount form
```
<form method="post">

    <input type="hidden" name="action" value="giftVouchers/saveProduct">
    {#<input type="hidden" name="redirect" value="{{ siteUrl }}shop/cart">#}{# SET THIS IF YOU NEED IT #}
    <input type="hidden" name="enabled" value="1">
    <input type="hidden" name="postDate[date]" value="{{ now | date('d/m/Y i:H') }}">
    <input type="hidden" name="typeId" value="8">{# SET THIS TO THE ID OF THE PRODUCT TYPE #}
    <input type="hidden" name="expiryDate[date]" value="">
    <input type="hidden" name="slug" value="">
    <input type="hidden" name="shippingCategoryId" value="1">
    <input type="hidden" name="variants[new1][unlimitedStock]" value="1">
    <input type="hidden" name="variants[new1][sku]" value="GV{{ now | date('U') }}">
    <input type="hidden" name="variants[new1][minQty]" value="1">
    <input type="hidden" name="variants[new1][maxQty]" value="1">
    <input type="hidden" name="taxCategoryId" value="1">
    <input type="hidden" name="freeShipping" value="1">
    <input type="hidden" name="promotable" value="1">

    <select name="variants[new1][price]">
        <option value="99.00">$99</option>
        <option value="160.00">$160</option>
    </select>

    <input type="hidden" name="title" value="Gift Voucher">
    <input type="text" name="fields[recipientName]" value="" placeholder="Recipient name">

    <input type="submit" value="{{ "Add to cart"|t }}" class="button"/>

</form>
```
