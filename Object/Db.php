<?php
/**
 * HarvestHand
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License Version 3
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to farmnik@harvesthand.com so we can send you a copy immediately.
 *
 * @copyright $Date: 2011-09-22 19:22:20 -0300 (Thu, 22 Sep 2011) $
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 * @package   HH_Object
 */

/**
 * Description of Base
 *
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id: Db.php 323 2011-09-22 22:22:20Z farmnik $
 * @copyright $Date: 2011-09-22 19:22:20 -0300 (Thu, 22 Sep 2011) $
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 * @package   HH_Object
 */
abstract class HH_Object_Db extends HH_Object
{
    /**
     * get db handle
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getZendDb()
    {
        if (isset($this->_config['Zend_Db'])) {
            return $this->_config['Zend_Db'];
        }

        return Bootstrap::get('Zend_Db');
    }

    /**
     * get db handle
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected static function _getStaticZendDb()
    {
        if (isset(self::$_staticConfig['Zend_Db'])) {
            return self::$_staticConfig['Zend_Db'];
        }

        return Bootstrap::get('Zend_Db');
    }

    /**
     * Get data (lazy loader)
     */
    protected function _get()
    {
        if (empty($this->_id)) {
            $this->_setData();
            return;
        }

        $cache = $this->_getZendCache();
        if (($data = $cache->load((string) $this)) !== false) {
            $this->_setData($data);
            return;
        }

        $sql = 'SELECT
                  *
                FROM
                    ' . $this->_getDatabase() . '
                WHERE
                    id = ?';

        $this->_setData(
            $this->_getZendDb()->fetchRow($sql, $this->_id)
        );

        $cache->save($this->_data, (string) $this);
    }

    /**
     * Insert data into object
     *
     * @param array $data
     * @return boolean
     * @throws HH_Object_Exception_Id If primary key needs to be defined
     * @throws HH_Object_Exception_NoData If no data to insert
     */
    public function insert($data)
    {
        $db = $this->_getZendDb();

        $db->insert(
            $this->_getDatabase(),
            $this->_prepareData($data)
        );
        $data['id'] = $db->lastInsertId();

        $this->_setData($data);

        $this->_getZendCache()->save($this->_data, (string) $this);
        
        $this->_notify(new HH_Object_Event_Insert());
    }

    /**
     * Update data in current object
     *
     * @param array|null $data
     * @return boolean
     * @throws HH_Object_Exception_Id if object ID is not set
     */
    public function update($data = null)
    {
        if (!empty($this->_id)) {

            if (!$this->_isLoaded) {
                $this->_get();
            }

            $this->_getZendDb()->update(
                $this->_getDatabase(),
                $this->_prepareData($data, false),
                array('id = ?' => $this->_id)
            );

            $this->_setData($data, false);

            $this->_getZendCache()->save($this->_data, (string) $this);
            
            $this->_notify(new HH_Object_Event_Update());
        }
    }

    /**
     * Delete current object
     *
     * @throws HH_Object_Exception_Id if object ID is not set
     * @return boolean
     */
    public function delete()
    {
        if (!empty($this->_id)) {

            $sql = 'DELETE FROM
                        ' . $this->_getDatabase() . '
                    WHERE
                        id = ?';

            $this->_getZendDb()->query($sql, $this->_id);

            $this->_getZendCache()
                ->remove((string) $this);
            
            $this->_notify(new HH_Object_Event_Delete());
        }

        $this->_reset();
    }

    /**
     *
     * @param type $tableSpace
     * @params array $options
     * @return HH_Object_Db[]
     */
    public static function fetchAll($options = array())
    {
        $database = self::_getStaticZendDb();
        
        $sql = 'SELECT 
                id
            FROM
                ' . self::_getStaticDatabase();
        
        $bind = array();
        
        if (isset($options['where'])) {
            $sql .= self::_sqlBuildWhere($options['where'], $bind);
        }
        
        if (isset($options['order'])) {            
            $sql .= self::_sqlBuildOrder($options['order']);
        }
        
        if (isset($options['limit'])) {
            $sql .= self::_sqlBuildLimit($options['limit']);
        }
        
        $rows = $database->fetchCol($sql, $bind);
        
        $return = array();
        $class = get_called_class();
        
        foreach ($rows as $row) {
            $return[] = new $class($row, $tableSpace);
        }
        
        return $return;
    }
    
    protected static function _sqlBuildWhere($data, &$bind)
    {
        if (!empty($data)) {
            
            if (is_array($data)) {
            
                $where = array();
                $database = self::_getStaticZendDb();

                foreach ($data as $key => $value) {
                    $where[] = $database->quoteIdentifier($key) . ' = ?';
                    $bind[] = $value;
                }

                if (!empty($where)) {
                    return ' WHERE ' . implode(' AND ', $where);
                }
            } else {
                return ' WHERE ' . $data;
            }
        }
    }
    
    protected static function _sqlBuildOrder($data)
    {
        if (!empty($data)) {            
            $orderBy = array();
            $database = self::_getStaticZendDb();
            
            foreach ($data as $colMeta) {
                $orderBy[] = $database->quoteIdentifier($colMeta['column']) 
                    . ' ' . $colMeta['dir'];
            }
            
            return ' ORDER BY ' . implode(', ', $orderBy);
        }
    }
    
    protected static function _sqlBuildLimit($data)
    {
        return ' LIMIT ' . $data['offset'] 
                . ', ' . $data['rows'];
    }
    
    protected function _getDatabase()
    {
        return 'farmnik_hh' . '.' . self::_buildTableName(get_class($this));
    }

    protected static function _getStaticDatabase()
    {
        return 'farmnik_hh' . '.' 
            . self::_buildTableName(get_called_class());
    }
    
    protected static function _buildTableName($class)
    {
        $pieces = explode('_', $class);
        $table = '';

        $first = true;
        
        foreach ($pieces as $piece) {
            
            if (in_array($piece, array('HH', 'Domain', 'HHF'))) {
                continue;
            }
            
            if (substr($piece, -1, 1) == 'y') {
                $piece = substr($piece, 0, -1) . 'ies';
            } else {
                $piece .= 's';
            }
            
            if ($first) {
                $table .= strtolower($piece);
                $first = false;
            } else {
                $table .= $piece;
            }
        }
        
        return $table;
    }
}