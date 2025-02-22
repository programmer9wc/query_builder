<?php

namespace Programmer9WC\QueryBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryBuilderController extends Controller
{
    protected $conn_key;

    public function __construct()
    {
        $this->conn_key = connect_to_manage_db();
    }

    public function index()
    {
        $query_form = null;
        $query_details = [];
        $tables = get_table_list($this->conn_key);
        $tables_data = get_table_list_with_comment($this->conn_key);

        return view('wc_querybuilder::query-reports.edit', compact('tables', 'query_form', 'query_details', 'tables_data'));
    }

    public function getRelations($table)
    {
        // Get foreign key relationships
        $relations = get_table_relations($table, $this->conn_key);
        return response()->json($relations);
    }

    public function getColumns($table)
    {
        $is_join_table = request()?->is_join_table == 'yes' ? 'yes' : 'no';

        // Validate table name to prevent SQL injection
        if (!in_array($table, get_table_list($this->conn_key))) {
            return response()->json(['error' => 'Invalid table name'], 400);
        }

        if ($is_join_table == 'yes') {
            $query = "
            SELECT 
                CONCAT(TABLE_NAME, '.', COLUMN_NAME) as full_name,
                TABLE_NAME as table_name,
                COLUMN_NAME as name,
                COLUMN_COMMENT as comment,
                DATA_TYPE as type,
                IS_NULLABLE as nullable,
                COLUMN_DEFAULT as default_value
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME != 'id'
            ORDER BY ORDINAL_POSITION
        ";
        } else {
            $query = "
                SELECT 
                    CONCAT(TABLE_NAME, '.', COLUMN_NAME) as full_name,
                    TABLE_NAME as table_name,
                    COLUMN_NAME as name,
                    COLUMN_COMMENT as comment,
                    DATA_TYPE as type,
                    IS_NULLABLE as nullable,
                    COLUMN_DEFAULT as default_value
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ";
        }
        

        $columns = DB::select($query, [get_database_name($this->conn_key), $table]);

        $comments = get_table_columns_comment($table, $this->conn_key);

        $response = ['columns' => $columns, 'comments' => $comments];

        return response()->json($response);
    }

    public function search(Request $request)
    {

        $mainTable = $request->input('main_table');
        $joins = $request->input('joins', []);
        $selectedColumns = $request->input('columns', []);
        $conditions = $request->input('conditions', []);
        $filters = $request->input('filter', []);
        $page = $request->input('page', 1);
        $perPage = $request->input('size', 10);
        $skip = ( $page - 1 ) * $perPage;


        try {
            // Start building the query
            $query = DB::connection($this->conn_key)->table($mainTable);

            // Apply joins
            $tables = [];
            $tables[] = $mainTable;
            foreach ($joins as $join) {
                if (empty($join['table']) || empty($join['type']) || 
                    empty($join['first_column']) || empty($join['second_column'])) {
                    continue;
                }

                $tables[] = $join['table'];

                $joinType = strtolower($join['type']);
                $method = match($joinType) {
                    'left' => 'leftJoin',
                    'right' => 'rightJoin',
                    'inner' => 'join',
                    default => 'leftJoin'
                };

                $query->$method($join['table'], $join['first_column'], '=', $join['second_column']);
            }

            // Apply conditions
            foreach ($conditions as $condition) {
                if (!empty($condition['column']) && !empty($condition['operator']) && isset($condition['value'])) {
                    if ($condition['operator'] === 'LIKE') {
                        $query->where($condition['column'], 'LIKE', '%' . $condition['value'] . '%');
                    } else {
                        $query->where($condition['column'], $condition['operator'], $condition['value']);
                    }
                }
            }

            // Apply filters
            foreach ($filters as $filter) {
                if (!empty($filter['field']) && !empty($filter['type']) && isset($filter['value'])) {
                    if ($filter['type'] === 'like') {
                        $query->where($filter['field'], 'LIKE', '%' . $filter['value'] . '%');
                    } else {
                        $query->where($filter['field'], $filter['type'], $filter['value']);
                    }
                }
            }

            // Get total count
            $totalCount = $query->count();

            // Get results with selected columns
            if (!empty($selectedColumns)) {
                $query->select($selectedColumns);
                // $query->select( arrange_records_column_comment( $selectedColumns ) );
            }

            $tableInfo = get_multiple_table_info($tables, $this->conn_key);
            $columns = get_columns_for_listing($mainTable, $tableInfo, $selectedColumns);


            $total = $query->get()->count();
            $results = $query->skip($skip)->take($perPage)->get();

            return response()->json([
                'data' => $results,
                'last_page' => (int)ceil($total / $perPage),
                'current_page' => (int)$page,
                'total' => (int)$total,
                'columns' => $columns,
                'tableInfo' => $tableInfo,
            ]);

            // return response()->json([
            //     'data' => $results,
            //     'total_count' => $totalCount
            // ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Query error',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    public function save(Request $request)
    {

        try {

            $req_data = $request->all();

            $qry_id = ( array_key_exists( 'qry_id', $req_data ) && (int)$req_data[ 'qry_id' ] > 0 ) ? (int)$req_data[ 'qry_id' ] : 0;

            $query_details = array();
            if ( is_array( $req_data ) && array_key_exists( 'query_details', $req_data ) ) {
                $query_details = (array)json_decode( $req_data[ 'query_details' ] );
            }

            $db_data = config( "database.connections.$this->conn_key" );

            $password = $db_data[ 'password' ] ?? null;
            if ( !is_null( $password ) && !empty( $password ) ) {
                $password = base64_encode( mange_db_pass_prefix() . '-v-' . $password );
            }

            $store_data = [ 
                'title'             => $req_data[ 'title' ] ?? null,
                'query_details'     => json_encode( $query_details ),
                'host'              => $db_data[ 'host' ] ?? null,
                'port'              => $db_data[ 'port' ] ?? null,
                'database'          => $db_data[ 'database' ] ?? null,
                'username'          => $db_data[ 'username' ] ?? null,
                'password'          => $password,
            ];

            $db_key = connect_to_main_db();

            $if_exists = DB::connection( $db_key )->table( 'query_forms' )->where( 'id', $qry_id )->first();
            if ( $if_exists ) {
                $in_query_forms = DB::connection( $db_key )->table( 'query_forms' )->where( 'id', $qry_id )->update( $store_data );
            } else {
                $qry_id = DB::connection( $db_key )->table( 'query_forms' )->insertGetId( $store_data );
            }

            $query_forms = DB::connection( $db_key )->table( 'query_forms' )->where( 'id', $qry_id )->first();

            session()->flash('success', 'The query details were successfully saved.');

            return response()->json([
                'result'    => true,
                'message'   => 'The query details were successfully saved.',
                'data'      => $query_forms,
            ], 200);
        } catch (\Exception $e) {

            session()->flash('error', 'Failed to save the query details.');
            
            return response()->json([
                'result'    => false,
                'message'   => 'Failed to save the query details.',
                'messages'  => $e->getMessage()
            ], 500);
        }

    }

}
