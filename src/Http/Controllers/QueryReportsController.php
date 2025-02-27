<?php
/**
 * Namespace containing controller classes responsible for handling query reports operations.
 */
namespace Programmer9WC\QueryBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryReportsController extends Controller
{

    protected $conn_key;

    /**
     * Constructor to establish database connection.
     */
    public function __construct()
    {
        $this->conn_key = connect_to_main_db();
    }

    /**
     * Handles listing of repeater query reports.
     * Supports AJAX-based pagination, sorting, and searching.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {

            // Retrieve datatable request parameters
            $draw = $request->get('draw');
            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Number of records per page

            $columnIndex_arr = $request->get('order');
            $columnName_arr = $request->get('columns');
            $order_arr = $request->get('order');
            $search_arr = $request->get('search');

            // Determine sorting parameters
            $columnIndex = $columnIndex_arr[0]['column']; // Column index
            $columnName = $columnName_arr[$columnIndex]['data']; // Column name
            $columnSortOrder = $order_arr[0]['dir']; // asc or desc
            $searchValue = $search_arr['value']; // Search value

            $database = env('QDB_DATABASE');
            // Query the database with filtering and ordering
            $data_arr = DB::connection($this->conn_key)->table('query_forms')->where('database',$database)->orderBy(!is_null($columnName) ? $columnName : 'id', $columnSortOrder)
                ->where( function ($query) use ($searchValue) {
                    $query->where('id', 'like', '%' . strtolower($searchValue) . '%')
                    ->orWhere('title', 'like', '%' . strtolower($searchValue) . '%');
                });

            // Retrieve record counts    
            $totalRecords = $data_arr->count();
            $totalRecordswithFilter = $data_arr->count();

            // Apply pagination
            $data_arr = $data_arr->skip($start)->take($rowperpage)->get();

            // Prepare response for the frontend datatable
            $response = array(
                "draw" => intval($draw),
                "iTotalRecords" => $totalRecords,
                "iTotalDisplayRecords" => $totalRecordswithFilter,
                "aaData" => $data_arr
            );

            return response()->json($response);

        }

        return view( 'wc_querybuilder::query-reports.index' );
    }

    /**
     * View a specific query report by its ID.
     */

    public function view($id)
    {

        // Retrieve query report details
        $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();
        $query_details = json_encode( json_decode( $query_form->query_details ) );

        return view( 'wc_querybuilder::query-reports.view', compact('query_form', 'query_details') );
    }

    /**
     * Edit a query report based on the provided ID.
     */
    public function edit($id)
    {
        // Fetch table lists and metadata
        $tables = get_table_list( connect_to_manage_db() );
        $tables_data = get_table_list_with_comment(connect_to_manage_db());

        // Retrieve the query form record
        $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();

        // Decode and structure query details
        $inputArray = (array)json_decode( $query_form->query_details );
        $query_details = convert_bracketed_keys_to_array($inputArray);

        // Return view with structured data
        return view('wc_querybuilder::query-reports.edit', compact('tables', 'query_form', 'query_details', 'tables_data'));
    }

    /**
     * Delete a query report by its ID.
     * Returns a JSON response indicating success or failure.
     */
    public function delete()
    {

        try {

            // Retrieve ID from request
            $id = request('id', 0);

            // Find the query report in the database
            $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();

            if ( $query_form ) {
                // Delete the record if found
                DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->delete();

                return response()->json([
                    'result'    => true,
                    'message'   => 'The query report was successfully deleted.',
                ], 200);

            } else {

                return response()->json([
                    'result'    => false,
                    'message'   => 'The query report was not found.',
                ], 200);

            }


        } catch (\Exception $e) {
            return response()->json([
                'result'    => false,
                'message'   => 'Failed to delete the query report.',
                'messages'  => $e->getMessage()
            ], 500);
        }

    }

}
