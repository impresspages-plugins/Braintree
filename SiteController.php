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



        if (!$order['userId'] && ipUser()->loggedIn()) {
            Model::update($paymentId, array('userId' => ipUser()->userId()));
        }

        if ($order['isPaid']) {
            $statusPageUrl = ipRouteUrl('Braintree_status', array('paymentId' => $paymentId, 'securityCode' => $securityCode));
            $answer = new \Ip\Response\Redirect($statusPageUrl);
        } else {
            //redirect to the payment
            $paymentModel = PaymentModel::instance();

            ipAddJs('https://js.braintreegateway.com/v2/braintree.js');
            ipAddJs('assets/braintree.js');

            $form = '<form id="checkout" method="post" action="/checkout">
  <div id="dropin"></div>
  <input type="submit" value="Pay $10">
</form>';

            $data = array(
                'form' => $form
            );

            $answer = ipView('view/page/paymentRedirect.php', $data)->render();
        }


        return $answer;

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
