<?php
class API_UNIENVIOS
{

  function __construct($url)
  {
    $this->urlBase = $url;
  }

  public function create_quotation($dados, $dimensoes)
  {
    
    $endpoint = $this->urlBase . '/external-integration/quotation/get-quotations';

    $body = array(
      'zipcode_destiny' => $dados['cep'],
      'estimate_height' => $dimensoes['estimate_height'],
      'estimate_width' => $dimensoes['estimate_width'],
      'estimate_length' => $dimensoes['estimate_length'],
      'estimate_weight' => $dimensoes['estimate_weight'],
      'order_value' => $dados['cart_subtotal'],
    );


    $body = wp_json_encode($body);

    $options = [
      'body'        => $body,
      'headers'     => [
        'Content-Type' => 'application/json',
        'email' => $dados['email'],
        'password' => $dados['senha'],
      ],
      'data_format' => 'body',
      'sslverify' => false
    ];

  

    $response = wp_remote_post($endpoint, $options);

    // return $response;
    // exit;

    if (is_wp_error($response)) {
      return $response;
    } elseif (200 === $response['response']['code']) {
    }elseif($response['response']['code'] != 200){
      return ['status_code' => $response['response']['code'], 'message' => $response['response']['message'] ];
    }
    return json_decode($response['body']);
  }

  public function cadastrar_cotacao($dados)
  {
    $endpoint = $this->urlBase . '/external-integration/quotation/create';

    //calculo de cubagem
        $cubagemTotal = 0;
        $pesoTotal = 0;

        $priceOrderTotal = 0;

            $my_width = $dados['estimate_width'];
            $my_height = $dados['estimate_height'];
            $my_length = $dados['estimate_length'];
            $my_weight = $dados['estimate_weight'];

            $medidas = [
                doubleval($my_width) * 1,
                doubleval($my_height) * 1,
                doubleval($my_length) * 1,
                doubleval($my_weight) * 1
            ];

            $priceOrderTotal  += doubleval($item['price']) * 1;


            $pesoTotal += $medidas[3];

            $cubagem = ($medidas[0] * $medidas[1] * $medidas[2]) / 6000;
            array_push($medidas, $cubagem);

            $cubagem = $cubagem;
            $cubagemTotal += $cubagem;
        

        $cubagemTotal = round($cubagemTotal, 4);

        $cubagemReversa = $cubagemTotal * doubleval(6000);

        $raizCubica = pow($cubagemReversa, 1.0 / 3.0);

        $raizCubica = round($raizCubica, 1);


    $body = array(
      'zipcode_destiny' => $dados['zipcode_destiny'],
      'document_recipient' => $dados['document_recipient'],
      'name_recipient' => $dados['name_recipient'],
      'email_recipient' => $dados['email_recipient'],
      'phone_recipient' => $dados['phone_recipient'],
      'estimate_height' => $raizCubica,
      'estimate_width' => $raizCubica,
      'estimate_length' => $raizCubica,
      'estimate_weight' => $pesoTotal,
      'order_value' => $dados['order_value'],
      'address' => $dados['address'],
      'number' => $dados['number'],
      'city' => $dados['city'],
      'neighbourhood' => $dados['neighbourhood'],
      'state' => $dados['state'],
      'complement' => $dados['complement'],
      'shipping_id' => $dados['shipping_id']
    );


    $body = wp_json_encode($body);

    $options = [
      'body'        => $body,
      'headers'     => [
        'Content-Type' => 'application/json',
        'email' => $dados['email'],
        'password' => $dados['senha'],
        'token' => $dados['token_cotacao'],
      ],
      'data_format' => 'body',
      'sslverify' => false
    ];

    $response = wp_remote_post($endpoint, $options);

    if (is_wp_error($response)) {
      return $response;
    } elseif (200 === $response['response']['code']) {
    }

    return json_decode($response['body']);
  }
}
