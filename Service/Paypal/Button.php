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
 * Description of Button
 *
 * @package   
 * @author    Michael Caplan <farmnik@harvesthand.com>
 * @version   $Id$
 * @copyright $Date$
 * @license   http://opensource.org/licenses/gpl-3.0.html  GPL 3
 */
class HH_Service_Paypal_Button
{
    const BUTTON_RECURRING = 'recurring';
    const BUTTON_TOTAL    = 'total';

    protected $_config = array();
    protected $_type;
    
    /**
     * @var HH_Domain_Farm
     */
    protected $_farm;

    public function __construct(HH_Domain_Farm $farm, $config, $type)
    {
        $this->_farm = $farm;
        
        $this->_config = $config;
        $this->_type = $type;
    }

    public function setConfig($params)
    {
        foreach ($params as $key => $value) {
            $this->_config[$key] = $value;
        }
    }

    public function  __toString()
    {
        return $this->toHTML();
    }

    public function toHTML()
    {
        $preferences = $this->_farm->getPreferences();
        
        $this->_config['domain'] = Bootstrap::get('Zend_Config')
            ->resources->paypal->button->url;
        
        $buttonParams = array(
            'item_name'     => $this->_config['item_name'],
            'item_number'   => $this->_config['item_number'],
            'currency_code' => 'CAD',
            'charset'       => 'UTF-8'
        );

        if ($this->_config['amount'] < 12) {
            $business = $preferences->get('microBusiness', 'paypal', false);
            $certId = $preferences->get('microCertId', 'paypal', false);
            
            if ($business && $certId) {
                $buttonParams['business'] = $business;
                $buttonParams['cert_id']  = $certId;
                
                if (Bootstrap::$env == 'production') {
                    $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/micro-pubcert.pem';
                    $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/micro-prvkey.pem';
                    $this->_config['cert'] = Bootstrap::$root . 'data/paypal/cert.pem';
                } else {
                    $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/sandbox-micro-pubcert.pem';
                    $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/paypal-sandbox-micro-prvkey.pem';
                    $this->_config['cert'] = Bootstrap::$root . 'data/paypal/paypal-sandbox-cert.pem';
                }
            } else {
                $buttonParams['business'] = $preferences->get('business', 'paypal', false);
                $buttonParams['cert_id']  = $preferences->get('certId', 'paypal', false);
                
                if (Bootstrap::$env == 'production') {
                    $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/regular-pubcert.pem';
                    $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/regular-prvkey.pem';
                    $this->_config['cert'] = Bootstrap::$root . 'data/paypal/cert.pem';
                } else {
                    $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/sandbox-regular-pubcert.pem';
                    $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/sandbox-regular-prvkey.pem';
                    $this->_config['cert'] = Bootstrap::$root . 'data/paypal/sandbox-cert.pem';
                }
            }
        } else {
            $buttonParams['business'] = $preferences->get('business', 'paypal', false);
            $buttonParams['cert_id']  = $preferences->get('certId', 'paypal', false);

            if (Bootstrap::$env == 'production') {
                $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/regular-pubcert.pem';
                $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/regular-prvkey.pem';
                $this->_config['cert'] = Bootstrap::$root . 'data/paypal/cert.pem';
            } else {
                $this->_config['signcert'] = Bootstrap::$root . 'data/paypal/sandbox-regular-pubcert.pem';
                $this->_config['privkey'] = Bootstrap::$root . 'data/paypal/sandbox-regular-prvkey.pem';
                $this->_config['cert'] = Bootstrap::$root . 'data/paypal/sandbox-cert.pem';
            }
        }
        
        if ($this->_type == self::BUTTON_TOTAL) {
            $buttonParams['bn'] = 'Taproot_BuyNow_WPS_CA';
            $buttonParams['amount'] = $this->_config['amount'];
            $buttonParams['cmd'] = '_xclick';
        } else {
            $buttonParams['bn'] = 'Taproot_Subscribe_WPS_CA';
            $buttonParams['cmd'] = '_xclick-subscriptions';
            $buttonParams['no_note'] = 1;
            $buttonParams['src'] = 1;
            $buttonParams['a3'] = $this->_config['amount'];
            $buttonParams['srt'] = $this->_config['srt'];
            $buttonParams['t3'] = 'W';
            $buttonParams['p3'] = $this->_config['p3'];
        }
        
        $contentBytes = array();
		foreach ($buttonParams as $name => $value) {
			$contentBytes[] = "$name=$value";
		}
        $contentBytes = implode("\n", $contentBytes);

		$encryptedButton = '<form action="' . $this->_config['domain'] . '/cgi-bin/webscr" method="post">';
        $encryptedButton .= '<input type="hidden" name="cmd" value="_s-xclick">';
        $encryptedButton .= '<input type="hidden" name="encrypted" value="' . htmlspecialchars($this->_signAndEncrypt($contentBytes)) . '">';
        $encryptedButton .= '<button name="Order" id="Save" type="submit" class="paypal">Pay Now!</button>';
        $encryptedButton .= '</form>';

        return $encryptedButton;
    }

