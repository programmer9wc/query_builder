@extends('wc_querybuilder::layout')

@section('css')

<style type="text/css">

    .join-card {
        border: 1px solid #dee2e6;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
    .remove-join {
        float: right;
        cursor: pointer;
        color: #dc3545;
    }
    .table-relationships {
        margin-top: 10px;
        padding: 10px;
        background-color: #e9ecef;
        border-radius: 5px;
    }
    .relationship-item {
        margin: 5px 0;
        padding: 5px;
        background-color: #fff;
        border-radius: 3px;
    }
    .condition-card {
        border: 1px solid #dee2e6;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 5px;
        background-color: #f8f9fa;
    }
    .remove-condition {
        float: right;
        cursor: pointer;
        color: #dc3545;
    }

</style>

@endsection

@section('content')

{{-- <button type="button" class="btn btn-primary btn-saveQueryModal" data-bs-toggle="modal" data-bs-target="#saveQueryModal" disabled>Save Query</button>
<!-- Modal -->
<div class="modal fade" id="saveQueryModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
        <form id="querySaveForm">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Save Query Builder</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
                <label>Title:</label>
                <input type="text" name="title" placeholder="Enter Title" class="form-control mt-1" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
      </form>
    </div>
  </div>
</div> --}}

<div class="card">
    <div class="card-header">

        <div class="d-flex justify-content-between">
            <h2>Add Query</h2>
            <div>
                <button type="button" class="btn btn-primary btn-saveQuery">Save Query</button>
            </div>
        </div>

    </div> {{-- end card header --}}

    <div class="card-body">

        <div class="mb-3">
            <h5>Query Title</h5>
            <div class="card">
                <div class="card-body">
                    <form id="querySaveForm">
                        <div class="mb-3">
                            <label>Title:</label>
                            <input type="text" name="title" id="queryReportTitle" placeholder="Enter Title" class="form-control mt-1" required>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <h5>Query Details</h5>
            <div class="card">
                <div class="card-body">
                    <form id="queryForm">
                        <!-- Main Table Selection -->
                        <div class="mb-3">
                            <label>Select Main Table:</label>
                            <select class="form-select main_table" id="mainTableSelect" name="main_table">
                                <option value="">Select a table</option>
                                @foreach($tables as $table)
                                    <option value="{{ $table }}">{{ $table }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Table Relationships -->
                        <div id="tableRelationships" class="table-relationships" style="display: none;">
                            <h6>Available Relationships:</h6>
                            <div id="relationshipsList"></div>
                        </div>

                        <!-- Join Tables Section -->
                        <div class="mb-3">
                            <label>Table Joins:</label>
                            <div id="joinsContainer"></div>
                            <button type="button" class="btn btn-secondary btn-sm" id="addJoin" disabled>Add Join</button>
                        </div>

                        <!-- Column Selection -->
                        <div class="mb-3">
                            <label>Select Columns:</label>
                            <div id="columnSelect" class="border p-3 d-flex align-items-start">
                                <!-- Columns will be populated dynamically -->
                            </div>
                        </div>

                        <!-- Conditions -->
                        <div class="mb-3">
                            <label>Conditions:</label>
                            <div id="conditions">
                                <div class="condition-card mb-2">
                                    <span class="remove-condition" style="font-size: 25px;">&times;</span>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <select class="form-select condition-column" name="conditions[0][column]">
                                                <option value="">Select Column</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="conditions[0][operator]">
                                                <option value="=">=</option>
                                                <option value="<"><</option>
                                                <option value=">">></option>
                                                <option value="LIKE">LIKE</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="conditions[0][value]" placeholder="Value">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="addCondition">Add Condition</button>
                        </div>

                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>

                    <!-- Results -->
                    {{-- <div class="mt-4 mb-3" id="resultsSummary" style="display: none;"></div> --}}
                    <div class="table-responsive mt-4">
                        {{-- <table class="table" id="resultsTable">
                            <thead><tr id="resultsHeader"></tr></thead>
                            <tbody id="resultsBody"></tbody>
                        </table> --}}
                        <div id="resultsTable"></div>
                    </div>

                </div>
            </div>
        </div>

    </div>{{-- end card body --}}
</div> {{-- end card main --}}


@endsection

@section('scripts')

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let availableColumns = {};
        let selectedColumns = [];
        let tableRelations = [];
        let tableRelationsColumns = [];

        // Handle main table selection
        $('#mainTableSelect').change(function() {
            const table = $(this).val();
            resetAllData();
            if (table) {
                loadTableRelations(table);
                loadTableColumns(table);
            } else {
                resetColumnSelection();
                updateColumnSelection();
            }
            $('.btn-saveQueryModal').prop('disabled', ( table ? false : true ));
        });

        function loadTableRelations(table) {
            $.get(`${site_url}/query-builder/relations/${table}`, function(relations) {

                $('#tableRelationships').addClass( 'd-none' );
                $('.join-card').remove();

                tableRelations = relations;
                let relationshipHtml = '';
                let relationShowFlag = false;
                
                relations.forEach(relation => {
                    relationShowFlag = true;
                    tableRelationsColumns[relation.referenced_table] = relation;
                    relationshipHtml += `
                        <div class="relationship-item">
                            ${relation.table_name}.${relation.column_name} → 
                            ${relation.referenced_table}.${relation.referenced_column}
                        </div>
                    `;
                });

                $('#relationshipsList').html(relationshipHtml);
                if ( relationShowFlag ) {
                    $('#tableRelationships').removeClass( 'd-none' );
                    $('#tableRelationships').show();
                }

                let rel_length = relations.length;
                $('#addJoin').prop('disabled', ( rel_length > 0 ? false : true ));

            });
        }

        function loadTableColumns(table) {
            $.get(`${site_url}/query-builder/columns/${table}`, function(columns) {
                availableColumns[table] = columns;
                resetColumnSelection();
                updateColumnSelection();
            });
        }

        function resetAllData() {
            availableColumns = {};
            selectedColumns = [];
            tableRelations = [];
            tableRelationsColumns = [];

            $('#addJoin').prop('disabled', true);
            $('#tableRelationships').css('display', 'none');
            $('#resultsSummary').css('display', 'none');
            $('#relationshipsList').html('');
            $('#joinsContainer').html('');
            // $('#conditions').html('');
            $('#resultsSummary').html('');
            $('#resultsHeader').html('');
            $('#resultsBody').html('');
        }

        function resetColumnSelection() {
            let selectTablesName = [];
            
            let mainTblName = $('#mainTableSelect').val();
            selectTablesName.push(mainTblName);
            
            $('.join-table').each(function() {
                let relTblName = $(this).val();
                selectTablesName.push(relTblName);
            });
            selectTablesName = $.unique(selectTablesName.sort());

            let resetAvailableColumns = {};
            Object.entries(availableColumns).forEach(([table, tableInfo]) => {
                if (Object.values(selectTablesName).includes(table)) {
                    resetAvailableColumns[table] = tableInfo;
                }
            });
            availableColumns = resetAvailableColumns;
        }

        // manage column selection
        $(document).on('click', '.column-select-checkbox', function() {
            let check_this = $(this);
            let table = check_this.attr( 'data-table_name' );
            let column = check_this.val();
            if (check_this.is(':checked')) {
                selectedColumns.push(column);
            } else {
                if (Object.values(selectedColumns).includes(column)) {
                    selectedColumns = selectedColumns.filter(function(selectedColumnsValue) {
                        return selectedColumnsValue !== column;
                    });
                }
            }
            selectedColumns = $.unique(selectedColumns.sort());
        });

        // Add Join
        $('#addJoin').click(function() {
            const joinId = Date.now();
            const joinHtml = `
                <div class="join-card" data-join-id="${joinId}">
                    <span class="remove-join" style="font-size: 25px;">&times;</span>
                    <div class="row">
                        <div class="col-md-2 d-none">
                            <select class="form-select d-none" name="joins[${joinId}][type]">
                                <option value="left" selected>LEFT JOIN</option>
                                <option value="right">RIGHT JOIN</option>
                                <option value="inner">INNER JOIN</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select join-table" name="joins[${joinId}][table]">
                                <option value="">Select Table</option>
                                ${tableRelations.map(relation => 
                                    `<option value="${relation.referenced_table}">${relation.referenced_table}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control first_column" name="joins[${joinId}][first_column]" 
                                placeholder="First Column" readonly>
                        </div>
                        <div class="col-md-1 text-center">
                            <span>=</span>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control second_column" name="joins[${joinId}][second_column]" 
                                placeholder="Second Column" readonly>
                        </div>
                    </div>
                </div>
            `;
            $('#joinsContainer').append(joinHtml);
        });

        // Remove Join
        $(document).on('click', '.remove-join', function() {
            $(this).closest('.join-card').remove();
            resetColumnSelection()
            updateColumnSelection();
        });

        // Handle join table selection
        $(document).on('change', '.join-table', function() {
            const join_table_this = $(this);
            const table = join_table_this.val();
            $('.join-table').removeClass( '.current-join-table-selection' )
            join_table_this.addClass( '.current-join-table-selection' )
            let is_disable = false;
            $('.join-table').each(function() {
                if ( 
                    !$(this).hasClass( '.current-join-table-selection' ) && 
                    $(this).val() == table && 
                    ( 
                        table != null && 
                        table != undefined && 
                        table != '' 
                    ) 
                ) {
                    is_disable = true;
                }
            });

            if ( is_disable ) {
                join_table_this.val('');
                // alert( 'This table is already selected. Please choose a different join.' );
                toastr.error('This table is already selected. Please choose a different join.');
            } else {
                if (table) {
                    let first_column = `${tableRelationsColumns[table]?.table_name}.${tableRelationsColumns[table]?.column_name}`;
                    let second_column = `${tableRelationsColumns[table]?.referenced_table}.${tableRelationsColumns[table]?.referenced_column}`;
                    join_table_this.closest('.join-card').find('.first_column').val( first_column );
                    join_table_this.closest('.join-card').find('.second_column').val( second_column );
                    loadTableColumns(table);
                } else {
                    join_table_this.closest('.join-card').find('.first_column').val('');
                    join_table_this.closest('.join-card').find('.second_column').val('');
                    resetColumnSelection();
                    updateColumnSelection();
                }
            }

        });

        function updateColumnSelection() {
            let columnHtml = '';

            Object.entries(availableColumns).forEach(([table, tableInfo]) => {
                let columns = tableInfo.columns;
                let comments = tableInfo.comments;

                columnHtml += `<div class="columnSelectTableWise m-2" id="columnSelectTable_${table}">`;
                columnHtml += `<h6>${table}</h6>`;
                columnHtml += `<div 
                                    class="" 
                                    style="
                                            overflow-y: auto;
                                            height: auto;
                                            max-height: 300px;"
                                >`;
                columns.forEach(column => {
                    columnHtml += `
                        <div class="form-check column-select-main">
                            <input class="form-check-input column-select-checkbox" type="checkbox" 
                                name="columns[${column.comment ? column.comment : column.name}]" value="${column.full_name}" 
                                id="col_${column.full_name}"
                                data-table_name="${table}"
                                ${Object.values(selectedColumns).includes(column.full_name) ? 'checked' : ''}
                                >
                            <label class="form-check-label column-select-label" for="col_${column.full_name}">
                                ${column.comment ? column.comment : column.name}
                            </label>
                        </div>
                    `;
                                // ${column.comment ? `<small class="text-muted">(${column.comment})</small>` : ''}
                });
                columnHtml += `</div>`;
                columnHtml += `</div>`;
            });
            $('#columnSelect').html(columnHtml);

            // Update condition dropdowns
            $('.condition-column').each(function() {
                let selected_column = $(this).val();
                let columnOptions = '<option value="">Select Column</option>';

                Object.entries(availableColumns).forEach(([table, tableInfo]) => {
                    let columns = tableInfo.columns;
                    columns.forEach(column => {
                        columnOptions += `<option value="${column.full_name}" 
                                            ${column.full_name == selected_column 
                                                ? 'selected' 
                                                : ''
                                            }>(${table}) ${column?.comment
                                                ? column?.comment
                                                : column?.name
                                            }</option>`;
                    });
                });
                $(this).html(columnOptions);
            });
        }

        // Add condition row
        let conditionCount = 1;
        $('#addCondition').click(function() {
            const columns = $('.condition-column').first().html();

            if ( columns == undefined || columns == 'undefined' ) {
                let columnOptions = '<option value="">Select Column</option>';
                Object.entries(availableColumns).forEach(([table, tableInfo]) => {
                    let tableColumns = tableInfo.columns;
                    tableColumns.forEach(tableColumn => {
                        columnOptions += `<option value="${tableColumn.full_name}" 
                                            >(${table}) ${tableColumn?.comment
                                                ? tableColumn?.comment
                                                : tableColumn?.name
                                            }</option>`;
                    });
                });
                columns = columnOptions;
            }

            const newRow = `
                <div class="condition-card mb-2">
                    <span class="remove-condition" style="font-size: 25px;">&times;</span>
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select condition-column" name="conditions[${conditionCount}][column]">
                                ${columns}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="conditions[${conditionCount}][operator]">
                                <option value="=">=</option>
                                <option value="<"><</option>
                                <option value=">">></option>
                                <option value="LIKE">LIKE</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="conditions[${conditionCount}][value]" placeholder="Value">
                        </div>
                    </div>
                </div>
            `;
            $('#conditions').append(newRow);
            conditionCount++;
        });

        // Remove condition row
        $(document).on('click', '.remove-condition', function() {
            $(this).closest('.condition-card').remove();
        });

        // // Form submission
        // $('#queryForm').submit(function(e) {
        //     e.preventDefault();

        //     var main_table = $('#mainTableSelect').val()
        //     if ( !main_table ) {
        //         toastr.error('Query details have not been selected.');
        //         return;
        //     }
            
        //     $('#resultsTable').closest('.table-responsive').append( '<div class="loader"></div>' );
            
        //     const formData = $(this).serializeArray();
        //     $.post(`${site_url}/query-builder/search`, formData, function(response) {
        //         $('#resultsTable').closest('.table-responsive').find('.loader').remove();
        //         if (response.error) {
        //             alert(response.message);
        //             return;
        //         }

        //         /*$('#resultsSummary').html(`
        //             <div class="alert alert-info">
        //                 Found ${response.total_count} records matching your criteria.
        //             </div>
        //         `).show();*/

        //         if (response.data.length > 0) {
        //             // Add headers
        //             const headers = Object.keys(response.data[0]);
        //             const headerRow = headers.map(header => `<th>${header}</th>`).join('');
        //             $('#resultsHeader').html(headerRow);

        //             // Add data rows
        //             const rows = response.data.map(row => {
        //                 const cells = headers.map(header => 
        //                     `<td>${row[header] !== null ? row[header] : ''}</td>`
        //                 ).join('');
        //                 return `<tr>${cells}</tr>`;
        //             }).join('');
        //             $('#resultsBody').html(rows);

        //             $('#resultsTable').DataTable();
        //         } else {
        //             $('#resultsBody').html(`
        //                 <tr><td colspan="100%" class="text-center">No results found</td></tr>
        //             `);
        //         }
        //     });
        // });

        // Form submission
        $('#queryForm').submit(function(e) {
            e.preventDefault();

            var main_table = $('#mainTableSelect').val()
            if ( !main_table || main_table == '' || main_table == undefined || main_table == 'undefined' ) {
                toastr.error('Query details have not been selected.');
                return;
            }
            
            const form_details = $(this).serializeArray();

            let formData = {};
            form_details.forEach(form_detail => {
                formData[form_detail.name] = form_detail.value;
            });

            var resultsTable = new Tabulator("#resultsTable", {
                layout: "fitColumns",
                ajaxURL: site_url + "/query-builder/search", // API endpoint
                ajaxConfig: "GET",
                ajaxParams: formData,
                filterMode: "remote", // Remote filtering
                pagination: true, // Enable pagination
                paginationMode: "remote", // Remote pagination
                paginationInitialPage: 1, // Initial page (default)
                paginationSize: 10, // Number of rows per page
                ajaxResponse: function (url, params, response) {

                    // Update columns dynamically
                    this.setColumns(response.columns);

                    // Return data to Tabulator
                    return response;
                },
            });
            
        });

        $('.btn-saveQuery').click(function() {
            $('#querySaveForm').submit();
        });

        // querySaveForm submission
        $('#querySaveForm').submit(function(e) {
            e.preventDefault();

            var queryReportTitle = $('#queryReportTitle').val()
            if ( !queryReportTitle || queryReportTitle == '' || queryReportTitle == undefined || queryReportTitle == 'undefined' ) {
                toastr.error('Query title has not been entered.');
                return;
            }
            
            var main_table = null;
            var query_details = {};

            const queryFormData = $('#queryForm').serializeArray();

            Object.entries(queryFormData).forEach(([index, query_detail]) => {
                query_details[query_detail.name] = query_detail.value;
                if ( query_detail.name == 'main_table' ) {
                    main_table = query_detail.value
                }
            });

            if ( !main_table || main_table == '' || main_table == undefined || main_table == 'undefined' ) {
                toastr.error('Query details have not been selected.');
            } else {

                var formData = new FormData(this);
                formData.append('query_details', JSON.stringify(query_details));

                toastr.clear();

                $.ajax({
                    url: `${site_url}/query-builder/save`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if ( response.result ) {
                            $('#saveQueryModal').modal('hide');
                            toastr.success( response.message );
                            window.location.href = '{{ route( 'query-report.index' ) }}';

                        } else if( !response.result ) {
                            toastr.error( response.message );
                        }
                    },
                    error: function (error) {
                        toastr.error( error.responseJSON.message );
                    }
                });

            }

        });

    });
</script>

@endsection