<?php
/*
Plugin Name: Unienvios
Plugin URI: https://unienvios.com.br/
Description: Plugin de Entrega
Version: 1.4.3
Author: Unienvios
Author URI: https://unienvios.com.br/
*/

/**
 * Check if WooCommerce is active
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	require_once __DIR__ . '/options.php';
	require_once __DIR__ . '/api.php';

	function unienvios_your_shipping_method_init()
	{

		if (!class_exists('Unienvios')) {
			require_once __DIR__ . '/ClassUnienvios.php';
		}
	}
		
			/* adds stylesheet file to the end of the queue */
			function unienvios_add_css_page_product()
			{
				$dir = plugin_dir_url(__FILE__);
				wp_enqueue_script( 'mask-jquery', plugins_url( 'assets/js/jquery.mask.js', __FILE__ ), array('jquery'), "0.1.0" );
				wp_enqueue_style('page-product-css', $dir . '/assets/css/page-product.css', array(), '0.1.0', 'all');
			}
			add_action('wp_enqueue_scripts', 'unienvios_add_css_page_product', PHP_INT_MAX);
		
		
		
			add_action('woocommerce_shipping_init', 'unienvios_your_shipping_method_init');
		
			function unienvios_add_your_shipping_method($methods)
			{
		
				$api = new API_UNIENVIOS('https://api.unienvios.com.br');
				$dados = [];
				$dados['email'] = sanitize_email( get_option('unienvios_options')['email'] );
				$dados['senha'] = get_option('unienvios_options')['senha'];
				$dados['cep'] = sanitize_text_field("03330000");
				$dados['cart_subtotal'] = sanitize_text_field( doubleval(150) );
		
				$dimensoes = [
					'estimate_height' => sanitize_text_field( doubleval(15) ),
					'estimate_width' => sanitize_text_field( doubleval(15) ),
					'estimate_length' => sanitize_text_field( doubleval(15) ),
					'estimate_weight' => sanitize_text_field( doubleval(15) ),
				];
		
				$quotation = $api->create_quotation($dados, $dimensoes);
				// echo "<pre>";
				// var_dump($quotation);
				// echo "</pre>";
				if ($quotation) {
		
					foreach ($quotation->quotations as $key => $qut) {
						$name_minusculo = preg_replace("/[^\w\s]/", "", iconv("UTF-8", "ASCII//TRANSLIT", $qut->name));
						$name_minusculo = strtolower(str_replace(" ", "_", $name_minusculo));
						$dados = ['id_metodo' => $name_minusculo, 'title' => $qut->name];
						$methods[] = (new Unienvios($dados));
					}
				}
		
				return $methods;
			}
		
		
			add_filter('woocommerce_shipping_methods', 'unienvios_add_your_shipping_method');
	

}


//salvar token
add_action('woocommerce_checkout_order_created', 'unienvios_add_custom_field_on_placed_order');
function unienvios_add_custom_field_on_placed_order($order)
{
	update_post_meta($order->get_id(), 'cotacao',  WC()->session->get('cotacao'));
	$name_shipping = WC()->session->get('chosen_shipping_methods')[0];
	update_post_meta($order->get_id(), 'shipping_id',  WC()->session->get("entrega_" . $name_shipping)['shipping_id']);
}

