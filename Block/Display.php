<?php

/**
 * Frontuser Integration
 * Copyright © 2017 Frontuser. All rights reserved.
 *
 * @category    Frontuser
 * @package     Frontuser_Integration
 * @author      Frontuser Team <support@frontuser.com>
 * @copyright   Frontuser (https://frontuser.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Frontuser\Integration\Block;

use Magento\Catalog\Model\Category;

class Display extends \Magento\Framework\View\Element\Template
{
	/**
	 * @var Registry
	 */
	protected $registry;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	protected $customerSession;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $checkoutSession;

	/**
	 * @var \Magento\CatalogInventory\Api\StockRegistryInterface
	 */
	protected $stockRegistry;

	/**
	 * @var \Magento\Catalog\Model\Product
	 */
	protected $productModel;

	/**
	 * @var \Magento\Review\Model\Review
	 */
	protected $reviewModel;

	/**
	 * @var \Magento\Checkout\Model\Cart
	 */
	protected $cartModel;

	/**
	 * @var \Magento\Sales\Model\Order
	 */
	protected $orderModel;

	/**
	 * @var ObjectManager
	 */
	private $manager;

	/**
	 * @var Product
	 */
	private $product;

	/**
	 * @var Category
	 */
	private $category;

	/**
	 * @var Page Title
	 */
	private $_pageTitle;

	/**
	 * Recipient frontuser config path
	 */
	const XML_PATH_FRONTUSER_WEBHASH = 'frontuser_section/general/frontuser_webhash';
	const XML_PATH_FRONTUSER_ENABLE = 'frontuser_section/general/frontuser_enable';
	const XML_PATH_FRONTUSER_MATRIX = 'frontuser_section/general/frontuser_matrix';
	const XML_PATH_FRONTUSER_PRODUCT = 'frontuser_section/matrixdata/matrixdata_product';
	const XML_PATH_FRONTUSER_USER = 'frontuser_section/matrixdata/matrixdata_user';

	/**
	 * Display constructor.
	 *
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Customer\Model\Session $customerSession,
	 * @param \Magento\Checkout\Model\Session $checkoutSession,
	 * @param \Magento\Catalog\Model\Product $productModel,
	 * @param \Magento\Review\Model\Review $reviewModel,
	 * @param \Magento\Checkout\Model\Cart $cartModel,
	 * @param \Magento\Sales\Model\Order $orderModel,
	 * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
	 * @param array $data
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Checkout\Model\Session $checkoutSession,
		\Magento\Catalog\Model\Product $productModel,
		\Magento\Review\Model\Review $reviewModel,
		\Magento\Checkout\Model\Cart $cartModel,
		\Magento\Sales\Model\Order $orderModel,
		\Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\View\Page\Title $pageTitle,
		array $data = []
	)
	{
		$this->registry = $registry;
		$this->customerSession = $customerSession;
		$this->checkoutSession = $checkoutSession;
		$this->stockRegistry = $stockRegistry;

		$this->productModel = $productModel;
		$this->reviewModel = $reviewModel;
		$this->cartModel = $cartModel;
		$this->orderModel = $orderModel;
        	$this->_pageTitle = $pageTitle;

		parent::__construct($context, $data);
	}

	/**
	 * @return bool
	 */
	public function isEnable()
	{
		$status = $this->_scopeConfig->getValue(self::XML_PATH_FRONTUSER_ENABLE);
		if(!empty( $status) && $status == 1) {
			if(!$this->getWebHash()) {
				return false;
			}
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isMatrixEnable()
	{
		$status = $this->_scopeConfig->getValue(self::XML_PATH_FRONTUSER_MATRIX);
		if(!empty( $status) && $status == 1) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function getWebHash()
	{
		$webhash = $this->_scopeConfig->getValue(self::XML_PATH_FRONTUSER_WEBHASH);
		if(!empty( $webhash )) {
			return $webhash;
		}
		return null;
	}

	/**
	 * @return mixed|null
	 */
	public function getProductMatrix()
	{
		$attributes = $this->_scopeConfig->getValue(self::XML_PATH_FRONTUSER_PRODUCT);
		if(!empty( $attributes )) {
			return unserialize( $attributes );
		}
		return null;
	}

	/**
	 * @return mixed|null
	 */
	public function getUserMatrix()
	{
		$attributes = $this->_scopeConfig->getValue(self::XML_PATH_FRONTUSER_USER);
		if(!empty( $attributes )) {
			return unserialize( $attributes );
		}
		return null;
	}

	/**
	 * @return bool
	 */
	private function isHomepage()
	{
		if ($this->_request->getFullActionName() == 'cms_index_index') {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function isProduct()
	{
		if ($this->_request->getFullActionName() == 'catalog_product_view') {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function isCategory()
	{
		if ($this->_request->getFullActionName() == 'catalog_category_view') {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function isCart()
	{
		if ($this->_request->getFullActionName() == 'checkout_cart_index') {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function isSuccess()
	{
		if ($this->_request->getFullActionName() == 'checkout_onepage_success') {
			return true;
		}
		return false;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		if($this->isHomepage()) {
			return 'home';
		}
		if($this->isProduct()) {
			return 'product';
		}
		if($this->isCategory()) {
			return 'category';
		}
		if($this->isCart()) {
			return 'cart';
		}
		if($this->isSuccess()) {
			return 'order_success';
		}

		$handler = $this->_request->getFullActionName();
		$handler = explode( "_", $handler);
		if(is_array( $handler) && count( $handler) > 0) {
			return current( $handler );
		}

		return $this->_request->getFullActionName();
	}

	/**
	 * @return Product
	 */
	private function getProduct()
	{
		if (is_null($this->product)) {
			$this->product = $this->registry->registry('product');
		}

		return $this->product;
	}

	/**
	 * @return Category
	 */
	private function getCategory()
	{
		if (is_null($this->category)) {
			$this->category = $this->registry->registry('current_category');
		}

		return $this->category;
	}

    /**
     * @return Page Title
     */
    public function getPageTitle()
    {
        if($pageBlock = $this->getLayout()->getBlock( 'page.main.title' )){
            $name = $pageBlock->getPageTitle();
            if ( is_object( $name ) ) {
                $name = $name->getText();
            }
            return $name;
        }
        return $this->_pageTitle->getShort();
    }

	/**
	 * @return array
	 */
	private function getUserDetail()
	{
		$_UserData = array();

		$customerSession = $this->customerSession;
		if($customerSession->isLoggedIn()) {
			$_UserData = array(
				'id'    => $customerSession->getCustomer()->getId(),
				'name'  => $customerSession->getCustomer()->getName(),
				'email' => $customerSession->getCustomer()->getEmail()
			);

			$attributes = $this->getUserMatrix();
			if(!empty( $attributes)) {
				foreach ($attributes as $attribute) {
					if(!empty( $attribute['field']) && !empty( $attribute['value'])) {
						$_UserData[ $attribute['field'] ] = $customerSession->getCustomer()->getData( $attribute['value'] );
					}
				}
			}
			unset( $attributes );
		}
		unset( $customerSession );
		return $_UserData;
	}

	/**
	 * @return array
	 */
	private function getReferrerDetail()
    {
        $_Referrer = array(
            'host' => $this->_request->getServer('HTTP_HOST'),
            'path' => $this->_request->getServer('REQUEST_URI'),
            'search' => $this->_request->getServer('QUERY_STRING'),
            'utm' => array(
                'medium' => $this->_request->getParam('medium'),
                'source' => $this->_request->getParam('source'),
                'campaign' => $this->_request->getParam('campaign'),
            )
        );

        return $_Referrer;
    }


	/**
	 * @return mixed
	 */
	private function getProductDetail()
	{
		$StockState = $this->stockRegistry->getStockItem($this->getProduct()->getId());

		$_ProductData = array(
			"id"            => $this->getProduct()->getId(),
			"sku"           => $this->getProduct()->getSku(),
			"name"          => $this->getProduct()->getName(),
			"description"   => $this->getProduct()->getDescription(),
			"cat_id"        => $this->getProduct()->getCategoryIds(),
			"stock"         => $StockState->getQty(),
			"currency"      => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
			"unit_price"    => $this->getProduct()->getPrice(),
			"final_price"   => $this->getProduct()->getFinalPrice(),
		);

		//TOOD: Add custom attributes to product object
		$attributes = $this->getProductMatrix();
		if(!empty( $attributes)) {
			foreach ($attributes as $attribute) {
				if(!empty( $attribute['field']) && !empty( $attribute['value'])) {
					$_ProductData[ 'field' ] = $this->getProduct()->getData( $attribute['value'] );
				}
			}
		}
		unset( $attributes);

		$relatedProducts = $this->getProduct()->getRelatedProducts();
		if (!empty($relatedProducts)) {
			foreach ($relatedProducts as $relatedProduct) {

				$product = $this->productModel->load($relatedProduct->getId());
				$StockState = $this->stockRegistry->getStockItem($product->getId());

				$_ProductData['related_products'][] = array(
					"pid" => $product->getId(),
					"sku" => $product->getSku(),
					"name" => $product->getName(),
					"stock" => $StockState->getQty(),
					"currency" => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
					"unit_price" => $product->getPrice(),
					"final_price" => $product->getFinalPrice(),
				);
				unset( $product ); unset( $StockState );
			}
		}
		unset( $relatedProducts );

		if($this->getProduct()->getTypeId() == "simple") {
			$_Options = $this->getProduct()->getOptions();
			if(!empty( $_Options)) {
				foreach ( $_Options as $_Option ) {
					foreach ( $_Option->getValues() as $value ) {
						$_ProductData['attributes'][ $_Option->getTitle() ][] = $value->getTitle();
					}
				}
			}
			unset( $_Options );
		}

		if($this->getProduct()->getTypeId() == "configurable") {
			$attributes = $this->getProduct()->getTypeInstance( true )->getConfigurableAttributesAsArray( $this->getProduct() );
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attribute ) {
					foreach ( $attribute['values'] as $data ) {
						$_ProductData['attributes'][ $attribute['label'] ][] = $data['label'];
					}
				}
			}
			unset( $attributes );
		}

		$reviews = $this->reviewModel->getCollection()
                         ->addStoreFilter($this->_storeManager->getStore()->getId())
                         ->addEntityFilter('product', $this->getProduct()->getId())
                         ->setDateOrder()->addRateVotes()->getItems();
		foreach ($reviews as $review) {
			$vote = $review->getRatingVotes()->getData('value');
			$_ProductData['reviews'][] = array(
				'comment' => $review->getDetail(),
				'rating' => !empty( $vote[0]['value'] )?$vote[0]['value']:0
			);
		}

		unset( $reviews); unset( $StockState );
		return $_ProductData;
	}


	/**
	 * @return array
	 */
	private function getCategoryDetail()
	{
		$_CategoryData = array(
			"id"    => $this->getCategory()->getId(),
            "name"  => $this->getCategory()->getName(),
		);

		$_CategoryData['listing'] = array(
			"search_term" => $this->_request->getServer('QUERY_STRING'),
	        "items_count" => 0,
	        "sorting" => array(
				"by" => $this->_request->getParam( 'product_list_order'),
	            "direction" => $this->_request->getParam( 'product_list_dir', 'asc')
			)
		);

		$proudcts = $this->getCategory()->getProductCollection()->addAttributeToSelect('*')->getItems();
		foreach ($proudcts as $product) {

			$StockState = $this->stockRegistry->getStockItem($product->getId());
			$_CategoryData['listing']['items'][] = array(
				"pid"         => $product->getId(),
				"sku"         => $product->getSku(),
				"name"        => $product->getName(),
				"stock"       => $StockState->getQty(),
				"currency"    => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
				"unit_price"  => $product->getPrice(),
				"final_price" => $product->getFinalPrice(),
			);
			$_CategoryData['listing']['items_count'] += 1;
			unset( $StockState );
		}

		unset( $proudcts );
		return $_CategoryData;
	}

	/**
	 * @return array
	 */
	private function getCartDetail()
	{
		$cart = $this->cartModel->getQuote();
		$_CartData = array();

		if($cart->getId()) {

			$_CartData = array(
				"quote_id"        => $cart->getId(),
				"items_qty"       => $cart->getItemsQty(),
				"currency"        => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
				"subtotal"        => $cart->getSubtotal(),
				"tax_amount"      => $cart->getTaxAmount(),
				"shipping_method" => '',
				"shipping_amount" => $cart->getShippingAmount(),
				"coupon_code"     => $cart->getCouponCode(),
				"discount_amount" => $cart->getSubtotal() - $cart->getSubtotalWithDiscount(),
				"created_on"      => $cart->getCreatedAt(),
				"updated_on"      => $cart->getUpdatedAt(),
				"grand_total"     => $cart->getGrandTotal()
			);

			$items = $cart->getItemsCollection()->getItems();
			foreach ($items as $product) {
				if($product->getParentItemId()) {
					continue;
				}
				$_CartData['cart_items'][] = array(
					"quote_item_id" => $product->getId(),
					"pid"         => $product->getProductId(),
					"sku"         => $product->getSku(),
					"name"        => $product->getName(),
					"currency"    => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
					"unit_price"  => $product->getPrice(),
					"final_price" => $product->getPrice(),
					"qty_added"   => $product->getRowTotal(),
					"row_total"   => $product->getQty(),
					"discount_amount" => $product->getDiscountAmount(),
					"created_on"      => $product->getCreatedAt(),
					"updated_on"      => $product->getUpdatedAt()
				);
			}
			unset( $items );
		}

		unset( $cart );
		return $_CartData;
	}

	/**
	 * @return array
	 */
	private function getOrderDetail()
	{
		$session = $this->checkoutSession;

		$_OrderData = array();

		$orderid = $session->getLastOrderId();
		if($orderid) {

			$order = $this->orderModel->load( $orderid );
			$_OrderData = array(
				"order_id" => $order->getId(),
			    "items_qty" => $order->getTotalItemCount(),
			    "currency" => $order->getOrderCurrencyCode(),
			    "subtotal" => $order->getSubtotal(),
			    "tax_amount" => $order->getTaxAmount(),
			    "shipping_method" => $order->getShippingMethod(),
			    "shipping_amount" => $order->getShippingAmount(),
			    "payment_method" => "",
			    "coupon_code" => $order->getCouponCode(),
			    "discount_amount" => $order->getDiscountAmount(),
			    "grand_total" => $order->getGrandTotal(),
				"created_on" => $order->getCreatedAt(),
				"updated_on" => $order->getUpdatedAt()
			);

			$billing = $order->getBillingAddress();
			$_OrderData['addresses']['billing'] = array(
				"name" => $billing->getFirstname(). ' '.$billing->getLastname(),
	            "address_1" => $billing->getData('street'),
	            "address_2" => "",
	            "city" => $billing->getCity(),
	            "region" => $billing->getRegion(),
	            "country" => $billing->getCountryId(),
	            "country_code" => $billing->getCountryId(),
	            "zipcode" => $billing->getPostcode()
			);

			$shipping = $order->getShippingAddress();
			$_OrderData['addresses']['shipping'] = array(
				"name" => $shipping->getFirstname(). ' '.$shipping->getLastname(),
				"address_1" => $shipping->getData('street'),
				"address_2" => "",
				"city" => $shipping->getCity(),
				"region" => $shipping->getRegion(),
				"country" => $shipping->getCountryId(),
				"country_code" => $shipping->getCountryId(),
				"zipcode" => $shipping->getPostcode()
			);

			$orderItems = $order->getAllItems();
			if(!empty( $orderItems)) {
				foreach ($orderItems as $item) {

					$_OrderData['ordered_items'][] = array(
						"ordered_item_id" => $item->getId(),
			            "pid" => $item->getProductId(),
			            "sku" => $item->getSku(),
			            "name" => $item->getName(),
			            "currency" => $order->getOrderCurrencyCode(),
						"unit_price"  => $item->getPrice(),
						"final_price" => $item->getPrice(),
			            "order_qty" => $item->getQtyOrdered(),
			            "row_total" => $item->getRowTotal(),
			            "tax_amount" => $item->getTaxAmount(),
			            "discount_amount" => $item->getDiscountAmount(),
						"created_on" => $item->getCreatedAt(),
						"updated_on" => $item->getUpdatedAt()
					);
				}
			}
			unset( $order ); unset( $orderItems );
			unset( $billing ); unset( $shipping );
		}

		unset( $session );
		return $_OrderData;
	}

	/**
	 * @param array $_MatrixData
	 *
	 * @return array
	 */
	public function getMatrixData($_MatrixData = array())
	{
		$_MatrixData['user'] = $this->getUserDetail();

		if($this->isHomepage()) {
			$_Data = $this->getReferrerDetail();
			if(!empty( $_Data )) {
				$_MatrixData['referrer'] = $_Data;
			}
			unset( $_Data );
		}

		if($this->isProduct()) {
			$_Data = $this->getProductDetail();
			if(!empty( $_Data )) {
				$_MatrixData['product'] = $_Data;
			}
			unset( $_Data );
		}

		if($this->isCategory()) {
			$_Data = $this->getCategoryDetail();
			if(!empty( $_Data )) {
				$_MatrixData['category'] = $_Data;
			}
			unset( $_Data );
		}

		if ($this->isCart()) {
			if($_Data = $this->getCartDetail()) {
				if(!empty( $_Data ) && $_Data["items_qty"] > 0) {
					$_MatrixData['cart'] = $_Data;
				}
				unset( $_Data );
			}
		}

		if($this->isSuccess()) {
			$_Data = $this->getOrderDetail();
			if(!empty( $_Data )) {
				$_MatrixData['order_success'] = $_Data;
			}
			unset( $_Data );
		}

		$_MatrixData = json_encode( array_filter( $_MatrixData ));

		return $_MatrixData;
	}

	/**
	 * Get total order amount
	 *
	 * @return string
	 */
	public function getRevenue()
	{
		$revenue = 0;

		$orderid = $this->checkoutSession->getLastOrderId();
		if ($orderid) {
			$order   = $this->orderModel->load( $orderid );
			$revenue = $order->getGrandTotal();
		}

		return number_format($revenue, 2, '.', '');
	}

	/**
	 * Get store currency code
	 *
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
	}


	public function encrypt_decrypt($action, $string)
	{
		$output = false;
		$encrypt_method = "AES-256-CBC";
		$secret_key = '!@#$%^&*';
		$secret_iv = 'frontuser';
		// hash
		$key = hash('sha256', $secret_key);

		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		if ( $action == 'encrypt' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		} else if( $action == 'decrypt' ) {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
		return $output;
	}
}