<?php


global $ecombhub_fi_details_object;
$mail = $ecombhub_fi_details_object;
$body = $mail->email_body;

/** @noinspection PhpUndefinedFieldInspection */
$gateways         = WC()->payment_gateways->get_available_payment_gateways();
$enabled_gateways = [];

if ( $gateways ) {
	foreach ( $gateways as $gateway ) {
		if ( $gateway->enabled == 'yes' ) {
			$enabled_gateways[] = $gateway;
		}
	}
}

try {
	$products = EcomhubFiListEvents::get_store_funnel_codes();
} catch (Exception $e) {
    print "<h5>Failed to Load Store Funnel Codes: {$e->getMessage()}</h5>";
}



?>

<script>
    var ecomhub_fi_org_post_ids = {};
    <?php foreach ( $mail->orders as $order ) {
        if (empty($order->post_product_id)) {continue;}            ?>
    if (!ecomhub_fi_org_post_ids.hasOwnProperty(<?= $order->post_product_id?>)) {
        ecomhub_fi_org_post_ids[<?= $order->post_product_id?>] = 1;
    } else {
        ecomhub_fi_org_post_ids[<?= $order->post_product_id?>] ++;
    }

    <?php } ?>
</script>

<table class="ecomhub-fi-overall-details">
    <thead>
    <tr>
        <th>User Name</th>
        <th>User Reference</th>
        <th>Invoice Number</th>
        <th>Comments</th>
        <th>Payment Gateway used to Track</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>
            <input class="ecomhub-fi-editable-funnel-fields" data-field="user_login"
                   data-old = "<?= $mail->user_login ?>"
                   value="<?= $mail->user_login ?>" title="User Name">
        </td>

        <td>
            <span> <?= $mail->user_id_reference ?> </span>
        </td>

        <td>
            <input class="ecomhub-fi-editable-funnel-fields"
                   data-field="invoice_number"

                   value="<?= $mail->invoice_number ?>" size="30" title="Invoice Number">
        </td>

        <td>
            <textarea class="ecomhub-fi-editable-funnel-fields"

                      data-field="comments" title="Comments"><?= $mail->comments ?></textarea>
        </td>
        <td style="padding: 0.5em">
			<?php
			print "<select class='ecomhub-fi-editable-funnel-fields' data-field='payment_method'>";
			foreach ( $enabled_gateways as $gateway ) {
				$code    = $gateway->id;
				$name    = $gateway->title;
				$default = '';
				if ( $mail->payment_type ) {
					if ( $code == $mail->payment_type ) {
						$default = "selected=\"selected\"";
					}
				}
				printf(
					'<option value="%s"  %s >%s</option>',
					$code, $default, $name
				);
			}
			print "</select>";
			?>
        </td>

    </tr>
    </tbody>
</table>
<br>

<div>
    <button type="button" class="button ecomhub-fi-save-changed-funnel">
        Save All Changes To This Mail
    </button>
    <div style="float: right;margin-right: 6em; color:red; font-weight: bold" class="ecomhub-fi-errors"></div>
</div>


<h2> Orders </h2>
<table>
    <tr>
        <td>

			<?php if ( empty( $mail->orders ) ) { ?>
                <h3>No orders with this mail</h3>

            <table class="ecomhub-fi-order-details">
                <thead>
                <tr>
                    <th>Funnel Product ID</th>
                    <th>Our Product ID</th>
                    <th>Payment Logged As</th>
                    <th>Order Total</th>
                    <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
			<?php } else { ?>
                <table class="ecomhub-fi-order-details">
                    <thead>
                    <tr>
                        <th>Funnel Product ID</th>
                        <th>Order</th>
                        <th>Payment Logged As</th>
                        <th>Order Total</th>
                        <th>Delete</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $mail->orders as $order ) {
						if (empty($order->post_product_id)) {continue;}  ?>
                        <tr>
                            <td>
                                <span><?= $order->funnel_product_id ?> </span>
                            </td>

                            <td>
	                            <?php
                                if ($order->order_id) {
	                            $wc_order = wc_get_order( $order->order_id );
	                            ?>
                                <a href="<?= $wc_order->get_edit_order_url() ?>" target="_blank">
                        <span class="ecomhub-fi-sub-order" >
                             <?= $order->post_title ?>
                        </span>
                                </a>

                              <?php } ?>
                            </td>

                            <td>
                                <span><?= $order->payment_type ?> </span>
                            </td>

                            <td>
                <span>
                    <?php if ( $order->order_total ) { ?>
                        $<?= round( ( floatval( $order->order_total ) + 0.00001 ) * 100 ) / 100 ?>
                    <?php } ?>
                </span>

                            </td>

                            <td class='ecomhub-fi-delete-holder'>
                        <span style="cursor: pointer; text-decoration: underline" class="ecomhub-fi-delete-order"
                              data-post="<?= $order->id ?>">delete</span>
                            </td>

                        </tr>

						<?php if ( $order->is_error ) { ?>
                            <tr style="margin-bottom: 1em">
                                <td colspan="1" style="font-weight: bold">Error</td>
                                <td colspan="4" style=" color: #ff2b4d;"> <?= $order->error_message ?></td>
                            </tr>

						<?php } ?>
					<?php } ?>
                    </tbody>
                </table>

			<?php } ?>
        </td>
        <td style="width: 10em;background-color: transparent"></td>
        <td>
            <table>
                <tr>
                    <td>
                        <select title="select product to add" class="ecomhub-fi-add-new-order-list">
                            <?php foreach ($products as $product) {
	                            $code    = $product->id;
	                            $name    = $product->post_title;
	                            printf(
		                            '<option value="%s"  %s >%s</option>',
		                            $code, $default, $name
	                            );
                             } ?>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="button ecomhub-fi-add-order-to-funnel">
                            Add Order
                        </button>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>