// define the woocommerce_order_status_changed callback 
function unienvios_action_woocommerce_order_status_changed($this_get_id, $this_status_transition_from, $this_status_transition_to, $instance)
{
	if ($this_status_transition_to == 'processing') :
		$api = new API_UNIENVIOS('https://api.unienvios.com.br');
		$order = wc_get_order($this_get_id);

		$cotacao = $order->get_meta('cotacao');
		$cotacao_shipping_id = $order->get_meta('shipping_id');
		// extras fields 
		$cpf = $order->get_meta('_billing_cpf');
		$number = $order->get_meta('_billing_number');
		$neighbourhood = $order->get_meta('_shipping_neighborhood');
		$complement = $order->get_meta('_billing_address_2') ? $order->get_meta('_billing_address_2') : $order->data['shipping']['address_2'];

		$dados = [
			'zipcode_destiny' => $order->data['shipping']['postcode'],
			'document_recipient' => $cpf ? $cpf : "00000000000000",
			'name_recipient' => $order->data['shipping']['first_name'].' '.$order->data['shipping']['last_name'],
			'email_recipient' => $order->data['billing']['email'],
			'phone_recipient' => $order->data['shipping']['phone'] ? $order->data['shipping']['phone'] : $order->data['billing']['phone'],
			'estimate_height' => $cotacao['estimate_height'],
			'estimate_width' => $cotacao['estimate_width'],
			'estimate_length' => $cotacao['estimate_length'],
			'estimate_weight' => $cotacao['estimate_weight'],
			'order_value' => $cotacao['order_value'],
			'address' => $order->data['shipping']['address_1'],
			'number' => $number ? $number : "NÃO INFORMADO",
			'city' => $order->data['shipping']['city'],
			'neighbourhood' => $neighbourhood ? $neighbourhood : "NÃO INFORMADO",
			'state' => $order->data['shipping']['state'],
			'complement' => $complement ? $complement : "NÃO INFORMADO",
			'shipping_id' => $cotacao_shipping_id,
			'token_cotacao' => $cotacao['token'],
			'email' => get_option('unienvios_options')['email'],
			'senha' => get_option('unienvios_options')['senha']
		];
		$quotation = $api->cadastrar_cotacao($dados);
        // error_log('teste abaixo');
        // error_log(json_encode($cotacao['estimate_height']));
	endif;
	//add message
};

// add the action 
add_action('woocommerce_order_status_changed', 'unienvios_action_woocommerce_order_status_changed', 10, 4);

function unienvios_campo_cep_pagina_do_produto()
{
	require_once __DIR__ . '/form-page-product.php';
}


add_action('woocommerce_after_add_to_cart_form', 'unienvios_campo_cep_pagina_do_produto', 99);

function unienvios_buscar_metodos_de_envios()
{

	$api = new API_UNIENVIOS('https://api.unienvios.com.br');
	$dados = [];
	$dados['email'] = sanitize_email( get_option('unienvios_options')['email'] );
	$dados['senha'] = get_option('unienvios_options')['senha'];
	$dados['cep'] =  sanitize_text_field( str_replace("-", "", $_POST['cep']) );
	$dados['cart_subtotal'] = sanitize_text_field( doubleval($_POST['price_product']) );

	$dimensoes = [
		'estimate_height' => 0,
		'estimate_width' => 0,
		'estimate_length' => 0,
		'estimate_weight' => 0,
	];


	$quotation = $api->create_quotation($dados, $dimensoes);

	if ($quotation) {
		wp_send_json($quotation);
	}
}

add_action('wp_ajax_unienvios_buscar_metodos_de_envios', 'unienvios_buscar_metodos_de_envios');
add_action('wp_ajax_nopriv_unienvios_buscar_metodos_de_envios', 'unienvios_buscar_metodos_de_envios');

// function teste2() {

// 	$api = new API_UNIENVIOS('https://api.unienvios.com.br');
// 		$dados = [];
// 		$dados['email'] = get_option('unienvios_options')['email'];
// 		$dados['senha'] = get_option('unienvios_options')['senha'];
// 		$dados['cep'] = "03330000";
// 		$dados['cart_subtotal'] = doubleval(0);

// 		$dimensoes = [
// 			'estimate_height' => doubleval(0),
// 			'estimate_width' => doubleval(0),
// 			'estimate_length' => doubleval(0),
// 			'estimate_weight' => doubleval(0),
// 		];

// 		$quotation = $api->create_quotation($dados, $dimensoes);


// }

// add_action('woocommerce_init', 'teste2');

function shipping_delivery_forecast( $shipping_method ) {
			
			$array_deliverys_times = WC()->session->get('array_delivery_time');
			// var_dump($shipping_method->label);
			foreach($array_deliverys_times as $times){
				if($times['qut_label'] == $shipping_method->label){
					$total = $times['time'];
				}
			}
	
			if ( $total ) {
				/* translators: %d: days to delivery */
				echo '<p><small>' . esc_html( sprintf( _n( 'Entrega em %d dia útil', 'Entrega em %d dias úteis', $total, 'unienvios' ), $total ) ) . '</small></p>';
			}
}

add_action( 'woocommerce_after_shipping_rate',  'shipping_delivery_forecast' , 100 );