<?php

namespace Programmer9WC\QueryBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryReportsController extends Controller
{

    protected $conn_key;

    public function __construct()
    {
        $this->conn_key = connect_to_main_db();
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {

            $draw = $request->get('draw');
            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Rows display per page

            $columnIndex_arr = $request->get('order');
            $columnName_arr = $request->get('columns');
            $order_arr = $request->get('order');
            $search_arr = $request->get('search');

            $columnIndex = $columnIndex_arr[0]['column']; // Column index
            $columnName = $columnName_arr[$columnIndex]['data']; // Column name
            $columnSortOrder = $order_arr[0]['dir']; // asc or desc
            $searchValue = $search_arr['value']; // Search value

            $data_arr = DB::connection($this->conn_key)->table('query_forms')->orderBy(!is_null($columnName) ? $columnName : 'id', $columnSortOrder)
                ->where( function ($query) use ($searchValue) {
                    $query->where('id', 'like', '%' . strtolower($searchValue) . '%')
                    ->orWhere('title', 'like', '%' . strtolower($searchValue) . '%');
                });

            /*$filter_status = $request->filter_status;
            if ( !is_null( $filter_status ) ) {
                $data_arr->where( 'is_active', $filter_status );
            }*/

            $totalRecords = $data_arr->count();
            $totalRecordswithFilter = $data_arr->count();

            $data_arr = $data_arr->skip($start)->take($rowperpage)->get();

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

    public function view($id)
    {
        $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();
        $query_details = json_encode( json_decode( $query_form->query_details ) );

        return view( 'wc_querybuilder::query-reports.view', compact('query_form', 'query_details') );
    }

    public function edit($id)
    {
        $tables = get_table_list( connect_to_manage_db() );
        $tables_data = get_table_list_with_comment(connect_to_manage_db());

        $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();
        $inputArray = (array)json_decode( $query_form->query_details );
        $query_details = convert_bracketed_keys_to_array($inputArray);

        return view('wc_querybuilder::query-reports.edit', compact('tables', 'query_form', 'query_details', 'tables_data'));
    }

    public function delete()
    {

        try {

            $id = request('id', 0);

            $query_form = DB::connection($this->conn_key)->table('query_forms')->where('id', $id)->first();

            if ( $query_form ) {

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
