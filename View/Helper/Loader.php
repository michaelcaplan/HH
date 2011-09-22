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
 * @copyright $Date$
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 * @package
 */

/**
 * Description of Loader
 *
 * @package   
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id$
 * @copyright $Date$
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 */
class HH_View_Helper_Loader extends Zend_View_Helper_Abstract
{
    const JS = 'JS';
    const CSS = 'CSS';
    
    /**
     * Current module, if any
     * @var string
     */
    protected $_module = null;
    
    /**
     * Current farm, if any
     * @var string
     */
    protected $_farm = null;
    
    /**
     * Browser cache hash, change to invalidate 
     * old browser cache
     * 
     * @var string
     */
    protected $_cacheHash = 'E3519D5E';
    
    /**
     * use compressed objects
     * @var boolean
     */
    protected $_compress = true;
    
    /**
     * loader objects
     * @var array
     */
    protected static $_objects = array(
        self::CSS => array(),
        self::JS => array()
    );
    
    /**
     * Loader
     * 
     * @param type $module
     * @return HH_View_Helper_Loader
     */
    public function Loader($module = null, $farm = null)
    {
        if ($module !== null) {
            if ($module === true) {
                $this->_module = Zend_Controller_Front::getInstance()
                    ->getRequest()->getModuleName();
            } else {
                $this->_module = $module;
            }
        }
        
        if ($farm !== null) {
            $this->_farm = $farm;
        }
        
        return $this;
    }
    
    /**
     * Use compressed objects?
     * 
     * @param boolean $compress 
     * @return HH_View_Helper_Loader
     */
    public function setCompress($compress = true)
    {
        $this->_compress = $compress;
        
        return $this;
    }
    
    /**
     * Hash to add to loader path to manage browser cache
     * 
     * @param string $cacheHash
     * @return HH_View_Helper_Loader
     */
    public function setCacheHash($cacheHash)
    {
        $this->_cacheHash = $cacheHash;
        
        return $this;
    }
    
    /**
     * Append data to loader
     * 
     * @param string $name Type of data to append
     * @param mixed $value Value to add
     * @return HH_View_Helper_Loader
     */
    public function __set($name, $value)
    {
        return $this->append($value, $name);
    }
    
    /**
     * Get loader HTML
     * @param string $name Type of data to append
     * @return string 
     */
    public function __get($name)
    {
        return $this->toString($name);
    }
    
    /**
     * Append data to loader
     * 
     * @param mixed $value Value to add
     * @param string $type Type of data to append
     * @return HH_View_Helper_Loader
     */
    public function append($value, $type = self::JS)
    {
        self::$_objects[$type][] = $this->_buildData($value);
        
        return $this;
    }
    
    protected function _buildData($value)
    {
        if (is_array($value)) {
            
            $return = array();
            
            foreach ($value as $v) {
                $return[] = $this->_buildData($v);
            }
            
            return $return;
        }
        
        return array(
            'path' => $value,
            'module' => $this->_module,
            'farm' => $this->_farm
        );
    }
    
    /**
     * Prepend data to loader
     * 
     * @param mixed $value Value to add
     * @param string $type Type of data to append
     * @return HH_View_Helper_Loader
     */
    public function prepend($value, $type = self::JS) 
    {
        array_unshift(self::$_objects[$type], $this->_buildData($value));
        
        return $this;
    }
    
    public function toString($type)
    {
        switch ($type) {
            case self::JS :
                return $this->jsToString();
                break;
            case self::CSS :
                return $this->cssToString();
                break;
        }
    }
    
    public function jsToString()
    {
        $return = '';
        $parts = array();
        
        foreach (self::$_objects[self::JS] as $item) {
            if (is_array($item) && !array_key_exists('path', $item)) {
                
                if (!empty($parts)) {
                    $return .= $this->_jsTemplate($parts);
                    
                    $parts = array();
                }
                
                $groupParts = array();
                
                foreach ($item as $i) {
                    $groupParts[] = $this->_buildUrlPart($i);
                }
                
                $return .= $this->_jsTemplate($groupParts);

            } else {
                $parts[] = $this->_buildUrlPart($item);
            }
        }
        
        if (!empty($parts)) {
            $return .= $this->_jsTemplate($parts);
        }
        
        return $return;
    }
    
    protected function _jsTemplate($parts)
    {
        $prefix = (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://';
        
        $prefix .= 'static.' . Bootstrap::$rootDomain . '/loader/t/j/c/';
        
        if ($this->_compress) {
            $prefix .= '1';
        } else {
            $prefix .= '0';
        }
        
        $prefix .= '/f/';
        
        return sprintf(
            '<script type="text/javascript" src="%s%s/h/%s.js"></script>' . "\n",
            $this->view->escape($prefix),
            $this->view->escape(implode('~', $parts)),
            $this->_cacheHash
        );
    }
    
    protected function _buildUrlPart($item)
    {
        $path = '';
        
        if (!empty($item['farm'])) {
            $path .= $item['farm'] . '_';
        }
        
        if (!empty($item['module'])) {
            $path .= $item['module'] . '_';
        }
        
        return $path . $item['path'];
    }
}