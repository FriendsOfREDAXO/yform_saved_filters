<?php

/**
 * Uninstall Script
 */

rex_sql_table::get(rex::getTable('yform_saved_filters'))->drop();
