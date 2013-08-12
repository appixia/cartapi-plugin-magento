<?php

require_once(Mage::getBaseDir('lib').'/appixiacartapi/Engine.php');
require_once(Mage::getBaseDir('lib').'/appixiacartapi/Helpers.php');

class Appixia_CartAPI_CartapiController extends Mage_Core_Controller_Front_Action
{
	// http://appixia.com/demos/magento/appixia/cartapi
	public function indexAction ()
	{
		// handle the request
		$request = CartAPI_Engine::handleRequest();
		if ($request === false) die('ERROR');

		// find the correct operation handler
		$operation = $request['metadata']['X-OPERATION'];

		// define all supported operations
		$request_router = array
		(
			'GetSingleItem' => 'Items',
			'GetItemList' => 'Items',
		);

		// find the correct operation handler
		$operation = $request['metadata']['X-OPERATION'];
		$func_name = 'Handle__'.$operation;
		$helper = null;
		if (isset($request_router[$operation])) $helper = Mage::helper('appixiacartapi/'.strtolower($request_router[$operation]));
		if ($helper == null) CartAPI_Helpers::dieOnError($request['encoder'], 'UnsupportedOperation', $operation.' not supported');

		// call the operation handler
		$helper->{$func_name}($request['metadata'], $request['data'], $request['encoder']);
	} 
}

?>