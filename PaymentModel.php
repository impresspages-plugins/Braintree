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
            require_once('lib/Braintree.php');


            \Braintree_Configuration::environment('sandbox');
            \Braintree_Configuration::merchantId('xd2kdfqd2n845gmd'); //merchant id
            \Braintree_Configuration::publicKey('hj3p5mfg5d3tzm2f'); //public key
            \Braintree_Configuration::privateKey('e540442c1f4fb0cd3daa4d11419070f2'); //private key

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

        $transactionId = $result->transaction->id;

        $result = \Braintree_Transaction::submitForSettlement($transactionId);

        if ($result->success) {
            return true;
        } else {
            $this->lastError = implode('. ', $result->errors);
            return false;
        }


    }

    public function lastError()
    {
        return $this->lastError;

    }

    public function processCallback($postData)
    {
        if (empty($postData['txn_type'])) {
            return;
        }



        if (!$response["status"]) {
            ipLog()->error(
                'Braintree.ipn: notification check error',
                $response
            );
            return;
        }

        $customData = json_decode($postData['custom'], true);

        $paymentId = isset($customData['paymentId']) ? $customData['paymentId'] : null;
        $currency = isset($postData['mc_currency']) ? $postData['mc_currency'] : null;
        $receiver = isset($postData['receiver_email']) ? $postData['receiver_email'] : null;
        $amount = isset($postData['mc_gross']) ? $postData['mc_gross'] : null;
        $test = isset($postData['test_ipn']) ? $postData['test_ipn'] : null;


        if ($test != $this->isTestMode()) {
            ipLog()->error('Braintree.ipn: IPN rejected. Test mode conflict', $response);
            return;
        }



        switch ($postData['payment_status']) {
            case 'Completed':
                $payment = Model::getPayment($paymentId);

                if (!$payment) {
                    ipLog()->error('Braintree.ipn: Order not found.', array('paymentId' => $paymentId));
                    return;
                }

                if ($payment['currency'] != $currency) {
                    ipLog()->error('Braintree.ipn: IPN rejected. Currency doesn\'t match', array('paypal currency' => $currency, 'expected currency' => $payment['currency']));
                    return;
                }

                $orderPrice = $payment['price'];
                $orderPrice = str_pad($orderPrice, 3, "0", STR_PAD_LEFT);
                $orderPrice = substr_replace($orderPrice, '.', -2, 0);

                if ($amount != $orderPrice) {
                    ipLog()->error('Braintree.ipn: IPN rejected. Price doesn\'t match', array('paypal price' => $amount, 'expected price' => '' . $orderPrice));
                    return;
                }

                if ($receiver != $this->getSid()) {
                    ipLog()->error('Braintree.ipn: IPN rejected. Recipient doesn\'t match', array('paypal recipient' => $receiver, 'expected recipient' => $this->getSid()));
                    return;
                }

                if ($response["httpResponse"] != 'VERIFIED') {
                    ipLog()->error('Braintree.ipn: Paypal doesn\'t recognize the payment', $response);
                    return;
                }

                if ($payment['isPaid']) {
                    ipLog()->error('Braintree.ipn: Order is already paid', $response);
                    return;
                }

                $info = array(
                    'id' => $payment['orderId'],
                    'paymentId' => $payment['id'],
                    'paymentMethod' => 'Braintree',
                    'title' => $payment['title'],
                    'userId' => $payment['userId']
                );

                ipLog()->info('Braintree.ipn: Successful payment', $info);

                $newData = array(
                    'isPaid' => 1
                );
                if (isset($postData['first_name'])) {
                    $newData['payer_first_name'] = $postData['first_name'];
                    $info['payer_first_name'] = $postData['first_name'];
                }
                if (isset($postData['last_name'])) {
                    $newData['payer_last_name'] = $postData['last_name'];
                    $info['payer_last_name'] = $postData['last_name'];
                }
                if (isset($postData['payer_email'])) {
                    $newData['payer_email'] = $postData['payer_email'];
                    $info['payer_email'] = $postData['payer_email'];
                }
                if (isset($postData['residence_country'])) {
                    $newData['payer_country'] = $postData['residence_country'];
                    $info['payer_country'] = $postData['residence_country'];
                }

                Model::update($paymentId, $newData);


                ipEvent('ipPaymentReceived', $info);


                break;
        }





    }


//
//
//    public function getBraintreeForm($paymentId)
//    {
//
//
//
//
//        return $form;
//
//
//
//        if (!$this->getSid()) {
//            throw new \Ip\Exception('Please enter configuration values for Braintree plugin');
//        }
//
//
//        $payment = Model::getPayment($paymentId);
//        if (!$payment) {
//            throw new \Ip\Exception("Can't find order id. " . $paymentId);
//        }
//
//
//        $currency = $payment['currency'];
//        $privateData = array(
//            'paymentId' => $paymentId,
//            'userId' => $payment['userId'],
//            'securityCode' => $payment['securityCode']
//        );
//
//
//
//        $values = array(
////            'business' => $this->getSid(),
////            'amount' => $payment['price'] / 100,
////            'currency_code' => $currency,
////            'no_shipping' => 1,
////            'custom' => json_encode($privateData),
//            'return' => ipRouteUrl('Braintree_userBack'),
//            'notify_url' => ipRouteUrl('Braintree_ipn'),
////            'item_name' => $payment['title'],
//            'item_number' => $payment['id']
//        );
//
//        if (!empty($payment['cancelUrl'])) {
//            $values['cancel_return'] = $payment['cancelUrl'];
//        }
//
//
//
//        $params = array(
//            'sid' => '1817037',
//            'mode' => '2CO',
//            'li_0_product_id' => $payment['id'],
//            'li_0_name' => $payment['title'],
//            'li_0_price' => $payment['price'] / 100,
//            'currency_code' => $currency,
//            'custom' => json_encode($privateData),
//            'demo' => $this->isTestMode() ? 'Y' : 'N',
//            'x_receipt_link_url' => 'http://develop.apro.lt',
//            'return_url' => 'http://develop.apro.lt'
//        );
//
//
//
//        return $form;
//    }

    /**
     *
     *  Returns $data encoded in UTF8. Very useful before json_encode as it fails if some strings are not utf8 encoded
     * @param mixed $dat array or string
     * @return array
     */
    private function checkEncoding($dat)
    {
        if (is_string($dat)) {
            if (mb_check_encoding($dat, 'UTF-8')) {
                return $dat;
            } else {
                return utf8_encode($dat);
            }
        }
        if (is_array($dat)) {
            $answer = array();
            foreach ($dat as $i => $d) {
                $answer[$i] = $this->checkEncoding($d);
            }
            return $answer;
        }
        return $dat;
    }


    public function getSid()
    {
        if ($this->isTestMode()) {
            return ipGetOption('Braintree.testSid');
        } else {
            return ipGetOption('Braintree.sid');
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
