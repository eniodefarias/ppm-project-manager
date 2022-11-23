<?php

use Diversen\Lang;

?>
<h3 class="sub-menu"><?=Lang::translate('Enable two factor authentication')?></h3>

<p><?=Lang::translate('Two factor is already enabled')?></p>
<p><a id="new-qr" href="/twofactor/recreate"><?=Lang::translate('Get a new QR code')?></p>
<script>

    let elem = document.getElementById('new-qr')
    elem.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('<?=Lang::translate('If you create a new QR code then the old code will be deleted')?>')) {
            Pebble.redirect('/twofactor/recreate');
        }
    })

</script>
