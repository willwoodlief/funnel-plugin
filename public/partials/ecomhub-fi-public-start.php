<?php
 global $ecombhub_fi_custom_header;

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/public/partials
 */
  $start_text = get_option('ecombhub_fi_start_text');
?>

<div class="ecomhub-fi">
    <div class='ecomhub-fi-custom-header'> <?= $ecombhub_fi_custom_header ?></div>
    <div class="ecomhub-fi-html">
        <div class="ecomhub-fi-start">
            <div class='ecomhub-fi-custom-header ecomhub-fi-start-text'> <?= $start_text ?></div>
            <label for="ecomhub-fi-dob">Verjaardag</label><input type="date" name="ecomhub-fi-dob" id="ecomhub-fi-dob">
            <br>
            <label for="ecomhub-fi-code">Code</label><input type="text" name="ecomhub-fi-code" id="ecomhub-fi-code">
        </div>
    </div>
    <button id='ecomhub-fi-submit'> Submit </button>

</div>
