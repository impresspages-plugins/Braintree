<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: mangirdas
 * Date: 7/30/14
 * Time: 2:19 PM
 */

namespace Plugin\Braintree;


class AdminController {
    public function index()
    {
        $config = array(
            'table' => 'braintree',
            'orderBy' => '`id` desc',
            'fields' => array(
                array(
                    'label' => __('Order ID', 'Braintree', false),
                    'field' => 'orderId',
                    'allowUpdate' => false,
                    'allowInsert' => false
                ),
                array(
                    'label' => __('Title', 'Braintree', false),
                    'field' => 'title'
                ),
                array(
                    'label' => __('Price', 'Braintree', false),
                    'field' => 'price',
                    'type' => 'Currency',
                    'currencyField' => 'currency'
                ),
                array(
                    'label' => __('Currency', 'Braintree', false),
                    'field' => 'currency'
                ),
                array(
                    'label' => __('Paid', 'Braintree', false),
                    'field' => 'isPaid',
                    'type' => 'Checkbox'
                ),
                array(
                    'label' => __('User ID', 'Braintree', false),
                    'field' => 'userId',
                    'type' => 'Integer'
                ),
                array(
                    'label' => __('First Name', 'Braintree', false),
                    'field' => 'payer_first_name'
                ),
                array(
                    'label' => __('Last Name', 'Braintree', false),
                    'field' => 'payer_last_name'
                ),
                array(
                    'label' => __('Email', 'Braintree', false),
                    'field' => 'payer_email'
                ),
                array(
                    'label' => __('Country', 'Braintree', false),
                    'field' => 'payer_country'
                ),
                array(
                    'label' => __('Created At', 'Braintree', false),
                    'field' => 'createdAt'
                ),



            )
        );
        return ipGridController($config);
    }
}
