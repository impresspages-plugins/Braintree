<?php
/**
 * @package   ImpressPages
 */



namespace Plugin\Braintree;


class SiteController extends \Ip\Controller
{
    public function pay($paymentId, $securityCode)
    {


        $order = Model::getPayment($paymentId);
        if (!$order) {
            throw new \Ip\Exception('Order ' . $paymentId . ' doesn\'t exist');
        }

        $paymentModel = PaymentModel::instance();
        if (!$order['isPaid'] && $paymentModel->isSkipMode()) {
            $paymentModel->markAsPaid($paymentId);
            $order = Model::getPayment($paymentId);
        }

        if (!$order['userId'] && ipUser()->loggedIn()) {
            Model::update($paymentId, array('userId' => ipUser()->userId()));
        }

        if ($order['isPaid']) {
            $response = $paymentModel->successResponse($paymentId, $securityCode);
            return $response;
        } else {
            //show credit card form
            ipAddJs('https://js.braintreegateway.com/v2/braintree.js');
            ipAddJs('assets/braintree.js');

            $clientToken = PaymentModel::instance()->clientToken();
            ipAddJsVariable('braintreeClientToken', $clientToken);


            $data = array(
                'postUrl' => ipRouteUrl('Braintree_charge'),
                'paymentId' => $paymentId,
                'securityCode' => $securityCode
            );

            $answer = ipView('view/page/paymentForm.php', $data)->render();
        }


        return $answer;

    }

    public function charge()
    {
        $nonce = ipRequest()->getPost('payment_method_nonce');
        if (empty($nonce)) {
            throw new \Ip\Exception('Empty payment nonce.');
        }

        $paymentId = ipRequest()->getPost('paymentId');
        $payment = Model::getPayment($paymentId);
        if (!$payment) {
            throw new \Ip\Exception('Unknown payment. Payment ID ' . $paymentId);
        }
        $securityCode = ipRequest()->getPost('securityCode');
        $retryUrl = ipRouteUrl('Braintree_pay', array('paymentId' => $paymentId, 'securityCode' => $securityCode));


        $accountCurrency = ipGetOption('Braintree.currency');
        $amount = $payment['price'];

        if ($accountCurrency != $payment['currency']) {
            $amount = ipConvertCurrency($amount, $payment['currency'], $accountCurrency);
            if ($amount === null) {
                $errorData = array(
                    'sourceCurrency' => $payment['currency'],
                    'destinationCurrency' => $accountCurrency,
                    'retryUrl' => $retryUrl
                );
                $answer = ipView('view/page/currencyConversionError.php', $errorData);
                return $answer;
            }
        }

        $paymentModel = PaymentModel::instance();

        $success = true;
        if (!$paymentModel->isSkipMode()) {
            $success = $paymentModel->charge($amount, $nonce);
        }


        if (!$success) {
            $viewData = array(
                'error' => $paymentModel->lastError(),
                'retryUrl' => $retryUrl
            );
            $answer = ipView('view/page/paymentError.php', $viewData);
            $filterData = array(
                'error' => $paymentModel->lastError(),
                'retryUrl' => $retryUrl,
                'paymentId' => $paymentId
            );
            $answer = ipFilter('Braintree_paymentErrorResponseError', $answer, $filterData);
            return $answer;
        }

        $paymentModel->markAsPaid($paymentId);

        $response = $paymentModel->successResponse($paymentId, $securityCode);
        return $response;
    }

    public function status($paymentId, $securityCode)
    {
        $payment = Model::getPayment($paymentId);
        if (!$payment) {
            throw new \Ip\Exception('Unknown order. Id: ' . $paymentId);
        }
        if ($payment['securityCode'] != $securityCode) {
            throw new \Ip\Exception('Incorrect order security code');
        }

        $data = array(
            'payment' => $payment,
            'paymentUrl' => ipRouteUrl('Braintree_pay', array('paymentId' => $payment['id'], 'securityCode' => $payment['securityCode']))
        );
        $view = ipView('view/page/status.php', $data);
        return $view;
    }
}
