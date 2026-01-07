<?php

/**
 * YForm Saved Filters AddOn
 * 
 * Ermöglicht das Speichern von Suchfiltern in YForm Manager Tabellen
 * 
 * @author Friends Of REDAXO
 * @package yform_saved_filters
 */

use FriendsOfREDAXO\YFormSavedFilters\YFormFilterService;

if (rex::isBackend() && rex::getUser()) {
    $addon = rex_addon::get('yform_saved_filters');
    
    // CSS und JS einbinden
    if (rex_be_controller::getCurrentPagePart(1) === 'yform') {
        rex_view::addCssFile($addon->getAssetsUrl('yform_saved_filters.css'));
        rex_view::addJsFile($addon->getAssetsUrl('yform_saved_filters.js'));
    }
    
    // Füge Filter-Buttons zur Toolbar hinzu
    rex_extension::register('YFORM_DATA_LIST_LINKS', function(rex_extension_point $ep) {
        $linkSets = $ep->getSubject();
        $table = $ep->getParams()['table'] ?? null;
        
        if (!$table) {
            return $linkSets;
        }
        
        $tableName = $table->getTableName();
        $service = new YFormFilterService();
        $filters = $service->getUserFilters(rex::getUser()->getId(), $tableName);
        
        // Füge gespeicherte Filter als Buttons hinzu
        if (!empty($filters)) {
            $isFirstFilter = true;
            foreach ($filters as $filter) {
                // Filter-Button zum Laden
                $item = [];
                $item['label'] = '<i class="fa fa-filter" aria-hidden="true"></i>&nbsp;&nbsp;' . rex_escape($filter['name']);
                
                // filter_data ist bereits ein Array (wurde in getUserFilters() dekodiert)
                $filterData = $filter['filter_data'];
                
                // URL manuell bauen - mit default_loaded=1 um Default-Filter-Auto-Load zu verhindern
                $url = 'index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName) . '&default_loaded=1';
                
                // Füge Filter-Parameter hinzu
                if (isset($filterData['rex_yform_filter'])) {
                    foreach ($filterData['rex_yform_filter'] as $key => $value) {
                        $valueStr = is_array($value) ? implode(',', $value) : $value;
                        $url .= '&rex_yform_filter[' . urlencode($key) . ']=' . urlencode($valueStr);
                    }
                }
                if (isset($filterData['rex_yform_search'])) {
                    $url .= '&rex_yform_search=' . urlencode($filterData['rex_yform_search']);
                }
                if (isset($filterData['rex_yform_searchvars'])) {
                    foreach ($filterData['rex_yform_searchvars'] as $key => $value) {
                        if (is_array($value)) {
                            // Multi-Select: Mehrere Parameter mit []
                            foreach ($value as $v) {
                                $url .= '&FORM[rex_yform_searchvars-' . urlencode($tableName) . '][' . urlencode($key) . '][]=' . urlencode($v);
                            }
                        } else {
                            $url .= '&FORM[rex_yform_searchvars-' . urlencode($tableName) . '][' . urlencode($key) . ']=' . urlencode($value);
                        }
                    }
                }
                
                // Sortierung hinzufügen
                if (!empty($filterData['sort'])) {
                    $url .= '&sort=' . urlencode($filterData['sort']);
                }
                if (!empty($filterData['sorttype'])) {
                    $url .= '&sorttype=' . urlencode($filterData['sorttype']);
                }
                
                $item['url'] = $url;
                $item['attributes']['class'][] = $filter['is_default'] ? 'btn-primary' : 'btn-default';
                if ($isFirstFilter) {
                    $item['attributes']['class'][] = 'yform-saved-filters-first';
                    $isFirstFilter = false;
                }
                $item['attributes']['title'] = $filter['is_default'] ? rex_i18n::msg('yform_saved_filters_default_filter') : '';
                
                $linkSets['table_links'][] = $item;
            }
            
            // "Filter verwalten" Button
            $item = [];
            $item['label'] = '<i class="fa fa-cog" aria-hidden="true"></i>&nbsp;&nbsp;' . rex_i18n::msg('yform_saved_filters_manage_filters');
            $item['url'] = '#';
            $item['attributes']['class'][] = 'btn-default';
            $item['attributes']['data-toggle'] = 'modal';
            $item['attributes']['data-target'] = '#yform-manage-filters-modal';
            
            $linkSets['table_links'][] = $item;
        }
        
        // "Filter speichern" Button - nur wenn aktive Filter vorhanden sind
        $hasActiveFilters = yform_saved_filters_has_active_filters($tableName);
        
        if ($hasActiveFilters) {
            $item = [];
            $item['label'] = '<i class="fa fa-save" aria-hidden="true"></i>&nbsp;&nbsp;' . rex_i18n::msg('yform_saved_filters_save_filter');
            $item['url'] = '#';
            $item['attributes']['class'][] = 'btn-success';
            $item['attributes']['data-toggle'] = 'modal';
            $item['attributes']['data-target'] = '#yform-save-filter-modal';
            
            $linkSets['table_links'][] = $item;
            
            // "Filter zurücksetzen" Button
            $item = [];
            $item['label'] = '<i class="fa fa-filter-circle-xmark" aria-hidden="true"></i>&nbsp;&nbsp;' . rex_i18n::msg('yform_saved_filters_reset_filters');
            $item['url'] = 'index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName) . '&default_loaded=1';
            $item['attributes']['class'][] = 'btn-default';
            
            $linkSets['table_links'][] = $item;
        }
        
        $ep->setSubject($linkSets);
        return $linkSets;
    });
    
    // Füge Modal und Handler oberhalb der Tabelle ein
    rex_extension::register('YFORM_MANAGER_DATA_PAGE_HEADER', function(rex_extension_point $ep) {
        $content = $ep->getSubject();
        
        // Rendere Fragment mit Modal und Aktionshandling
        $fragment = new rex_fragment();
        $filterHtml = $fragment->parse('yform_saved_filters.php');
        
        $ep->setSubject($content . $filterHtml);
        return $content . $filterHtml;
    });
}

/**
 * Prüft ob aktive Filter vorhanden sind
 */
function yform_saved_filters_has_active_filters(string $tableName): bool {
    // Prüfe Standard YForm Filter-Parameter
    $hasFilters = false;
    
    foreach (['rex_yform_filter', 'rex_yform_search'] as $param) {
        $value = rex_request($param, 'array', []);
        if (!empty(array_filter($value))) {
            $hasFilters = true;
            break;
        }
    }
    
    // Prüfe YForm Searchvars Format: FORM[rex_yform_searchvars-tablename]
    if (!$hasFilters) {
        $formData = rex_request('FORM', 'array', []);
        $searchKey = 'rex_yform_searchvars-' . $tableName;
        
        if (isset($formData[$searchKey]) && !empty(array_filter($formData[$searchKey]))) {
            $hasFilters = true;
        }
    }
    
    return $hasFilters;
}
