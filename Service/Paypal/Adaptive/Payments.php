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
 * Description of AdaptivePayments
 *
 * @package   
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id$
 * @copyright $Date$
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 */
class HH_Service_Paypal_Adaptive_Payments extends HH_Service_Paypal_Adaptive_Base
{
    public function pay($amount)
    {
        $client = $this->_getPaypalClient();
        $client->setUri(
            Bootstrap::get('Zend_Config')
                ->resources->paypal->adaptive->account->url . 'CreateAccount'
        );
        
        $payload = array(
            'actionType' => 'PAY',
            'receiverList' => array(
                'receiver' => array(
                    array(
                        'email' => ''
                    )
                )
            )
        );
    }
}