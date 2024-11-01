<?php
class Unienvios extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct($dados = null)
    {
        $this->id                 = $dados ? $dados['id_metodo'] : 'unienvios'; // Id for your shipping method. Should be uunique.
        $this->method_title       = $dados ? $dados['title'] : 'unienvios title';  // Title shown in admin
        $this->title              = $this->get_option('title'); // This can be added as an setting but for this example its forced.
        $this->enabled              = $this->get_option('enabled');
        $this->extra_days              = $this->get_option('extra_days');
        // $this->calculator              = $this->get_option('calculator');
        

        $this->init();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'title' => array(
                'title'         => __('Título'),
                'type'             => 'text',
                'description'     => __('Isso controla o título que o usuário vê durante o checkout.'),
                'default'        => __($this->method_title),
                'desc_tip'        => true
            ),
            'enabled' => array(
                'title'         => __('Ativar/Desativar'),
                'type'             => 'checkbox',
                'label'         => __('Ativar este método de entrega'),
                'default'         => 'yes',
            ),
            'extra_days' => array(
                'title'         => __('Dias Adicionais'),
                'type'             => 'number',
                'label'         => __('Quantidade de dias a mais para a entrega'),
                'default'         => 0,
            ),
            // 'calculator' => array(
            //     'title'         => __('Ativar/Desativar Calculadora de Frete'),
            //     'type'             => 'checkbox',
            //     'label'         => __('Ativar ou desativar campo de calcular frete na página do produto'),
            //     'default'         => 'yes',
            // ),
        );
    } // End init_form_fields()

    function init()
    {
        // Load the settings API
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = null)
    {
        
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();

        $dimensoes = [
            'estimate_height' => 0,
            'estimate_width' => 0,
            'estimate_length' => 0,
            'estimate_weight' => 0
        ];
        $count = 0;
        //LOOP ALL THE PRODUCTS IN THE CART
		$cubagemTotal = 0;
		$pesoTotal = 0;
        foreach ($items as $item => $values) {
            $count += 1;
            $_product =  wc_get_product($values['data']->get_id());

			$medidas = [
						doubleval($_product->get_height()) * intval( $values['quantity'] ),
						doubleval($_product->get_width()) * intval( $values['quantity'] ),
						doubleval($_product->get_length()) * intval( $values['quantity']),
                        doubleval($_product->get_weight()) * intval( $values['quantity'] )
			];

            $pesoTotal += $medidas[3];

			$cubagem = ( $medidas[0] * $medidas[1] * $medidas[2] )/6000;
			array_push($medidas, $cubagem);
		
			$cubagem = $cubagem;
			$cubagemTotal += $cubagem; 
            
        }

		$cubagemTotal = round($cubagemTotal, 4);

		$cubagemReversa = $cubagemTotal * doubleval(6000);

		$raizCubica = pow($cubagemReversa, 1.0/3.0);

		$raizCubica = round($raizCubica, 1);

        $dimensoes = [
                    'estimate_height' => $raizCubica,
                    'estimate_width' => $raizCubica,
                    'estimate_length' => $raizCubica,
                    'estimate_weight' => $pesoTotal
                ];

                
                // wp_send_json($dimensoes);
                
                $api = new API_UNIENVIOS('https://api.unienvios.com.br');
                $dados = [];
                $dados['email'] = get_option('unienvios_options')['email'];
                $dados['senha'] = get_option('unienvios_options')['senha'];
                $dados['cep'] = str_replace("-", "", $package['destination']['postcode']);
                $dados['cart_subtotal'] = doubleval($package['cart_subtotal']);
                
                
                
                if ($dados['cep'] != "") {
                    $quotation = $api->create_quotation($dados, $dimensoes);
                    $array_delivery_time = [];

                    foreach ($quotation->quotations as $key => $qut) {
                        
                        $name_minusculo = preg_replace("/[^\w\s]/", "", iconv("UTF-8", "ASCII//TRANSLIT", $qut->name));
                        $name_minusculo = strtolower(str_replace(" ", "_", $name_minusculo));
                        
                        array_push($array_delivery_time, ['qut_label' => $qut->name,'time'=>$qut->delivery_time + $this->extra_days]);

                        if ($name_minusculo == $this->id) {
                            $rate = array(
                                'label' => $this->title,
                                'cost' => $qut->final_price,
                                'calc_tax' => 'per_item',
                                'delivery_time' => $qut->delivery_time + $this->extra_days 
                            );
                            // wc_add_notice( __('debug: ', 'woothemes') .  json_encode($this->extra_days), 'error' );
                        // return;
                            
                    // Register the rate
                    $this->add_rate($rate);

                    WC()->session->set(
                        'cotacao',
                        array(
                            'cotation_name' => $qut->name,
                            'token' => $quotation->token,
                            'zipcode_destiny' => $dados['cep'],
                            'estimate_height' => $dimensoes['estimate_height'],
                            'estimate_width' => $dimensoes['estimate_width'],
                            'estimate_length' => $dimensoes['estimate_length'],
                            'estimate_weight' => $dimensoes['estimate_weight'],
                            'order_value' => $dados['cart_subtotal'],
                            'shipping_id' => $qut->id
                        )
                    );

                    WC()->session->set(
                        "entrega_" . $this->id,
                        array(
                            "shipping_id" => $qut->id
                        )
                    );
                } else {
                }
            }

            WC()->session->set(
                'array_delivery_time',
                $array_delivery_time
            );
        }
    }
}
