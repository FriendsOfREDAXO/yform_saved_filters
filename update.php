<?php

/**
 * YForm Saved Filters - Update Script
 * 
 * Wird bei jedem Update des AddOns ausgeführt
 */

// Prüfe ob Tabelle existiert, falls nicht: erstelle sie
$sql = rex_sql::factory();
$sql->setQuery('SHOW TABLES LIKE "' . rex::getTable('yform_saved_filters') . '"');

if ($sql->getRows() === 0) {
    // Tabelle existiert nicht, installiere sie
    rex_sql_table::get(
        rex::getTable('yform_saved_filters'))
        ->ensurePrimaryIdColumn()
        ->ensureColumn(new rex_sql_column('user_id', 'int(10) unsigned'))
        ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
        ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
        ->ensureColumn(new rex_sql_column('filter_data', 'text'))
        ->ensureColumn(new rex_sql_column('is_default', 'tinyint(1)', false, '0'))
        ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
        ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
        ->ensureIndex(new rex_sql_index('user_id', ['user_id'], rex_sql_index::INDEX))
        ->ensureIndex(new rex_sql_index('table_name', ['table_name'], rex_sql_index::INDEX))
        ->ensureIndex(new rex_sql_index('user_table', ['user_id', 'table_name'], rex_sql_index::INDEX))
        ->ensure();
}
