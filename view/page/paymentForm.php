<form class="ipModuleForm" id="checkout" method="post" action="<?php echo $postUrl ?>">
    <div id="dropin"></div>
    <input type="hidden" name="securityToken" value="<?php echo escAttr(ipSecurityToken()) ?>" />
    <input type="hidden" name="paymentId" value="<?php echo escAttr($paymentId) ?>" />
    <input type="hidden" name="securityCode" value="<?php echo escAttr($securityCode) ?>" />
    <input class="btn btn-default" type="submit" value="Pay">
</form>

