<?php

/**
 * Fragment für gespeicherte YForm Filter
 * Wird über Extension Point in YForm Manager eingebunden
 * Zuständig für: Modal zum Speichern, Aktionen (speichern/löschen/default/laden)
 */

use FriendsOfREDAXO\YFormSavedFilters\YFormFilterService;

$addon = rex_addon::get('yform_saved_filters');
$user = rex::getUser();
$userId = $user->getId();
$tableName = rex_request('table_name', 'string', '');

if (!$tableName) {
    return;
}

// Service-Instanz
$service = new YFormFilterService();

// Aktuelle Filter aus URL auslesen
$rex_yform_filter = rex_request('rex_yform_filter', 'array', []);
$rex_yform_search = rex_request('rex_yform_search', 'string', '');
$list_sort = rex_request('sort', 'string', '');
$list_sorttype = rex_request('sorttype', 'string', '');

// YForm Manager Search Format: FORM[rex_yform_searchvars-tablename]
$formData = rex_request('FORM', 'array', []);
$searchKey = 'rex_yform_searchvars-' . $tableName;
$yformSearchVars = [];
if (isset($formData[$searchKey]) && is_array($formData[$searchKey])) {
    $yformSearchVars = array_filter($formData[$searchKey], function($value, $key) {
        return $value !== '' && $value !== null && $key !== 'send';
    }, ARRAY_FILTER_USE_BOTH);
}

// Bereinige Filter-Array
$rex_yform_filter = array_filter($rex_yform_filter, function($value) {
    return $value !== null && $value !== '' && $value !== [];
});

// Prüfe ob Filter aktiv sind
$hasActiveFilter = !empty($rex_yform_filter) || !empty($rex_yform_search) || !empty($yformSearchVars);

// YForm-Tabelle laden für Feld-Labels
$yformTable = null;
$yformFields = [];
try {
    $yformTable = rex_yform_manager_table::get($tableName);
    if ($yformTable) {
        foreach ($yformTable->getFields() as $field) {
            $yformFields[$field->getName()] = $field->getLabel() ?: $field->getName();
        }
    }
} catch (Exception $e) {
    // Tabelle nicht gefunden, nutze Feldnamen
}

// ======================
// AKTIONEN BEHANDELN
// ======================

// Filter speichern
if (rex_post('yform_save_filter', 'string') === '1' && $hasActiveFilter) {
    $filterName = rex_post('filter_name', 'string', '');
    $setAsDefault = rex_post('set_as_default', 'int', 0) === 1;
    
    if (!empty($filterName)) {
        $filterData = [
            'rex_yform_filter' => $rex_yform_filter,
            'rex_yform_search' => $rex_yform_search,
            'rex_yform_searchvars' => $yformSearchVars,
            'sort' => $list_sort,
            'sorttype' => $list_sorttype,
        ];
        
        $service->saveFilter($userId, $tableName, $filterName, $filterData, $setAsDefault);
        
        // Redirect zu aktueller Seite mit Filtern - URL manuell bauen
        $url = 'index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName);
        if (!empty($rex_yform_filter)) {
            foreach ($rex_yform_filter as $key => $value) {
                $valueStr = is_array($value) ? implode(',', $value) : $value;
                $url .= '&rex_yform_filter[' . urlencode($key) . ']=' . urlencode($valueStr);
            }
        }
        if (!empty($rex_yform_search)) {
            $url .= '&rex_yform_search=' . urlencode($rex_yform_search);
        }
        if (!empty($yformSearchVars)) {
            foreach ($yformSearchVars as $key => $value) {
                if (is_array($value)) {
                    // Multi-Select: Mehrere Parameter mit []
                    foreach ($value as $v) {
                        $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . '][]=' . urlencode($v);
                    }
                } else {
                    $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . ']=' . urlencode($value);
                }
            }
        }
        
        rex_response::sendRedirect($url);
    }
}

