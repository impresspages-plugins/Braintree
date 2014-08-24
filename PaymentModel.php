<?php
/**
 * @package   ImpressPages
 */


namespace Plugin\Braintree;


class PaymentModel
{

    const MODE_PRODUCTION = 'Production';
    const MODE_TEST = 'Test';
    const MODE_SKIP = 'Skip';

    protected static $initialized = false;


    protected static $instance;
    protected static $clientToken;

    protected $lastError = null;

    protected function __construct()
    {
        if (self::$initialized == false) {
            if (!$this->isSkipMode()) {
                require_once('lib/Braintree.php');


                \Braintree_Configuration::environment($this->isTestMode() ? 'sandbox' : 'production');
                \Braintree_Configuration::merchantId($this->merchantId()); //merchant id
                \Braintree_Configuration::publicKey($this->publicKey()); //public key
                \Braintree_Configuration::privateKey($this->privateKey()); //private key
            }

            self::$initialized = true;
        }
    }

    protected function __clone()
    {
    }

    /**
     * Get singleton instance
     * @return PaymentModel
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new PaymentModel();
        }

        return self::$instance;
    }

    public function clientToken()
    {
        if ($this->isSkipMode()) {
            throw new \Ip\Exception('Can\'t get client token in skip mode');
        }
        if (self::$clientToken) {
            return self::$clientToken;
        }

        $options = array();
        if (ipUser()->loggedIn()) {
            $options['userId'] = ipUser()->userId();
        }
        $clientToken = \Braintree_ClientToken::generate($options);

        self::$clientToken = $clientToken;
        return self::$clientToken;
    }

    /**
     * @param $amount in cents
     * @param $nonce
     */
    public function charge($amount, $nonce)
    {
        $result = \Braintree_Transaction::sale(array(
                'amount' => $amount / 100,
                'paymentMethodNonce' => $nonce
            )
        );

        if (!$result->success) {
            $this->lastError = $result->errors->deepAll()[0]->message;
            return false;

        }
        $transactionId = $result->transaction->id;

        $result = \Braintree_Transaction::submitForSettlement($transactionId);

        if ($result->success) {
            return true;
        } else {
            $this->lastError = implode('. ', $result->errors->deepAll());
            return false;
        }


    }

    public function lastError()
    {
        return $this->lastError;

    }


    public function markAsPaid($paymentId, $dbData = array(), $eventData = array())
    {
        $payment = Model::getPayment($paymentId);

        $dbData['isPaid'] = 1;
        Model::update($paymentId, $dbData);

        $info = array(
            'id' => $payment['orderId'],
            'paymentId' => $payment['id'],
            'paymentMethod' => 'Braintree',
            'title' => $payment['title'],
            'userId' => $payment['userId']
        );
        $info = array_merge($info, $eventData);
        ipEvent('ipPaymentReceived', $info);

    }

    public function successResponse($paymentId, $securityCode)
    {
        $payment = Model::getPayment($paymentId);

        $orderUrl = ipRouteUrl('Braintree_status', array('paymentId' => $paymentId, 'securityCode' => $securityCode));
        $response = new \Ip\Response\Redirect($orderUrl);
        if (!empty($payment['successUrl'])) {
            $response = new \Ip\Response\Redirect($payment['successUrl']);
        }
        $response = ipFilter('Braintree_paymentCompleteResponse', $response);
        return $response;
    }


    public function merchantId()
    {
        if ($this->isTestMode()) {
            return ipGetOption('Braintree.testMerchantId');
        } else {
            return ipGetOption('Braintree.merchantId');
        }
    }

    public function publicKey()
    {
        if ($this->isTestMode()) {
            return ipGetOption('Braintree.testPublicKey');
        } else {
            return ipGetOption('Braintree.publicKey');
        }
    }

    public function privateKey()
    {
        if ($this->isTestMode()) {
            return ipGetOption('Braintree.testPrivateKey');
        } else {
            return ipGetOption('Braintree.privateKey');
        }
    }


    public function isTestMode()
    {
        return ipGetOption('Braintree.mode') == self::MODE_TEST;
    }


    public function isSkipMode()
    {
        return ipGetOption('Braintree.mode') == self::MODE_SKIP;
    }

    public function isProductionMode()
    {
        return ipGetOption('Braintree.mode') == self::MODE_PRODUCTION;
    }

    public function correctConfiguration()
    {
        if ($this->getActive() && $this->getSid()) {
            return true;
        } else {
            return false;
        }
    }

}
