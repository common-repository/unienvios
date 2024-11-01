<?php
global $product;
if ($product && $product->height && $product->width && $product->length && $product->weight) :
    //form
?>
    <form id="form-cep">
        <input type="text" id="cep" placeholder="00000-000">
        <input type="hidden" id="price_product" value="<?php esc_html_e( $product->price ) ?>">
        <input type="hidden" id="estimate_height" value="<?php esc_html_e( $product->height ) ?>">
        <input type="hidden" id="estimate_width" value="<?php esc_html_e( $product->width ) ?>">
        <input type="hidden" id="estimate_length" value="<?php esc_html_e( $product->length ) ?>">
        <input type="hidden" id="estimate_weight" value="<?php esc_html_e( $product->weight ) ?>">
        <input type="submit" value="Calcular Frete" class="buscar_metodos" id="submit">
    </form>
    <div id="table-envios" class="metodos_envio" style="display: none;">
        <table class="table-metodos">
            <thead>
                <tr>
                    <th>Forma de Envio</th>
                    <th>Custo Estimado</th>
                    <th>Entrega Estimada</th>
                </tr>
            </thead>
            <tbody class="methods">
            </tbody>
        </table>
    </div>
    <div style="display: none;" class="erro">
        <p>Não existem métodos de envio disponíveis para a sua área!</p>
    </div>
    <script type="text/javascript">
        jQuery('#cep').mask("00000-000")
        jQuery('.buscar_metodos').click(function(event) {
            event.preventDefault();
            jQuery('#submit').attr('value', 'Carregando...')
            jQuery('.methods .valores').remove()
            var cep = jQuery("#cep").val()
            jQuery.ajax({
                type: 'POST',
                url: "<?php echo admin_url('admin-ajax.php') ?>",
                data: {
                    "action": "unienvios_buscar_metodos_de_envios",
                    "cep": cep
                },
                success: function(data) {
                    // console.log(data)
                    jQuery('#submit').attr('value', 'Calcular Frete')
                    if (data['quotations']) {
                        jQuery('.metodos_envio').css('display', 'flex')
                        jQuery.each(data['quotations'], function(key, value) {
                            jQuery('.methods').append(
                                "<tr class='valores'><td>" + value['name'] + "</td><td>" +
                                value['final_price'].toLocaleString("pt-BR", {
                                    style: "currency",
                                    currency: "BRL"
                                }) + "</td><td>" +
                                value['delivery_time'] + " Dias</td></tr>"
                            )
                        })

                    } else {
                        jQuery('.erro').css('display', 'block')
                    }
                }
            });
            return false;
        });
    </script>
    
<?php

//alterar metodo de envio
// $method_rate_id = array('correios_pac');

// WC()->session->set( 'chosen_shipping_methods', $method_rate_id );
else :
?>
    <p>Cálculo de frete indisponível</p>
<?php
endif;
