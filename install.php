<?php

/**
 * Installation Script
 * Erstellt die Datenbank-Tabelle für gespeicherte Filter
 */

rex_sql_table::get(rex::getTable('yform_saved_filters'))
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('user_id', 'int(11) unsigned'))
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('filter_data', 'text'))
    ->ensureColumn(new rex_sql_column('is_default', 'tinyint(1)', false, '0'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->setPrimaryKey('id')
    ->ensureIndex(new rex_sql_index('user_table', ['user_id', 'table_name']))
    ->ensure();