<h2> Mail Details </h2>

<table class="ecomhub-fi-raw-detail">
    <tbody>
    <tr>
        <td>ID</td>
        <td><?= $mail->id ?></td>
        <td></td>

        <td>Created At</td>
        <td><span class="ecomhub-fi-ts-to-local" data-ts="<?= $mail->created_at_ts ?>"></span></td>
        <td></td>

        <td>User Email in Notice</td>
        <td><?= $mail->email_from_notice ?></td>

    </tr>

	<?php if ( $mail->is_error ) { ?>
        <tr>
            <td>Error</td>
            <td colspan="7">
                <span style="text-align: left; color: #ff2b4d;width:100%">
                <?= ( $mail->error_message ) . ' <br> ' . $mail->extra_error_message ?>
                </span>

            </td>
        </tr>
	<?php } ?>

    <?php if ( $order->extra_order_id ) { ?>
        <tr>
            <td>Membership Product Sold</td>
            <td>

                <a href="<?= get_edit_post_link( $order->extra_order_product_id ) ?>" target="_blank">
                        <span class="ecomhub-fi-sub-order" >
                            <?= $order->extra_order_post_title ?>
                        </span>
                </a>
            </td>
            <td></td>

            <td>Membership Order ID</td>
            <td>
                <?php
                $wc_order = wc_get_order( $order->extra_order_id );
                ?>
                <a href="<?= $wc_order->get_edit_order_url() ?>" target="_blank">
                        <span class="ecomhub-fi-sub-order" >
                            Order # <?=  $order->extra_order_id ?>
                        </span>
                </a>
            </td>
            <td></td>

            <td>Membership Cost on Books</td>
            <td>$<?= round( ( floatval( $order->extra_order_total ) + 0.00001 ) * 100 ) / 100 ?></td>
        </tr>
    <?php } ?>


    <tr>
        <td>Email From</td>
        <td><?= $mail->email_from ?></td>
        <td></td>

        <td>Email To</td>
        <td><?= $mail->email_to ?></td>
        <td></td>

        <td>Email Subject</td>
        <td><?= $mail->email_subject ?></td>
    </tr>


    <tr>
        <td>Email Body</td>
        <td colspan="7">
			<?= ( $body ) ?>
        </td>
    </tr>

    </tbody>
</table>

