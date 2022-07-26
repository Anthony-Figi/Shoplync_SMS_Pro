<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/
class WebserviceSpecificManagementOrderInvoicePayments implements WebserviceSpecificManagementInterface {
     
    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /**
     * @var string
     */
    protected $output;
    
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;
 
     /**
     * @param WebserviceOutputBuilder $obj
     *
     * @return WebserviceSpecificManagementInterface
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }
    
    /**
     * Get Object Output
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        
        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }
 
    public function manage()
    {
        $method = $this->wsObject->method;
        
        if(isset($method) && $method == 'POST')
        {
            $post_xml = trim(file_get_contents('php://input'));
            
            $xml = null;
            try {
                $xml = new SimpleXMLElement($post_xml);
            } catch (Exception $error) {
                error_log('error: '.$error);
                return '';
            }
            
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'order_invoice_payment '
            .'VALUES('.$xml->OrderInvoicePayment->id_order_invoice.','.$xml->OrderInvoicePayment->id_order_payment.','.$xml->OrderInvoicePayment->id_order.') ' 
            .'ON DUPLICATE KEY UPDATE id_order = id_order;';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
        if($xml != null)
        {
            $this->output = $xml->OrderInvoicePayment->asXML();
            return $xml->OrderInvoicePayment->asXML();
        }
        else
        {
            return $this->getWsObject()->getOutputEnabled();
        }
    }
    
    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        $contentOutput = array('<?xml version="1.0" encoding="UTF-8"?>'.'<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">', '</prestashop>');
        
        if ($this->output != '') {
            return $this->objOutput->getObjectRender()->overrideContent($this->output);
        }

        return '';
    }
}