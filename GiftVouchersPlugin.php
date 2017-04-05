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

	public function init()
	{
	}

}
