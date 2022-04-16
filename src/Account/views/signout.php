<?php

use Diversen\Lang;

require  'templates/header.tpl.php';

?>

<h3 class="sub-menu"><?=Lang::translate('Sign out')?></h2>


<p><?=Lang::translate('You are logged in')?>. <a href="/account/logout"><?=Lang::translate('Sign out')?></a>

<p><?=Lang::translate('Or')?>: <a href="/account/logout?all_devices=1"><?=Lang::translate('Sign out of all devices')?></a></p>


<?php

require 'templates/footer.tpl.php';
