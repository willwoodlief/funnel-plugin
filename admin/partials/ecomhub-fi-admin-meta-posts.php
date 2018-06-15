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
global $ecombhub_fi_posts_array;
$posts = $ecombhub_fi_posts_array;

?>



<table class="ecomhub-fi-order-products">
    <thead>
    <tr>
        <th>Title</th>
        <th>Post ID</th>
        <th>Click Funnel Product ID</th>
        <th>Delete</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($posts as $post) {?>
    <tr>
        <td><?= $post->post_title?></td>
        <td><?= $post->id?></td>
        <td><?= $post->product_id?></td>
        <td><span style="cursor: pointer; text-decoration: underline" class="unbind-post"
                  data-post="<?= $post->id ?>"  data-product="<?= $post->product_id ?>">delete</span></td>
    </tr>
    <?php } ?>
    </tbody>
</table>

<script>
    (function ($) {
        $('table.ecomhub-fi-order-products .unbind-post').click(function(e) {
            var post_id = $(this).data('post');
            var product_id = $(this).data('product');
            ecomhub_fi_talk_to_backend('x_posts', {unbind: post_id,product_id:product_id }, options_success);

            function options_success(d) {

                $('div.product-ids-html-here').html(d.html);
            };
        });
    })(jQuery);


</script>



