<?php

/**
 * Service-Klasse für gespeicherte YForm Filter
 * 
 * @package yform_saved_filters
 */

namespace FriendsOfREDAXO\YFormSavedFilters;

use rex;
use rex_sql;
use rex_user;

class YFormFilterService
{
    /**
     * Prüft, ob der User globale Filter (inkl. globalem Default) verwalten darf.
     */
    public static function canManageGlobalDefaults(?rex_user $user): bool
    {
        if (null === $user) {
            return false;
        }

        return $user->isAdmin() || $user->hasPerm('yform_saved_filters[global_default]');
    }

    /**
     * Speichert einen Filter für einen Benutzer und eine Tabelle
     *
     * @param int $userId
     * @param string $tableName
     * @param string $name
     * @param array<string, mixed> $filterData
     * @param bool $isDefault
     * @param bool $isGlobal
     * @param bool $isGlobalDefault
     * @return bool
     */
    public static function saveFilter(int $userId, string $tableName, string $name, array $filterData, bool $isDefault = false, bool $isGlobal = false, bool $isGlobalDefault = false): bool
    {
        try {
            $sql = rex_sql::factory();

            if ($isGlobalDefault) {
                $isGlobal = true;
            }
            
            // Wenn dieser Filter als Standard gesetzt werden soll, alle anderen Standard-Filter deaktivieren
            if ($isDefault) {
                $sql->setQuery('UPDATE ' . rex::getTable('yform_saved_filters') . ' 
                               SET is_default = 0 
                               WHERE user_id = :user_id AND table_name = :table_name', 
                               ['user_id' => $userId, 'table_name' => $tableName]);
            }

            // Wenn globaler Standard gesetzt wird, alle anderen globalen Standards der Tabelle deaktivieren
            if ($isGlobalDefault) {
                $sql->setQuery('UPDATE ' . rex::getTable('yform_saved_filters') . '
                               SET is_global_default = 0
                               WHERE table_name = :table_name',
                               ['table_name' => $tableName]);
            }
            
            $sql->setTable(rex::getTable('yform_saved_filters'));
            $sql->setValue('user_id', $userId);
            $sql->setValue('table_name', $tableName);
            $sql->setValue('name', $name);
            $sql->setValue('filter_data', json_encode($filterData));
            $sql->setValue('is_default', $isDefault ? 1 : 0);
            $sql->setValue('is_global', $isGlobal ? 1 : 0);
            $sql->setValue('is_global_default', $isGlobalDefault ? 1 : 0);
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
                                             WHERE table_name = :table_name
                                                 AND (user_id = :user_id OR is_global = 1)
                                             ORDER BY is_default DESC, is_global_default DESC, is_global DESC, name ASC', 
                       ['user_id' => $userId, 'table_name' => $tableName]);
        
        $filters = [];
        for ($i = 0; $i < $sql->getRows(); $i++) {
            $filters[] = [
                'id' => $sql->getValue('id'),
                'user_id' => (int) $sql->getValue('user_id'),
                'name' => $sql->getValue('name'),
                'filter_data' => json_decode($sql->getValue('filter_data'), true),
                'is_default' => (bool) $sql->getValue('is_default'),
                'is_global' => (bool) $sql->getValue('is_global'),
                'is_global_default' => (bool) $sql->getValue('is_global_default'),
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
                   WHERE id = :id AND (user_id = :user_id OR is_global = 1)', 
                       ['id' => $filterId, 'user_id' => $userId]);
        
        if ($sql->getRows() === 0) {
            return null;
        }
        
        return [
            'id' => $sql->getValue('id'),
            'user_id' => (int) $sql->getValue('user_id'),
            'name' => $sql->getValue('name'),
            'table_name' => $sql->getValue('table_name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => (bool) $sql->getValue('is_default'),
            'is_global' => (bool) $sql->getValue('is_global'),
            'is_global_default' => (bool) $sql->getValue('is_global_default'),
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

        if ($sql->getRows() > 0) {
            return [
                'id' => $sql->getValue('id'),
                'name' => $sql->getValue('name'),
                'filter_data' => json_decode($sql->getValue('filter_data'), true),
                'is_default' => true,
                'is_global' => (bool) $sql->getValue('is_global'),
                'is_global_default' => (bool) $sql->getValue('is_global_default'),
            ];
        }

        $sql->setQuery(
            'SELECT * FROM ' . rex::getTable('yform_saved_filters') . '
             WHERE table_name = :table_name AND is_global_default = 1
             ORDER BY updatedate DESC
             LIMIT 1',
            ['table_name' => $tableName],
        );

        if ($sql->getRows() === 0) {
            return null;
        }

        return [
            'id' => $sql->getValue('id'),
            'name' => $sql->getValue('name'),
            'filter_data' => json_decode($sql->getValue('filter_data'), true),
            'is_default' => false,
            'is_global' => true,
            'is_global_default' => true,
        ];
    }
    
    /**
     * Löscht einen Filter
     *
     * @param int $filterId
     * @param int $userId
     * @return bool
     */
    public static function deleteFilter(int $filterId, int $userId, bool $canManageGlobalDefaults = false): bool
    {
        try {
            $sql = rex_sql::factory();
            if ($canManageGlobalDefaults) {
                $sql->setQuery('DELETE FROM ' . rex::getTable('yform_saved_filters') . ' 
                               WHERE id = :id AND (user_id = :user_id OR is_global = 1)', 
                               ['id' => $filterId, 'user_id' => $userId]);
            } else {
                $sql->setQuery('DELETE FROM ' . rex::getTable('yform_saved_filters') . ' 
                               WHERE id = :id AND user_id = :user_id', 
                               ['id' => $filterId, 'user_id' => $userId]);
            }
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

    /**
     * Setzt einen Filter als globalen Standard für alle Benutzer.
     */
    public static function setGlobalDefaultFilter(int $filterId, int $userId): bool
    {
        try {
            $sql = rex_sql::factory();

            $filter = self::getFilter($filterId, $userId);
            if (!$filter) {
                return false;
            }

            $sql->setQuery(
                'UPDATE ' . rex::getTable('yform_saved_filters') . '
                 SET is_global_default = 0
                 WHERE table_name = :table_name',
                ['table_name' => $filter['table_name']],
            );

            $sql->setQuery(
                'UPDATE ' . rex::getTable('yform_saved_filters') . '
                 SET is_global_default = 1, updatedate = :updatedate
                 WHERE id = :id',
                ['id' => $filterId, 'updatedate' => date('Y-m-d H:i:s')],
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Setzt oder entfernt die globale Sichtbarkeit eines Filters.
     */
    public static function setGlobalFilter(int $filterId, int $userId, bool $isGlobal): bool
    {
        try {
            $sql = rex_sql::factory();

            $filter = self::getFilter($filterId, $userId);
            if (!$filter) {
                return false;
            }

            $values = [
                'is_global' => $isGlobal ? 1 : 0,
                'updatedate' => date('Y-m-d H:i:s'),
            ];

            // Ein globaler Default darf nicht auf einem nicht-globalen Filter liegen.
            if (!$isGlobal) {
                $values['is_global_default'] = 0;
            }

            $sql->setTable(rex::getTable('yform_saved_filters'));
            $sql->setWhere(['id' => $filterId]);
            foreach ($values as $key => $value) {
                $sql->setValue($key, $value);
            }
            $sql->update();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
