<?php echo ipRenderWidget('Heading', array('title' => __('Payment error', 'Braintree', false))) ?>

<?php echo ipRenderWidget('Text', array('text' => $error)) ?>
<?php echo ipRenderWidget('Text', array('text' => '<a class="_button button" href="' . $retryUrl . '">' . __('Retry', 'Braintree') . '</a>')) ?>
