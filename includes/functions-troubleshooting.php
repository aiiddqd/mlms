<?php
if ( ! defined('ROWS_PER_SEGMENT') ) define('ROWS_PER_SEGMENT', 100);

function _zwt_ts_stow($query_line, $fp) {
    if(! @fwrite($fp, $query_line,strlen($query_line)))
        die(__('Error writing query:','sitepress') . '  ' . $query_line);
}
 
function _zwt_ts_backquote($a_name) {
    if (!empty($a_name) && $a_name != '*') {
        if (is_array($a_name)) {
            $result = array();
            reset($a_name);
            while(list($key, $val) = each($a_name)) 
                $result[$key] = '`' . $val . '`';
            return $result;
        } else {
            return '`' . $a_name . '`';
        }
    } else {
        return $a_name;
    }
} 
      
function _zwt_ts_backup_table($table, $segment = 'none', $fp) {
        global $wpdb;

        $table_structure = $wpdb->get_results("DESCRIBE $table");        
        if(($segment == 'none') || ($segment == 0)) {
            _zwt_ts_stow("\n\n", $fp);
            _zwt_ts_stow("DROP TABLE IF EXISTS " . _zwt_ts_backquote($table) . ";\n", $fp);
            // Table structure
            _zwt_ts_stow("\n\n", $fp);
            $create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
            _zwt_ts_stow($create_table[0][1] . ' ;', $fp);
            _zwt_ts_stow("\n\n", $fp);
        }
        
        if(($segment == 'none') || ($segment >= 0)) {
            $defs = array();
            $ints = array();
            foreach ($table_structure as $struct) {
                if ( (0 === strpos($struct->Type, 'tinyint')) ||
                    (0 === strpos(strtolower($struct->Type), 'smallint')) ||
                    (0 === strpos(strtolower($struct->Type), 'mediumint')) ||
                    (0 === strpos(strtolower($struct->Type), 'int')) ||
                    (0 === strpos(strtolower($struct->Type), 'bigint')) ) {
                        $defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
                        $ints[strtolower($struct->Field)] = "1";
                }
            }
            
            
            // Batch by $row_inc
            
            if($segment == 'none') {
                $row_start = 0;
                $row_inc = ROWS_PER_SEGMENT;
            } else {
                $row_start = $segment * ROWS_PER_SEGMENT;
                $row_inc = ROWS_PER_SEGMENT;
            }
            
            do {    
                $table_data = $wpdb->get_results("SELECT * FROM $table LIMIT {$row_start}, {$row_inc}", ARRAY_A);

                $entries = 'INSERT INTO ' . _zwt_ts_backquote($table) . ' VALUES (';    
                //    \x08\\x09, not required
                $search = array("\x00", "\x0a", "\x0d", "\x1a");
                $replace = array('\0', '\n', '\r', '\Z');
                if($table_data) {
                    foreach ($table_data as $row) {
                        $values = array();
                        foreach ($row as $key => $value) {
                            if (isset($ints[strtolower($key)]) && $ints[strtolower($key)]) {
                                // make sure there are no blank spots in the insert syntax,
                                // yet try to avoid quotation marks around integers
                                $value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
                                $values[] = ( '' === $value ) ? "''" : $value;
                            } else {
                                $values[] = "'" . str_replace($search, $replace, esc_sql($value)) . "'";
                            }
                        }
                        _zwt_ts_stow(" \n" . $entries . implode(', ', $values) . ');', $fp);
                    }
                    $row_start += $row_inc;
                }
            } while((count($table_data) > 0) and ($segment=='none'));
        }
        
        if(($segment == 'none') || ($segment < 0)) {
            // Create footer/closing comment in SQL-file
            _zwt_ts_stow("\n", $fp);
        }
    } // end backup_table()  

?>
