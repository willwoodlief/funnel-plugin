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
global $ecombhub_fi_stats_object;
$stats = $ecombhub_fi_stats_object;

?>



<table class="ecomhub-fi-raw-detail">
    <tbody>
    <tr>
        <td>Total Completed</td>
        <td><?= $stats->number_completed ?></td>
        <td> </td>

        <td>Latest Process Time</td>
        <td><span class="ecomhub-fi-ts-to-local" data-ts="<?= $stats->max_created_at_ts ?>"></span> </td>
        <td> </td>

        <td>Total User Actions</td>
        <td>
		    <?=  ($stats->total_user_actions)  ?>
        </td>
        <td> </td>

        <td>Total Errors</td>
        <td><?= $stats->total_errors ?></td>
        <td> </td>

        <td>Total Items</td>
        <td><?= $stats->total_items ?></td>
        <td> </td>

        <td>Total of Orders</td>
        <td>$<?= number_format($stats->total_of_orders, 2, '.',',') ?></td>
        <td> </td>



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