    protected function _signAndEncrypt($contentBytes)
    {
        $dataStrFile  = realpath(tempnam('/tmp', 'pp_'));
        $fd = fopen($dataStrFile, 'w');
		if(!$fd) {
			throw new Exception('Could not open temporary file ' . $dataStrFile);
		}
		fwrite($fd, $contentBytes);
		fclose($fd);

		$signedDataFile = realpath(tempnam('/tmp', 'pp_'));
        
        $res = !@openssl_pkcs7_sign(
            $dataStrFile,
            $signedDataFile,
            'file://' . $this->_config['signcert'],
            array('file://' . $this->_config['privkey'], ''),
            array(),
            PKCS7_BINARY
        );
        
		if ($res) {
			unlink($dataStrFile);
			unlink($signedDataFile);
			throw new Exception('Could not sign data: ' . openssl_error_string());
		}

		unlink($dataStrFile);

		$signedData = file_get_contents($signedDataFile);
		$signedDataArray = explode("\n\n", $signedData);
		$signedData = $signedDataArray[1];
		$signedData = base64_decode($signedData);

		unlink($signedDataFile);

		$decodedSignedDataFile = realpath(tempnam('/tmp', 'pp_'));
		$fd = fopen($decodedSignedDataFile, 'w');
		if(!$fd) {
			throw new Exception('Could not open temporary file ' . $decodedSignedDataFile);
		}
		fwrite($fd, $signedData);
		fclose($fd);

		$encryptedDataFile = realpath(tempnam('/tmp', 'pp_'));
        
        $res = !@openssl_pkcs7_encrypt(
            $decodedSignedDataFile,
            $encryptedDataFile,
            file_get_contents($this->_config['cert']),
            array(),
            PKCS7_BINARY
        );
        
		if($res) {

			unlink($decodedSignedDataFile);
			unlink($encryptedDataFile);
			throw new Exception('Could not encrypt data: ' . openssl_error_string());
		}

		unlink($decodedSignedDataFile);

		$encryptedData = file_get_contents($encryptedDataFile);
		if(!$encryptedData) {
			throw new Exception('Encryption and signature of data failed.');
		}

		unlink($encryptedDataFile);

		$encryptedDataArray = explode("\n\n", $encryptedData);
		$encryptedData = trim(str_replace("\n", '', $encryptedDataArray[1]));

        return '-----BEGIN PKCS7-----' . $encryptedData . '-----END PKCS7-----';
    }

    public static function verifyIpn($data)
    {
        $data['cmd'] = '_notify-validate';
        $content = http_build_query($data);

        $context = stream_context_create(
            array(
                'http' => array(
                    'method' => 'POST',
                    'content' => $content,
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                        . "Content-Length: " . strlen($content)
                )
            )
        );

        $result = file_get_contents(
            ((!empty($data['test_ipn'])) ? 'http://www.sandbox.paypal.com' :'http://www.paypal.com') . '/cgi-bin/webscr',
            null,
            $context
        );

        if (strcmp($result, 'VERIFIED') == 0) {
           return true;
        } else {
            return false;
        }
    }
}