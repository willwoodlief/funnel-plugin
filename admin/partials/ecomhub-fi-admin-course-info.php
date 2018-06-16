<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'ecomhub-fi-list-events.php';
$ecomhub_fi_course_info_array = EcomhubFiListEvents::get_courses_info();
?>

<div class="ecomhub-fi-course-info ecomhub-fi-product_ids">
    <table>
        <thead>
        <tr>
            <th>Course ID</th>
            <th>Title</th>
            <th>Membership Shop ID</th>
            <th>Membership Base</th>
            <th>Notes</th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $ecomhub_fi_course_info_array as $course ) { ?>
            <tr>
                <td>
                    <input type="hidden" class="ecomhub-fi-editable-course-fields" data-field="id"
                           value="<?= $course->id ?>">
                    <span><?= $course->id ?></span>
                </td>
                <td>
                    <span><?= $course->title ?></span>
                </td>
                <td>
                    <input class="ecomhub-fi-editable-course-fields" data-field="associated_shop_membership"
                           value="<?= $course->associated_shop_membership ?>" title="Associated Shop Membership ID">
                </td>

                <td>
                    <input class="ecomhub-fi-editable-course-fields" data-field="membership_base"
                           value="<?= $course->membership_base ?>" title="Membership Base Number">
                </td>

                <td>
                    <input class="ecomhub-fi-editable-course-fields" data-field="notes"
                           value="<?= $course->notes ?>" title="Notes for Course">
                </td>
            </tr>
		<?php } ?>
        </tbody>
    </table>
    <br>

    <div>
        <button type="button" class="button ecomhub-fi-save-course-info">
            Save Course Info
        </button>
        <div style="float: right;margin-right: 6em; color:red; font-weight: bold"
             class="ecomhub-fi-course-info-errors"></div>
    </div>

</div>


<!--suppress EqualityComparisonWithCoercionJS -->
<script>
    (function ($) {


        $(".ecomhub-fi-editable-course-fields").on('input', function () {
            $(this).addClass('ecomhub-fi-changed-detail');
        });


        $(".ecomhub-fi-save-course-info").click(function () {
            //get all .ecomhub-fi-editable-funnel-fields

            $('div.ecomhub-fi-course-info-errors').text('');

            var da_news = [];
            $("div.ecomhub-fi-course-info table tr").each(function () {
                var tr = $(this);
                var course_info = {};
                tr.find('.ecomhub-fi-editable-course-fields').each(function () {
                    var input = $(this);
                    var name = input.data('field');
                    course_info[name] = input.val();
                });
                if (course_info) {
                    da_news.push(course_info);
                }

            });

            ecomhub_fi_talk_to_backend('update_course_info', {course_info: da_news},
                function (data) {
                    console.log(data);
                    $('div.ecomhub-fi-course-info :input').removeClass('ecomhub-fi-changed-detail');
                }, function (message) {
                    $('div.ecomhub-fi-course-info-errors').html(message);
                    //clear all changed marks
                    $('div.ecomhub-fi-course-info :input').removeClass('ecomhub-fi-changed-detail');

                }

            );

        });


    })(jQuery);


</script>

