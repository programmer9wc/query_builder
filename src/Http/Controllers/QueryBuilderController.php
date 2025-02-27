<?php

/**
 * Namespace containing controller classes responsible for handling query builder operations.
 */

namespace Programmer9WC\QueryBuilder\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueryBuilderController extends Controller
{
    protected $conn_key;

    /**
     * Constructor to initialize the database connection.
     */
    public function __construct()
    {
        $this->conn_key = connect_to_manage_db();
    }

    /**
     * Display the query report page.
     *
     * @return \Illuminate\View\View The query report edit view.
     */
    public function index()
    {
        // Initialize query-related variables
        $query_form = null;
        $query_details = [];
        // Fetch the list of tables from the database
        $tables = get_table_list($this->conn_key);

        // Fetch additional table metadata including comments
        $tables_data = get_table_list_with_comment($this->conn_key);

        // Return the view with retrieved data

        return view('wc_querybuilder::query-reports.edit', compact('tables', 'query_form', 'query_details', 'tables_data'));
    }

    /**
     * Retrieve foreign key relationships for a given table.
     *
     * @param string $table The name of the database table.
     * @return \Illuminate\Http\JsonResponse JSON response containing the table relationships.
     */
    public function getRelations($table)
    {
        // Fetch foreign key relationships for the specified table
        $relations = get_table_relations($table, $this->conn_key);

        // Return the relations as a JSON response
        return response()->json($relations);
    }

    /**
     * Retrieve column details for a given table.
     *
     * @param string $table The name of the table.
     * @return \Illuminate\Http\JsonResponse JSON response with column details.
     */
    public function getColumns($table)
    {
        $is_join_table = request()?->is_join_table == 'yes' ? 'yes' : 'no';

        // Validate if the table exists to prevent SQL injection
        if (!in_array($table, get_table_list($this->conn_key))) {
            return response()->json(['error' => 'Invalid table name'], 400);
        }

        // Define query to retrieve column details
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
        ";

        // Exclude 'id' column for join tables
        if ($is_join_table == 'yes') {
            $query .= " AND COLUMN_NAME != 'id'";
        }

        $query .= " ORDER BY ORDINAL_POSITION";

        
        // Execute query and fetch column details
        $columns = DB::select($query, [get_database_name($this->conn_key), $table]);
        // Get column comments
        $comments = get_table_columns_comment($table, $this->conn_key);
        // Return response as JSON
        $response = ['columns' => $columns, 'comments' => $comments];

        return response()->json($response);
    }


    /**
     * Perform a database search based on provided filters, conditions, and joins.
     *
     * @param \Illuminate\Http\Request $request The request containing search parameters.
     * @return \Illuminate\Http\JsonResponse JSON response with search results.
     */
    public function search(Request $request)
    {
        // Retrieve request parameters
        $mainTable = $request->input('main_table');
        $joins = $request->input('joins', []);
        $selectedColumns = $request->input('columns', []);
        $conditions = $request->input('conditions', []);
        $filters = $request->input('filter', []);
        $page = $request->input('page', 1);
        $perPage = $request->input('size', 10);
        $skip = ( $page - 1 ) * $perPage;


        try {
            // Initialize query builder
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

            // Get total count before pagination
            $totalCount = $query->count();

            // Apply column selection
            if (!empty($selectedColumns)) {
                $query->select($selectedColumns);
            }
            // Fetch table and column information
            $tableInfo = get_multiple_table_info($tables, $this->conn_key);
            $columns = get_columns_for_listing($mainTable, $tableInfo, $selectedColumns);

            // Apply pagination
            $total = $query->get()->count();

            // Get paginated results
            $results = $query->skip($skip)->take($perPage)->get();

            return response()->json([
                'data' => $results,
                'last_page' => (int)ceil($total / $perPage),
                'current_page' => (int)$page,
                'total' => (int)$total,
                'columns' => $columns,
                'tableInfo' => $tableInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Query error',
                'message' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Save a query builder section based on the provided data.
     *
     * @param \Illuminate\Http\Request $request The request containing form data.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function save(Request $request)
    {

        try {

            $req_data = $request->all();

            $qry_id = ( array_key_exists( 'qry_id', $req_data ) && (int)$req_data[ 'qry_id' ] > 0 ) ? (int)$req_data[ 'qry_id' ] : 0;

            $query_details = array();
            if ( is_array( $req_data ) && array_key_exists( 'query_details', $req_data ) ) {
                $query_details = (array)json_decode( $req_data[ 'query_details' ] );
            }

            // Get database credentials
            $db_data = config( "database.connections.$this->conn_key" );
            $password = $db_data[ 'password' ] ?? null;
            if ( !is_null( $password ) && !empty( $password ) ) {
                $password = base64_encode( mange_db_pass_prefix() . '-v-' . $password );
            }
             // Prepare data for saving
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
            
            // Insert or update query form
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
                // Improved error handling with better exception catching
            session()->flash('error', 'Failed to save the query details.');
            
            return response()->json([
                'result'    => false,
                'message'   => 'Failed to save the query details.',
                'messages'  => $e->getMessage()
            ], 500);
        }

    }

}
