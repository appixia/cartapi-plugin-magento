<?php

class Appixia_CartAPI_Helper_Items extends Mage_Core_Helper_Abstract
{
	public function Handle__GetSingleItem($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Id'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Id argument missing');

		// load the product
		$product = Mage::getModel('catalog/product')->load($request['Id']);
		if (!$product->getId()) CartAPI_Helpers::dieOnError($encoder, 'ItemNotFound', 'Id '.$request['Id'].' not found');

		// create the response
		$response = CartAPI_Helpers::createSuccessResponse($encoder);

		// add the item to the response
		$item = &$encoder->addContainer($response, 'Item');

		// fill in the item fields
		$encoder->addNumber($item, 'Id', $product->getId());
		$encoder->addString($item, 'Title', $product->getName());
		$encoder->addNumber($item, 'Price', $product->getPrice());
		$encoder->addString($item, 'ThumbnailUrl', $product->getSmallImageUrl(135,135));

		// show the response
		$encoder->render($response);
	}

	public function Handle__GetItemList($metadata, $request, $encoder)
	{
		// required arguments
		if (!isset($request['Paging'])) CartAPI_Helpers::dieOnError($encoder, 'IncompleteRequest', 'Paging argument missing');
		CartAPI_Helpers::validatePagingRequest($encoder, $request['Paging']);

		// get the products
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToSelect('name');
		$collection->addAttributeToSelect('price');
		$collection->addAttributeToSelect('small_image');
		$collection->setPageSize($request['Paging']['ElementsPerPage'])->setCurPage($request['Paging']['PageNumber']);

		// optional arguments
		if (isset($request['Filter']))
		{
			// TODO: support an array of filters, need to check how this works in the URL param decoder too.. may not be simple
			$db_field_name_map = array('Title' => 'name');

			$filter = $request['Filter'];
			CartAPI_Helpers::validateFilter($encoder, $filter, $db_field_name_map);
			$db_field_name = $db_field_name_map[$filter['Field']];
			$sql_operator = CartAPI_Helpers::getSqlFilterOperatorFromRelation($encoder, $filter['Relation']);
			$sql_value = CartAPI_Helpers::getSqlFilterValueFromRelation($encoder, $filter['Relation'], $filter['Value']);

			$magento_operator = $this->getMagentoOperatorFromSqlOperator($sql_operator);
			if ($magento_operator === false) CartAPI_Helpers::dieOnError($encoder, 'UnsupportedFilter', $filter['Relation'].' filter relation is unsupported');

			$collection->addFieldToFilter(array(
				array('attribute' => $db_field_name, $magento_operator => $sql_value),
			));
		}

		// count the total number of results
		$total_elements = $collection->getSize();

		// create the response
		$response = CartAPI_Helpers::createSuccessResponseWithPaging($encoder, $request['Paging'], $total_elements);

		// add the items to the response if needed
		$items = &$encoder->addArray($response, 'Item');

		// encode each item
		foreach ($collection as $product)
		{
			// encode the item
			$item = &$encoder->addContainerToArray($items);
			$encoder->addNumber($item, 'Id', $product->getId());
			$encoder->addString($item, 'Title', $product->getName());
			$encoder->addNumber($item, 'Price', $product->getPrice());
			$encoder->addString($item, 'ThumbnailUrl', $product->getSmallImageUrl(135,135));
		}

		// show the response
		$encoder->render($response);
	}

	// returns false on failure
	private function getMagentoOperatorFromSqlOperator($sql_operator)
	{
		$map = array('LIKE' => 'like');
		$sql_operator = strtoupper($sql_operator);
		if (isset($map[$sql_operator])) return $map[$sql_operator];
		return false;
	}
}

?>