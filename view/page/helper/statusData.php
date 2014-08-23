<table>
    <tr>
        <td><b><?php echo __('Order ID', 'Braintree') ?></b></td>
        <td><?php echo esc($payment['orderId']) ?></td>
    </tr>
    <tr>
        <td><b><?php echo __('Paid', 'Braintree') ?></b></td>
        <td><?php echo __($payment['isPaid'] ? 'Yes' : 'No', 'Braintree') ?>
            <?php if (!$payment['isPaid']) { ?>
                <a href="<?php echo $paymentUrl ?>">(<?php echo __('Pay Now', 'Braintree') ?>)</a>
            <?php } ?>
        </td>
    </tr>
    <tr>
        <td><b><?php echo __('Item', 'Braintree') ?></b></td>
        <td><?php echo esc($payment['title']) ?></td>
    </tr>
    <tr>
        <td><b><?php echo __('Amount', 'Braintree') ?></b></td>
        <td><?php echo esc(ipFormatPrice($payment['price'], $payment['currency'], 'Braintree')) ?></td>
    </tr>
    <tr>
        <td><b><?php echo __('Date', 'Braintree') ?></b></td>
        <td><?php echo esc(ipFormatDateTime(strtotime($payment['createdAt']), 'Braintree')) ?></td>
    </tr>
</table>
