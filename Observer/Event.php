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
 * @copyright $Date: 2011-08-03 19:26:55 -0300 (Wed, 03 Aug 2011) $
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 * @package   HH_Observer
 */

/**
 * Description of Event
 *
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id: Event.php 302 2011-08-03 22:26:55Z farmnik $
 * @package   HH_Observer
 * @copyright $Date: 2011-08-03 19:26:55 -0300 (Wed, 03 Aug 2011) $
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 */
abstract class HH_Observer_Event
{
    /**
     * Event name
     * @var string
     */
    private $_event;

    /**
     * Construct event
     *
     * @param string $event
     */
    public function  __construct($event)
    {
        $this->_event = $event;
    }

    /**
     * Get event name
     * 
     * @return string
     */
    public function getEvent()
    {
        return $this->_event;
    }

}