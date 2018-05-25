<?php
/**
 * @var {ChiSurvey} $survey_obj
 */
global $survey_obj;

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
switch ($survey_obj->section_name) {
    case 'vitacheck': {
        $text = get_option('ecombhub_fi_vitacheck_text');
        $state = 'vitacheck';
        break;
    }
    case 'psychologische': {
        $text = get_option('ecombhub_fi_psychologische_text');
        $state = 'psychologische';
        break;
    }
    default:{
        $text = 'unknown section [plugin error]';
        break;
    }
}

?>

<div class="ecomhub-fi-questions">
    <h2> Survey </h2>
    <div class="ecomhub-fi-customized-header">
        <?= $text ?>
    </div>
    <input type="hidden" id="ecomhub-fi-survey-code" class="ecomhub-fi-code" value="<?= $survey_obj->survey_code ?>">
    <input type="hidden" id="ecomhub-fi-state-holder" class="ecomhub-fi-state-info" value="<?= $state ?>">
    <div class="ecomhub-fi-questions-list">
    <?php foreach ($survey_obj->loaded_questions as $question) {?>
        <?php print $survey_obj->generate_question_html($question); ?>
    <?php } ?>
    </div>

</div>
