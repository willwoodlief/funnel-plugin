<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/admin/partials
 */
?>

<style>
    .slick-row {
        line-height: 16px;
    }

    .loading-indicator {
        display: inline-block;
        padding: 12px;
        background: white;
        -opacity: 0.5;
        color: black;
        font-weight: bold;
        z-index: 9999;
        border: 1px solid red;
        -moz-border-radius: 10px;
        -webkit-border-radius: 10px;
        -moz-box-shadow: 0 0 5px red;
        -webkit-box-shadow: 0 0 5px red;
        -text-shadow: 1px 1px 1px white;
    }

    .loading-indicator label {
        padding-left: 20px;
        background: url('https://6pac.github.io/SlickGrid/images/ajax-loader-small.gif') no-repeat center left;
    }
</style>

<div class="wrap">
    <h1>Funnel Integrations Page</h1>
    <div>
        <form method="post" action="options.php">
		    <?php
		    // This prints out all hidden setting fields
		    settings_fields( 'ecomhub-fi-options-group' );
		    do_settings_sections( 'comhub-fi-funnels' );
		    submit_button();
		    ?>
        </form>
    </div>
    <div class="ecomhub-fi-admin">
        <div class="ecomhub-fi-table">

            <div class="grid-header" style="width:100%">
                <label>Survey Results Search</label>
                <span style="float:right;display:inline-block;">
                <label for="ecomhub-fi-search-table">Search Invoice (partial or full) [enter]: </label>
                 <input type="text" id="ecomhub-fi-search-table" value="">
            </span>
            </div>
            <div id="myGrid" style="width:100%;height:600px;"></div>
            <div id="pager" style="width:100%;height:20px;"></div>
        </div>
        <div class="ecomhub-fi-detail">
            <div class="ecomhub-fi-stats">
                <span style="margin-bottom: 0.25em;font-weight: bold;font-size: larger"> Overall Stats for all Reports</span>
            </div>
            <div class="ecomhub-fi-details-here">

            </div>
        </div>
    </div>

</div>

<script>
    let grid;
    let loader = new Slick.Data.RemoteModel();

    let dateFormatter = function (row, cell, value, columnDef, dataContext) {
        let d = new Date(dataContext.created_at_ts * 1000);
        return '<span>' + d.toLocaleDateString() + '</span>';
    };

    let my_columns = [
        {id: "invoice_number", name: "invoice_id", field: "invoice_id", formatter: null, width: 90, sortable: true},
        {id: "user_id", name: "user_id", field: "user_id", formatter: null, width: 100, sortable: true},
        {
            id: "created_at_ts",
            name: "created at",
            field: "created_at_ts",
            formatter: dateFormatter,
            width: 90,
            sortable: true
        }

    ];
    let options = {
        rowHeight: 21,
        editable: false,
        enableAddRow: false,
        enableCellNavigation: false,
        enableColumnReorder: false
    };
    let loadingIndicator = null;
    jQuery(function () {

        function unused_param() {
        }

        grid = new Slick.Grid("#myGrid", loader.data, my_columns, options);


        grid.onViewportChanged.subscribe(function (e, args) {
            unused_param(e);
            unused_param(args);
            let vp = grid.getViewport();
            loader.ensureData(vp.top, vp.bottom);
        });
        grid.onSort.subscribe(function (e, args) {
            loader.setSort(args.sortCol.field, args.sortAsc ? 1 : -1);
            let vp = grid.getViewport();
            loader.ensureData(vp.top, vp.bottom);
        });
        loader.onDataLoading.subscribe(function () {
            if (!loadingIndicator) {
                loadingIndicator = jQuery("<span class='loading-indicator'><label>Buffering...</label></span>").appendTo(document.body);
                let g = jQuery("#myGrid");
                loadingIndicator
                    .css("position", "absolute")
                    .css("top", g.position().top + g.height() / 2 - loadingIndicator.height() / 2)
                    .css("left", g.position().left + g.width() / 2 - loadingIndicator.width() / 2);
            }
            loadingIndicator.show();
        });
        loader.onDataLoaded.subscribe(function (e, args) {
            for (let i = args.from; i <= args.to; i++) {
                grid.invalidateRow(i);
            }
            grid.updateRowCount();
            grid.render();
            loadingIndicator.fadeOut();
        });
        let search_element = jQuery("#ecomhub-fi-search-table");
        search_element.keyup(function (e) {
            if (e.which === 13) {
                loader.setSearch(jQuery(this).val());
                let vp = grid.getViewport();
                loader.ensureData(vp.top, vp.bottom);
            }
        });
        loader.setSearch(search_element.val());
        loader.setSort("created_at", -1);
        grid.setSortColumn("created_at", false);
        // load the first page
        grid.onViewportChanged.notify();

        grid.onClick.subscribe(function (e, args) {
            grid.setSelectedRows([args.row]);
            let tim = grid.getDataItem(args.row);
            ecomhub_fi_talk_to_backend('detail', {id: tim.id}, function (data) {
                jQuery('div.ecomhub-fi-details-here').html(data.html);
            });

        });

        grid.setSelectionModel(new Slick.RowSelectionModel({
            selectActiveRow: false
        }));
        // grid.invalidateAllRows();
        // grid.invalidate();
        // grid.render();
    })
</script>
