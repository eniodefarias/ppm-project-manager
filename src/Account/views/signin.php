<?php declare(strict_types=1);

use Diversen\Lang;
use App\AppMain;
use App\AppUtils;

?>

<h3 class="sub-menu"><?=$title?></h3>

<p><?=Lang::translate('By signing in you agree to the following terms of service, privacy policy, and disclaimer')?></p>
<div class="action-links">
    <a href="/account/terms/terms-of-service"><?=Lang::translate('Terms of service')?></a>
    <a href="/account/terms/privacy-policy"><?=Lang::translate('Privacy policy')?></a>
    <a href="/account/terms/disclaimer"><?=Lang::translate('Disclaimer')?></a>
</div>
<div class="clear"></div>
<form id="login-form">
    
    <?=(new AppUtils())->getCSRF()->getCSRFFormField()?>
    
    <label for="email"><?= Lang::translate('E-mail') ?></label>
    <input id="email" type="text" name="email">

    <label for="password"><?= Lang::translate('Password') ?></label>
    <input id="password" type="password" name="password">

    <label for="keep_login">
        <?= Lang::translate('Keep me signed in') ?>
    </label>

    <input type="checkbox" value="1" id="keep_login" name="keep_login" checked="checked">
        
    <br />
    <button id="login"><?= Lang::translate('Send') ?></button>
    <div class="loadingspinner hidden"></div>
</form>

<script type="module" nonce="<?=(new AppUtils())->getCSP()->getNonce();?>">

    import {Pebble} from '/js/pebble.js?v=<?=AppMain::VERSION?>';
    
    var spinner = document.querySelector('.loadingspinner');

    document.getElementById('login').addEventListener("click", async function(e) {

        e.preventDefault();

        spinner.classList.toggle('hidden');

        const form = document.getElementById('login-form');
        const data = new FormData(form);

        try {
            const res = await Pebble.asyncPost('/account/post_signin', data);
            
            if (res.error === false) {
                Pebble.redirect(res.redirect);
            } else {
                Pebble.setFlashMessage(res.message, 'error');
            }

        } catch (e) {
            await Pebble.asyncPostError('/error/log', e.stack);
        } finally {
            spinner.classList.toggle('hidden');
        }
    });

</script>
