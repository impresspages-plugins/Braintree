<?php

$routes['braintree/pay/{paymentId}/{securityCode}'] = array(
    'name' => 'Braintree_pay',
    'plugin' => 'Braintree',
    'controller' => 'SiteController',
    'action' => 'pay'
);


$routes['braintree/ipn'] = array(
    'name' => 'Braintree_ipn',
    'plugin' => 'Braintree',
    'controller' => 'PublicController',
    'action' => 'ipn'
);


$routes['braintree/userback'] = array(
    'name' => 'Braintree_userBack',
    'plugin' => 'Braintree',
    'controller' => 'PublicController',
    'action' => 'userBack'
);

$routes['braintree/status/{paymentId}/{securityCode}'] = array(
    'name' => 'Braintree_status',
    'plugin' => 'Braintree',
    'controller' => 'SiteController',
    'action' => 'status'
);
