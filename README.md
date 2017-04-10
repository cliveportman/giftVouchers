# giftVouchers
Adds gift vouchers to Craft Commerce.
## Setup
Installing the plugin will create a product type called `giftVouchers` but you'll need to create and add a couple of custom fields before using the cart/checkout:
- recipientName (plain text)
- discountCode (plain text)

Remember to add them to the product's fields within Craft Commerce.

## Gift voucher format
So they can easily be identified from other discounts in the store, we're prepending `gv` to the beginning of the coupon code.

## Create discount form
```
<form method="post">

    <input type="hidden" name="action" value="giftVouchers/saveProduct">
    {#<input type="hidden" name="redirect" value="{{ siteUrl }}shop/cart">#}
    <input type="hidden" name="enabled" value="1">
    <input type="hidden" name="postDate[date]" value="{{ now | date('d/m/Y i:H') }}">
    <input type="hidden" name="typeId" value="8">
    <input type="hidden" name="expiryDate[date]" value="">
    <input type="hidden" name="slug" value="">
    <input type="hidden" name="shippingCategoryId" value="1">
    <input type="hidden" name="variants[new1][unlimitedStock]" value="1">
    <input type="hidden" name="variants[new1][sku]" value="GV{{ now | date('U') }}">
    <input type="hidden" name="variants[new1][minQty]" value="1">
    <input type="hidden" name="variants[new1][maxQty]" value="5">
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
