<?php
namespace Payum\Payex\Api;

use Payum\Exception\InvalidArgumentException;

class PxOrder
{
    /**
     * @var SoapClientFactory
     */
    protected $clientFactory;
    
    /**
     * @var array
     */
    protected $options;

    /**
     * @param SoapClientFactory $clientFactory
     * @param array $options
     *
     * @throws \Payum\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(SoapClientFactory $clientFactory, array $options) 
    {
        $this->clientFactory = $clientFactory;
        $this->options = $options;
        
        if (true == empty($this->options['accountNumber'])) {
            throw new InvalidArgumentException('The accountNumber option must be set.');
        }
        
        if (true == empty($this->options['encryptionKey'])) {
            throw new InvalidArgumentException('The encryptionKey option must be set.');
        }

        if (false == is_bool($this->options['sandbox'])) {
            throw new InvalidArgumentException('The boolean sandbox option must be set.');
        }
    }

    /**
     * @link http://www.payexpim.com/technical-reference/pxorder/initialize8/
     * 
     * @var array $parameters
     * 
     * @return \stdClass
     */
    public function Initialize8(array $parameters)
    {
        $parameters['accountNumber'] = $this->options['accountNumber'];
        
        $parameters['hash'] = $this->calculateHash($parameters, array(
            'accountNumber',
            'purchaseOperation',
            'price',
            'priceArgList',
            'currency',
            'vat',
            'orderID',
            'productNumber',
            'description',
            'clientIPAddress',
            'clientIdentifier',
            'additionalValues',
            'externalID',
            'returnUrl',
            'view',
            'agreementRef',
            'cancelUrl',
            'clientLanguage'
        ));
        
        $client = $this->clientFactory->createWsdlClient($this->getPxOrderWsdl());

        $response = @$client->Initialize8($parameters);

        return $this->convertSimpleXmlToArray(new \SimpleXMLElement($response->Initialize8Result));
    }

    /**
     * @link http://www.payexpim.com/technical-reference/pxorder/complete-2/
     * 
     * @param array $parameters
     * 
     * @return \stdClass
     */
    public function Complete(array $parameters)
    {
        $parameters['accountNumber'] = $this->options['accountNumber'];

        $parameters['hash'] = $this->calculateHash($parameters, array(
            'accountNumber',
            'orderRef',
        ));

        $client = $this->clientFactory->createWsdlClient($this->getPxOrderWsdl());

        $response = @$client->Complete($parameters);

        return $this->convertSimpleXmlToArray(new \SimpleXMLElement($response->CompleteResult));
    }

    /**
     * @param array $parameters
     * @param array $parametersKeys
     * 
     * @return string
     */
    protected function calculateHash(array $parameters, array $parametersKeys)
    {
        $orderedParameters = array();
        foreach ($parametersKeys as $parametersKey) {
            if (false == isset($parameters[$parametersKey])) {
                //TODO exception?
                continue;
            }
            
            $orderedParameters[$parametersKey] = $parameters[$parametersKey];
        }
        
        return md5(trim(implode("", $orderedParameters)) . $this->options['encryptionKey']);
    }

    /**
     * @return string
     */
    protected function getPxOrderWsdl()
    {
        return $this->options['sandbox'] ? 
            'https://test-external.payex.com/pxorder/pxorder.asmx?wsdl' : 
            'https://external.payex.com/pxorder/pxorder.asmx?wsdl'
        ;
    }

    /**
     * @param \SimpleXMLElement $element
     * 
     * @return array
     */
    protected function convertSimpleXmlToArray(\SimpleXMLElement $element)
    {
        return json_decode(json_encode((array) $element));
    }
}