<!--suppress EqualityComparisonWithCoercionJS -->
<script>
    (function ($) {


        $(".ecomhub-fi-editable-funnel-fields").on('input', function() {
            $(this).addClass('ecomhub-fi-changed-detail');
        });


        $(".ecomhub-fi-add-order-to-funnel").click(function() {

            var sel = $(".ecomhub-fi-add-new-order-list");
            var product_id = sel.val();
            var product_description = sel.find('option:selected').text();
            $("table.ecomhub-fi-order-details tbody").append(
                "<tr><td colspan='4' class='ecomhub-fi-changed-detail'><span class='ecomhub-fi-sub-order  ' data-product='"+
                  product_id+"'>"+ product_description +
                "</span> </td><td class='ecomhub-fi-delete-holder'>" +
                "<span style='cursor: pointer; text-decoration: underline' class='ecomhub-fi-delete-order'" +
                " data-post='"+ product_id +"' onclick='EcomhubFiDoRemovedOrder(null,this);'>Delete</span>" +
                "</td></tr>");
        });



      //  $( document ).on( "click", , EcomhubFiDoRemovedOrder );
      //  $( document ).on( "click", ".ecomhub-fi-restore-order", EcomhubFiDoAddedOrder );


        $(".ecomhub-fi-delete-order").click(EcomhubFiDoRemovedOrder);


        $('span.ecomhub-fi-ts-to-local').each(function () {
            let ts = $(this).data('ts');
            if (ts) {
                let d = new Date(ts * 1000);
                $(this).text(d.toLocaleDateString() + ' ' + d.toLocaleTimeString());
            }

        });

        $(".ecomhub-fi-save-changed-funnel").click(function() {
            //get all .ecomhub-fi-editable-funnel-fields

            $('div.ecomhub-fi-errors').text('');
            if (!ecomhub_fi_selected_mail_id) {return;}
            var changed_attributes = {id: ecomhub_fi_selected_mail_id};
            $(".ecomhub-fi-editable-funnel-fields.ecomhub-fi-changed-detail").each(function() {
                var input = $(this);
                var name = input.data('field');
                var value =  input.val();
                if (name === 'user_login') {
                    var older = input.data("old");
                    if (older === value ) {
                        return true;
                    }
                }
                changed_attributes[name] = value;
            }) ;


            var new_order_lookup = {};
            var new_order_array = [];
            //get all orders that are not ignored
            $('.ecomhub-fi-sub-order').not('.ecomhub-fi-ignore').each(function() {
                var that = $(this);
                var product = parseInt(that.data('product'));
                if (!new_order_lookup.hasOwnProperty(product)) {
                    new_order_lookup[product] = 1;
                } else {
                    new_order_lookup[product] ++;
                }
                new_order_array.push(product);
            });

            function objectEquals(obj1, obj2) {
                for (let i in obj1) {
                    if (obj1.hasOwnProperty(i)) {
                        if (!obj2.hasOwnProperty(i)) return false;
                        if (obj1[i] != obj2[i]) return false;
                    }
                }
                for (let i in obj2) {
                    if (obj2.hasOwnProperty(i)) {
                        if (!obj1.hasOwnProperty(i)) return false;
                        if (obj1[i] != obj2[i]) return false;
                    }
                }
                return true;
            }

            if (! objectEquals(new_order_lookup,ecomhub_fi_org_post_ids)) {
                changed_attributes['orders'] = new_order_array;
            }

            if (Object.keys(changed_attributes).length > 0) {
                ecomhub_fi_talk_to_backend('update_funnel', changed_attributes, function(data) {
                    jQuery('div.ecomhub-fi-details-here').html(data.html);
                }, function (data) {
                    //see if object or string
                    var message = '';
                    if (typeof data === 'string' || data instanceof String) {
                        message = data;
                    } else {
                        if (data.hasOwnProperty('message')) {
                            message = data.message;
                        } else {
                            message = "Error happened, but no message. Look in browser debug console for details";
                        }
                    }
                    $('div.ecomhub-fi-errors').html(message);
                });
            }

        });


    })(jQuery);

    function EcomhubFiDoRemovedOrder(event,which) {
        debugger;
        var that = jQuery(this);
        if (which) {
            that = jQuery(which);
        }
        var file  =  that.closest('tr').find('.ecomhub-fi-sub-order');
        //find out which state it is in
        if (file.hasClass('ecomhub-fi-ignore')) {
            that.closest('tr').find('td').not('.ecomhub-fi-delete-holder').removeClass('ecomhub-fi-removed-detail');
            that.closest('tr').find('td').not('.ecomhub-fi-delete-holder').addClass('ecomhub-fi-changed-detail');
            that.closest('tr').find('.ecomhub-fi-sub-order').removeClass('ecomhub-fi-ignore');
            that.removeClass('ecomhub-fi-restore-order').addClass('ecomhub-fi-delete-order').text("Delete");
        } else {
            that.closest('tr').find('td').not('.ecomhub-fi-delete-holder').removeClass('ecomhub-fi-changed-detail');
            that.closest('tr').find('td').not('.ecomhub-fi-delete-holder').addClass('ecomhub-fi-removed-detail');
            that.closest('tr').find('.ecomhub-fi-sub-order').addClass('ecomhub-fi-ignore');
            that.removeClass('ecomhub-fi-delete-order').addClass('ecomhub-fi-restore-order').text("Restore");

        }

    }


</script>

