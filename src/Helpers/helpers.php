<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;


/**
 * Helper function for testing.
 * This function simply returns a string confirming that helper functions are working.
 */
function text_helpers()
{
	return 'Helper functions testing successfully completed.';
}


/**
 * Get database password prefix.
 * This function returns a predefined string used as a prefix for database passwords.
 */
function mange_db_pass_prefix()
{
	return 'QBDM';
}

/**
 * Configure a dynamic database connection.
 * This function sets up a new database connection dynamically based on provided configuration.
 *
 * @param string $connectionName The name of the connection to configure.
 * @param array $config An array containing database connection details such as host, port, username, etc.
 */
function connect_to_database($connectionName, $config)
{
    // Add the dynamic database connection
    config([
        "database.connections.$connectionName" => [
            'driver'    => 'mysql',
            'host'      => $config['host'],
            'port'      => $config['port'],
            'database'  => $config['database'],
            'username'  => $config['username'],
            'password'  => $config['password'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],
    ]);

}

/**
 * Connect to the manage database.
 * Captures request environment variables and sets up a connection for the manage database.
 *
 * @return string The name of the configured database connection.
 */
function connect_to_manage_db()
{
	$connectionName = 'manage_db';

	$request = Request::capture();

	connect_to_database($connectionName, [
		'host'     => $request->server('QDB_HOST'),
		'port'     => $request->server('QDB_PORT'),
		'database' => $request->server('QDB_DATABASE'),
		'username' => $request->server('QDB_USERNAME'),
		'password' => $request->server('QDB_PASSWORD'),
	]);

	return $connectionName;
}

/**
 * Connect to the main database.
 * Captures request environment variables and sets up a connection for the main database.
 *
 * @return string The name of the configured database connection.
 */
function connect_to_main_db()
{
	$connectionName = 'mysql';

	$request = Request::capture();

	connect_to_database($connectionName, [
        'host'     => $request->server('DB_HOST'),
        'port'     => $request->server('DB_PORT'),
        'database' => $request->server('DB_DATABASE'),
        'username' => $request->server('DB_USERNAME'),
        'password' => $request->server('DB_PASSWORD'),
    ]);

	return $connectionName;
}

/**
 * Retrieve the name of the database for a given connection.
 * This function returns null if the database name cannot be determined.
 *
 * @param string $connectionName The database connection name.
 * @return string|null The name of the database or null if not found.
 */
function get_database_name($connectionName = 'mysql')
{
	$database = null;
	try {
		$database = DB::connection($connectionName)->getDatabaseName();
		if ( is_null( $database ) || empty( $database ) ) {
			$database = null;
		}
	} catch (\Exception $e) {
		$database = null;
	}
	return $database;
}

/**
 * Get the table prefix of the current database connection.
 * Returns null if there is no prefix.
 *
 * @return string|null The table prefix or null if not found.
 */
function get_table_prefix()
{
	$prefix = null;
	try {
		$prefix = DB::getTablePrefix();
		if ( is_null( $prefix ) || empty( $prefix ) ) {
			$prefix = null;
		}
	} catch (\Exception $e) {
		$prefix = null;
	}
	return $prefix;
}

/**
 * List of database tables that should be skipped (ignored).
 *
 * @return array List of table names to be excluded from operations.
 */
function get_skip_tables()
{
	return [
		'cache',
		'cache_locks',
		'failed_jobs',
		'job_batches',
		'jobs',
		'migrations',
		'password_reset_tokens',
		'sessions',
	];
}

/**
 * Retrieve a list of tables from a database connection, excluding the skipped tables.
 *
 * @param string $connectionName The database connection name.
 * @return array List of table names.
 */
function get_table_list($connectionName = 'mysql')
{
	$tables = [];

	try {
		$tables = DB::connection($connectionName)->select('SHOW TABLES');
	} catch (Exception $e) {
		if ( $databaseName = get_database_name($connectionName) ) {
			$tables = DB::connection($connectionName)->select('SELECT table_name FROM information_schema.tables WHERE table_schema = ?', [$databaseName]);
		}
	}

	$tables = array_map(function($table) {
		return reset($table);
	}, $tables);

	$tables = array_values( array_diff( $tables, get_skip_tables() ) );

	return $tables;
}

/**
 * Retrieve table comments from the database schema.
 *
 * @param string $connectionName The database connection name.
 * @return array List of tables with their respective comments.
 */
function get_table_list_with_comment($connectionName = 'mysql')
{
	$tables = [];

	if ( $databaseName = get_database_name($connectionName) ) {
		$tables = DB::connection($connectionName)->select('SELECT table_name As table_name, table_comment AS table_comment FROM information_schema.tables WHERE table_schema = ?', [$databaseName]);
	}

	$tables = collect($tables)->keyBy('table_name');
	$tables = ( $tables && count($tables) > 0 ) ? $tables->toarray() : [];
	return $tables;
}

/**
 * Retrieve column comments for a given table.
 *
 * @param string $table The table name.
 * @param string $connectionName The database connection name.
 * @return array|null Array of column comments or null if not found.
 */
function get_table_columns_comment($table, $connectionName = 'mysql')
{
	$comments = null;
	if ( !is_null($table) && !empty($table) ) {
		$tableInfo = DB::select("
			SELECT 
			COLUMN_NAME as name,
			COLUMN_COMMENT as comment
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
			", [get_database_name($connectionName), $table]
		);

		$comments = collect($tableInfo)->keyBy('name');
	}

	return $comments;
}

/**
 * Retrieve column comments for a given table.
 *
 * @param string $table The table name.
 * @param string $connectionName The database connection name.
 * @return array|null Array of column comments or null if not found.
 */
function get_table_info($table, $connectionName = 'mysql')
{
	$comments = [];
	if ( !is_null($table) && !empty($table) ) {
		$tableInfo = DB::select("
			SELECT 
			COLUMN_NAME as name,
			COLUMN_COMMENT as comment,
			TABLE_NAME as table_name
			FROM information_schema.COLUMNS 
			WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
			ORDER BY ORDINAL_POSITION ASC
			", [get_database_name($connectionName), $table]
		);

		if ( $tableInfo && count( $tableInfo ) > 0 ) {
			foreach ( $tableInfo as $tblInfo) {
				$tblInfoKey = $tblInfo->table_name . '.' .$tblInfo->name;
				$comments[ $tblInfoKey ] =  ( !is_null( $tblInfo?->comment ) && !empty( $tblInfo?->comment ) ) ? $tblInfo->comment : $tblInfoKey;
			}
		}
	}

	return $comments;
}

/* -------------------------------------------------------------------------------------------------------------------------------------------------- */
/**
 * Get multiple table information.
 */
function get_multiple_table_info($tables = [], $connectionName = 'mysql')
{
	$tableInfo = [];

	if ( is_array( $tables ) && count( $tables ) > 0 ) {
		foreach ( $tables as $table ) {
			$tableInfoDetails = get_table_info($table, $connectionName);
			if ( is_array( $tableInfoDetails ) && count( $tableInfoDetails ) > 0 ) {
				$tableInfo = array_merge($tableInfo, $tableInfoDetails);
			}
		}
	}

	return $tableInfo;
}

/**
 * Get columns mappings for listing.
 *
 * This function processes table column information and prepares an array of columns
 * that can be used for display purposes, including sorting and filtering options.
 *
 * @param string|null $mainTable The main table name.
 * @param array $tableInfo An array containing column comments or metadata.
 * @param array $selectedColumns An array of selected columns to be used instead of full table info.
 * @return array An array of formatted column data.
 */
function get_columns_for_listing($mainTable = null, $tableInfo = [], $selectedColumns = [])
{
	$columns = $selected_columns = $columnsInfo = [];

	// Ensure the main table is not null or empty
	if ( !is_null( $mainTable ) && !empty( $mainTable ) ) {

		 // If selected columns are provided, store them in a key-value format
		if ( is_array( $selectedColumns ) && count( $selectedColumns ) > 0 ) {
			foreach ( $selectedColumns as $scol_key => $scol_value ) {
				$selected_columns[ $scol_value ] = $scol_key;
			}
		}

		// Use selected columns if available; otherwise, fallback to table info
		if ( is_array( $selected_columns ) && count( $selected_columns ) > 0 ) {
			$columnsInfo = $selected_columns;
		} elseif ( is_array( $tableInfo ) && count( $tableInfo ) > 0 ) {
			$columnsInfo = $tableInfo;
		}

		 // Process each column and structure the output array
		if ( is_array( $columnsInfo ) && count( $columnsInfo ) > 0 ) {
			foreach ( $columnsInfo as $field => $title ) {
				// Split the field into table name and column name (if prefixed)
				$field_split = explode('.', $field);

				$field_table = ( is_array( $field_split ) && count( $field_split ) > 0 ) ? $field_split[0] : null;
				$field_name = ( is_array( $field_split ) && count( $field_split ) > 1 ) ? $field_split[1] : null;

				// Determine if the column should be merged based on the ID check
				$column_merge_flag = ( $field_name == 'id' && $field_table != $mainTable ) ? false : true;
				if ( $column_merge_flag ) {
					$columns[] = [
						'field' => $field_name,   // Column name
                        'title' => $title,        // Display title (from comments or selected columns)
                        'sorter' => 'string',     // Default sorting type
                        'headerSort' => true,     // Enable sorting in UI
                        'headerFilter' => 'input' // Enable filtering
					];
				}
			}
		}

	}

	return $columns;
}

/**
 * Retrieve table relationships from the database schema.
 *
 * @param string $table The table name.
 * @param string $connectionName The database connection name.
 * @return array|null Array of table relationships or null if not found.
 */
function get_table_relations($table, $connectionName = 'mysql')
{
	$relations = null;
	if ( !is_null($table) && !empty($table) ) {
		$relations = DB::select("
			SELECT 

			IF(TABLE_NAME='$table', TABLE_NAME, REFERENCED_TABLE_NAME) as table_name,
			IF(TABLE_NAME='$table', COLUMN_NAME, REFERENCED_COLUMN_NAME) as column_name,
			IF(TABLE_NAME='$table', REFERENCED_TABLE_NAME, TABLE_NAME) as referenced_table,
			IF(TABLE_NAME='$table', REFERENCED_COLUMN_NAME, COLUMN_NAME) as referenced_column

			FROM information_schema.KEY_COLUMN_USAGE
			WHERE 
			REFERENCED_TABLE_SCHEMA = ? AND 
			(TABLE_NAME = ? OR REFERENCED_TABLE_NAME = ?) AND
			REFERENCED_TABLE_NAME IS NOT NULL
			", [get_database_name($connectionName), $table, $table]
		);
	}
	
	return $relations;
}

/**
 * Arrange records column comments.
 *
 * This function modifies an array of selected columns by appending alias information
 * using the provided column comments.
 *
 * @param array $selectedColumns Array of selected columns with their comments.
 * @return array Modified array of selected columns with alias formatting.
 */
function arrange_records_column_comment($selectedColumns)
{
	$selected_columns = $selectedColumns;
	if ( isset( $selectedColumns ) && is_array( $selectedColumns ) && count( $selectedColumns ) > 0 ) {
		foreach ($selectedColumns as $comment => $selectedColumn) {
			if ( isset( $comment ) && !empty( $comment ) && !is_null( $comment ) ) {
				$selected_columns[ $comment ] = $selectedColumn . ' AS ' . $comment;
			}
		}
	}

	return $selected_columns;
}

/**
 * Convert bracketed keys into a nested array structure.
 *
 * This function processes an input array where keys might contain bracketed notation
 * (e.g., "joins[table][column]") and converts them into a proper nested array.
 *
 * @param array $inputArray The array with bracketed keys.
 * @return array The converted nested array.
 */
function convert_bracketed_keys_to_array($inputArray)
{
	$query_details = [];

    foreach ($inputArray as $key => $value) {
        if (preg_match('/^([^\[]+)\[(.+)\]$/', $key, $matches)) {
            $baseKey = $matches[1]; // e.g., "joins" or "columns"
            $nestedKeys = explode('][', trim($matches[2], '[]')); // Extract keys inside brackets

            // Build nested array dynamically
            $current = &$query_details[$baseKey];
            foreach ($nestedKeys as $nestedKey) {
                if (!isset($current[$nestedKey])) {
                    $current[$nestedKey] = [];
                }
                $current = &$current[$nestedKey];
            }
            $current = $value; // Assign value to the final nested key
        } else {
            // If the key has no brackets, assign it directly
            $query_details[$key] = $value;
        }
    }

	return $query_details;
}

/* -------------------------------------------------------------------------------------------------------------------------------------------------- */