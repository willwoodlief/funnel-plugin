<?php


/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/public/partials
 */
global $ecombhub_fi_details_object;
$mail = $ecombhub_fi_details_object;
$body = $mail->email_body;
?>


<table class="ecomhub-fi-raw-detail">
    <tbody>
    <tr>
        <td>ID</td>
        <td><?= $mail->id ?></td>
        <td> </td>

        <td>Created At</td>
        <td><span class="ecomhub-fi-ts-to-local" data-ts="<?= $mail->created_at_ts ?>"></span> </td>
    </tr>
    <tr>
        <td>Completed</td>
        <td>
            <?=  ($mail->is_completed) ?
	            '<span class="fa fa-thumbs-up" style="text-align: center; color: #10b624;width:100%"> </span>'
            :
                "No"
             ?>
        </td>
        <td> </td>
        <td>Invoice Number</td>
        <td><?= $mail->invoice_number ?></td>
    </tr>
    <?php if ($mail->is_error) { ?>
        <tr>
            <td>Error</td>
            <td colspan="4">
                <span  style="text-align: left; color: #ff2b4d;width:100%">
                <?=  ($mail->error_message) ?>
                </span>

            </td>
        </tr>
    <?php } ?>

    <?php if ($mail->user_id_read) { ?>
        <tr>
            <td>User Name</td>
            <td><?= $mail->user_nicename ?></td>
            <td> </td>

            <td>User Email</td>
            <td><?= $mail->user_email ?></td>
        </tr>
    <?php } ?>

    <tr>
        <td>Email From</td>
        <td><?= $mail->email_from ?></td>
        <td> </td>

        <td>Email To</td>
        <td><?= $mail->email_to ?></td>
    </tr>

    <tr>
        <td>Email Subject</td>
        <td><?= $mail->email_subject ?></td>
        <td> </td>

        <td>Our Comments</td>
        <td><?= $mail->comments ?></td>
    </tr>

    <tr>
        <td>Email Body</td>
        <td colspan="4">
		    <?=  ($body) ?>
        </td>
    </tr>

    </tbody>
</table>

<script>
    (function ($) {
        $('span.ecomhub-fi-ts-to-local').each(function() {
            let ts = $(this).data('ts');
            if (ts) {
                let d = new Date(ts*1000);
                $(this).text(d.toLocaleDateString() + ' ' + d.toLocaleTimeString());
            }

        });
    })(jQuery);

</script>