// Filter löschen
if ($deleteId = rex_request('delete_yform_filter', 'int', 0)) {
    $service->deleteFilter($deleteId, $userId);
    rex_response::sendRedirect('index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName));
}

// Als Standard setzen
if ($defaultId = rex_request('set_default_yform_filter', 'int', 0)) {
    $service->setDefaultFilter($defaultId, $userId);
    rex_response::sendRedirect('index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName));
}

// Filter laden
if ($loadId = rex_request('load_yform_filter', 'int', 0)) {
    $loadedFilter = $service->getFilter($loadId, $userId);
    if ($loadedFilter) {
        $filterData = $loadedFilter['filter_data'];
        $url = 'index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName);
        
        if (!empty($filterData['rex_yform_filter'])) {
            foreach (array_filter($filterData['rex_yform_filter']) as $key => $value) {
                if ($value !== null && $value !== '') {
                    $valueStr = is_array($value) ? implode(',', $value) : $value;
                    $url .= '&rex_yform_filter[' . urlencode($key) . ']=' . urlencode($valueStr);
                }
            }
        }
        if (!empty($filterData['rex_yform_search'])) {
            $url .= '&rex_yform_search=' . urlencode($filterData['rex_yform_search']);
        }
        if (!empty($filterData['rex_yform_searchvars'])) {
            foreach ($filterData['rex_yform_searchvars'] as $key => $value) {
                if (is_array($value)) {
                    // Multi-Select: Mehrere Parameter mit []
                    foreach ($value as $v) {
                        $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . '][]=' . urlencode($v);
                    }
                } else {
                    $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . ']=' . urlencode($value);
                }
            }
        }
        if (!empty($filterData['sort'])) {
            $url .= '&sort=' . urlencode($filterData['sort']);
        }
        if (!empty($filterData['sorttype'])) {
            $url .= '&sorttype=' . urlencode($filterData['sorttype']);
        }
        
        rex_response::sendRedirect($url);
    }
}

// Standard-Filter beim ersten Laden anwenden (mit Schutz gegen Redirect-Loop)
// NICHT anwenden wenn:
// - Bereits aktive Filter vorhanden sind
// - Ein Filter explizit geladen wird (load_yform_filter Parameter)
// - Der Default-Filter bereits geladen wurde (default_loaded Parameter)
// - Filter zurückgesetzt werden sollen
$skipDefaultFilter = $hasActiveFilter 
    || rex_request('load_yform_filter', 'int', 0) 
    || rex_request('default_loaded', 'bool', false)
    || rex_request('reset_filters', 'bool', false);

if (!$skipDefaultFilter) {
    $defaultFilter = $service->getDefaultFilter($userId, $tableName);
    if ($defaultFilter) {
        $filterData = $defaultFilter['filter_data'];
        $url = 'index.php?page=yform/manager/data_edit&table_name=' . urlencode($tableName) . '&default_loaded=1';
        
        $hasFilters = false;
        if (!empty($filterData['rex_yform_filter'])) {
            foreach (array_filter($filterData['rex_yform_filter']) as $key => $value) {
                $valueStr = is_array($value) ? implode(',', $value) : $value;
                $url .= '&rex_yform_filter[' . urlencode($key) . ']=' . urlencode($valueStr);
                $hasFilters = true;
            }
        }
        if (!empty($filterData['rex_yform_search'])) {
            $url .= '&rex_yform_search=' . urlencode($filterData['rex_yform_search']);
            $hasFilters = true;
        }
        if (!empty($filterData['rex_yform_searchvars'])) {
            foreach ($filterData['rex_yform_searchvars'] as $key => $value) {
                if (is_array($value)) {
                    // Multi-Select: Mehrere Parameter mit []
                    foreach ($value as $v) {
                        $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . '][]=' . urlencode($v);
                    }
                } else {
                    $url .= '&FORM[' . urlencode($searchKey) . '][' . urlencode($key) . ']=' . urlencode($value);
                }
                $hasFilters = true;
            }
        }
        
        // Nur redirecten wenn tatsächlich Filter vorhanden sind
        if ($hasFilters) {
            rex_response::sendRedirect($url);
        }
    }
}

// Aktuelle URL-Parameter für Modal
$currentParams = [];
foreach ($_GET as $param => $value) {
    if (!in_array($param, ['yform_save_filter', 'filter_name', 'set_as_default', 'delete_yform_filter', 'set_default_yform_filter', 'load_yform_filter'])) {
        $currentParams[$param] = $value;
    }
}

/**
 * Rekursive Funktion zum Generieren von Hidden Fields aus verschachtelten Arrays
 * @param string $name
 * @param mixed $value
 * @return string
 */
function renderHiddenFields($name, $value) {
    if (is_array($value)) {
        $output = '';
        foreach ($value as $key => $val) {
            $output .= renderHiddenFields($name . '[' . rex_escape($key) . ']', $val);
        }
        return $output;
    }
    return '<input type="hidden" name="' . $name . '" value="' . rex_escape($value) . '">';
}

// ======================
// MODAL ZUM SPEICHERN
// ======================
?>

<?php if ($hasActiveFilter): ?>
<!-- Modal zum Speichern von Filtern -->
<div class="modal fade" id="yform-save-filter-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= rex_url::currentBackendPage() ?>" method="post">
                <?php foreach ($currentParams as $param => $value): ?>
                    <?= renderHiddenFields(rex_escape($param), $value) ?>
                <?php endforeach; ?>
                <input type="hidden" name="yform_save_filter" value="1">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="rex-icon fa-save"></i> <?= $addon->i18n('save_filter') ?></h4>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="filter_name"><?= $addon->i18n('filter_name') ?> *</label>
                        <input type="text" class="form-control" id="filter_name" name="filter_name" required maxlength="191" autofocus>
                    </div>
                    
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong><?= $addon->i18n('current_filter_settings') ?>:</strong>
                        </div>
                        <div class="panel-body" style="padding: 10px;">
                            <table class="table table-condensed" style="margin-bottom: 0;">
                                <?php if (!empty($rex_yform_search)): ?>
                                <tr>
                                    <td style="width: 40%;"><strong><?= $addon->i18n('search_term') ?>:</strong></td>
                                    <td><?= rex_escape($rex_yform_search) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($rex_yform_filter)): ?>
                                    <?php foreach ($rex_yform_filter as $fieldName => $fieldValue): ?>
                                        <?php if ($fieldValue !== '' && $fieldValue !== null): ?>
                                        <?php 
                                            // Feld-Label ermitteln
                                            $fieldLabel = $yformFields[$fieldName] ?? $fieldName;
                                        ?>
                                        <tr>
                                            <td><strong><?= rex_escape($fieldLabel) ?>:</strong></td>
                                            <td><?= is_array($fieldValue) ? implode(', ', array_map('rex_escape', $fieldValue)) : rex_escape($fieldValue) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($yformSearchVars)): ?>
                                    <?php foreach ($yformSearchVars as $fieldKey => $fieldValue): ?>
                                        <?php if ($fieldValue !== '' && $fieldValue !== null && $fieldKey !== 'send' && $fieldKey !== 'search'): ?>
                                        <?php 
                                            // Feld-Label ermitteln
                                            $fieldLabel = $yformFields[$fieldKey] ?? $fieldKey;
                                            
                                            // Array-Werte (z.B. bei Choice-Feldern) als kommaseparierte Liste ausgeben
                                            $displayValue = is_array($fieldValue) ? implode(', ', $fieldValue) : $fieldValue;
                                        ?>
                                        <tr>
                                            <td><strong><?= rex_escape($fieldLabel) ?>:</strong></td>
                                            <td><?= rex_escape($displayValue) ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <?php if (empty($rex_yform_search) && empty($rex_yform_filter) && empty($yformSearchVars)): ?>
                                <tr>
                                    <td colspan="2" class="text-muted"><?= $addon->i18n('no_filters_active') ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="set_as_default" value="1">
                            <?= $addon->i18n('set_as_default_filter') ?>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?= $addon->i18n('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="rex-icon fa-save"></i> <?= $addon->i18n('save') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal zur Filter-Verwaltung -->
<?php 
$savedFilters = $service->getUserFilters(rex::getUser()->getId(), $tableName);
if (!empty($savedFilters)): 
?>
<div class="modal fade" id="yform-manage-filters-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="rex-icon fa-cog"></i> <?= $addon->i18n('manage_filters') ?></h4>
            </div>
            
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><?= $addon->i18n('name') ?></th>
                            <th style="width: 150px;"><?= $addon->i18n('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($savedFilters as $filter): ?>
                        <tr>
                            <td>
                                <strong><?= rex_escape($filter['name']) ?></strong>
                                <?php if ($filter['is_default']): ?>
                                    <span class="label label-primary"><?= $addon->i18n('default_filter') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$filter['is_default']): ?>
                                <a href="index.php?page=yform/manager/data_edit&table_name=<?= urlencode($tableName) ?>&set_default_yform_filter=<?= $filter['id'] ?>" 
                                   class="btn btn-default btn-xs" 
                                   title="<?= $addon->i18n('set_as_default') ?>">
                                    <i class="fa fa-star"></i>
                                </a>
                                <?php endif; ?>
                                
                                <a href="index.php?page=yform/manager/data_edit&table_name=<?= urlencode($tableName) ?>&delete_yform_filter=<?= $filter['id'] ?>" 
                                   class="btn btn-danger btn-xs" 
                                   title="<?= $addon->i18n('delete') ?>"
                                   onclick="return confirm('<?= $addon->i18n('delete_filter_confirm') ?>');">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?= $addon->i18n('close') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
