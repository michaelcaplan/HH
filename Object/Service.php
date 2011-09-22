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
 * @package   HH_Object
 */

/**
 * Object service
 *
 * @package   HH_Object
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id$
 * @copyright $Date$
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 */
abstract class HH_Object_Service
{

    /**
     * @var HH_Object
     */
    protected $_object;


    /**
     * Service constructor
     */
    public function __construct(HH_Object $object)
    {
        $this->_object = $object;
    }

    public function save()
    {
        throw new Exception('Not implemented');
    }
}