<?php echo ipRenderWidget('Heading', array('title' => __('Currency conversion error', 'Braintree', false))) ?>

<?php echo ipRenderWidget('Text', array('text' =>
        ipReplacePlaceholders(
            __('An error occurred while converting {sourceCurrency} to {destinationCurrency}. Please install currency conversion plugin and enter appropriate conversion rates.', 'Braintree', false),
            'Braintree',
            array(
                'sourceCurrency' => $sourceCurrency,
                'destinationCurrency' => $destinationCurrency
            )
        )
    ))
?>
