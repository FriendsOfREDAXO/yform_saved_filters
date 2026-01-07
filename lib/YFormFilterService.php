<?php

/**
 * Service-Klasse für gespeicherte YForm Filter
 * 
 * @package yform_saved_filters
 */

namespace FriendsOfREDAXO\YFormSavedFilters;

use rex;
use rex_sql;

class YFormFilterService
{
    /**
     * Speichert einen Filter für einen Benutzer und eine Tabelle
     *
     * @param int $userId
     * @param string $tableName
     * @param string $name
     * @param array<string, mixed> $filterData
     * @param bool $isDefault
     * @return bool
     */
    public static function saveFilter(int $userId, string $tableName, string $name, array $filterData, bool $isDefault = false): bool
    {
        try {
            $sql = rex_sql::factory();
            
            // Wenn dieser Filter als Standard gesetzt werden soll, alle anderen Standard-Filter deaktivieren
            if ($isDefault) {
                $sql->setQuery('UPDATE ' . rex::getTable('yform_saved_filters') . ' 
                               SET is_default = 0 
                               WHERE user_id = :user_id AND table_name = :table_name', 
                               ['user_id' => $userId, 'table_name' => $tableName]);
            }
            
            $sql->setTable(rex::getTable('yform_saved_filters'));
            $sql->setValue('user_id', $userId);
            $sql->setValue('table_name', $tableName);
            $sql->setValue('name', $name);
            $sql->setValue('filter_data', json_encode($filterData));
            $sql->setValue('is_default', $isDefault ? 1 : 0);
            $sql->setValue('createdate', date('Y-m-d H:i:s'));
            $sql->setValue('updatedate', date('Y-m-d H:i:s'));
            $sql->insert();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Lädt alle Filter für einen Benutzer und eine Tabelle
     *
     * @param int $userId
     * @param string $tableName
     * @return array<int, array<string, mixed>>
     */
    public static function getUserFilters(int $userId, string $tableName): array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('yform_saved_filters') . ' 
                       WHERE user_id = :user_id AND table_name = :table_name
                       ORDER BY is_default DESC, name ASC', 
                       ['user_id' => $userId, 'table_name' => $tableName]);
        
        $filters = [];
        for ($i = 0; $i < $sql->getRows(); $i++) {
            $filters[] = [
                'id' => $sql->getValue('id'),
                'name' => $sql->getValue('name'),
                'filter_data' => json_decode($sql->getValue('filter_data'), true),
                'is_default' => (bool) $sql->getValue('is_default'),
                'createdate' => $sql->getValue('createdate'),
                'updatedate' => $sql->getValue('updatedate'),
            ];
            $sql->next();
        }
        
        return $filters;
    }
    
    /**
     * Lädt einen einzelnen Filter
     *
     * @param int $filterId
     * @param int $userId
     * @return array<string, mixed>|null
     */
    public static function getFilter(int $filterId, int $userId): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('yform_saved_filters') . ' 
                       WHERE id = :id AND user_id = :user_id', 
                       ['id' => $filterId, 'user_id' => $userId]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return [
            'id' => $sql->getValue('id'),
            'name' => $sql->getValue('name'),
            'table_name' => $sql->getValue('table_name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => (bool) $sql->getValue('is_default'),
        ];
    }
    
    /**
     * Lädt den Standard-Filter für einen Benutzer und eine Tabelle
     *
     * @param int $userId
     * @param string $tableName
     * @return array<string, mixed>|null
     */
    public static function getDefaultFilter(int $userId, string $tableName): ?array
    {
        $sql = rex_sql::factory();
        $sql->setQuery('SELECT * FROM ' . rex::getTable('yform_saved_filters') . ' 
                       WHERE user_id = :user_id AND table_name = :table_name AND is_default = 1 
                       LIMIT 1', 
                       ['user_id' => $userId, 'table_name' => $tableName]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return [
            'id' => $sql->getValue('id'),
            'name' => $sql->getValue('name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => true,
        ];
    }
    
    /**
     * Löscht einen Filter
     *
     * @param int $filterId
     * @param int $userId
     * @return bool
     */
    public static function deleteFilter(int $filterId, int $userId): bool
    {
        try {
            $sql = rex_sql::factory();
            $sql->setQuery('DELETE FROM ' . rex::getTable('yform_saved_filters') . ' 
                           WHERE id = :id AND user_id = :user_id', 
                           ['id' => $filterId, 'user_id' => $userId]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Setzt einen Filter als Standard
     *
     * @param int $filterId
     * @param int $userId
     * @return bool
     */
    public static function setDefaultFilter(int $filterId, int $userId): bool
    {
        try {
            $sql = rex_sql::factory();
            
            // Hole die Tabelle des Filters
            $filter = self::getFilter($filterId, $userId);
            if (!$filter) {
                return false;
            }
            
            // Alle anderen Standard-Filter für diese Tabelle deaktivieren
            $sql->setQuery('UPDATE ' . rex::getTable('yform_saved_filters') . ' 
                           SET is_default = 0 
                           WHERE user_id = :user_id AND table_name = :table_name', 
                           ['user_id' => $userId, 'table_name' => $filter['table_name']]);
            
            // Diesen Filter als Standard setzen
            $sql->setQuery('UPDATE ' . rex::getTable('yform_saved_filters') . ' 
                           SET is_default = 1, updatedate = :updatedate 
                           WHERE id = :id', 
                           ['id' => $filterId, 'updatedate' => date('Y-m-d H:i:s')]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
