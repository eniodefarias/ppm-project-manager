<?php

use \Diversen\Lang;
use \Pebble\CSRF;

$csrf_token = (new CSRF())->getToken();

require 'App/templates/header.tpl.php';

?>
<h3 class="sub-menu"><?= Lang::translate('Add project') ?></h3>

<form id="project_add" name="project_add" method="post">
	<label for="title"><?= Lang::translate('Title') ?> *</label>
	<input id="title" type="text" name="title" placeholder="<?= Lang::translate('Enter title') ?>" value="" class="input-large">
	<label for="note"><?= Lang::translate('Note') ?></label>
	<textarea name="note" placeholder="<?= Lang::translate('Add an optional project note') ?>"></textarea>
	<button id="project_submit" type="submit" name="submit" value="submit"><?= Lang::translate('Add') ?></button>
	<div class="loadingspinner hidden"></div>
</form>

<script type="module">
	import {Pebble} from '/App/js/pebble.js';

	
	let return_to = Pebble.getQueryVariable('return_to');
	var spinner = document.querySelector('.loadingspinner');

	var elem = document.getElementById('project_submit');
	elem.addEventListener('click', async function(e) {
		e.preventDefault();

		spinner.classList.toggle('hidden');

		var form = document.getElementById('project_add');
		var data = new FormData(form);

		let res;
		try {
			res = await Pebble.asyncPost('/project/post', data);
			spinner.classList.toggle('hidden');
			if (res.error === false) {
				if (return_to) {
					window.location.replace(return_to);
				} else {
					window.location.replace(res.project_redirect);
				}
			} else {
				Pebble.setFlashMessage(res.error, 'error');
			}
			console.log(res);
		} catch (e) {
			spinner.classList.toggle('hidden');
			console.log(e)
		}
	})
</script>

<?php

require 'App/templates/footer.tpl.php';
