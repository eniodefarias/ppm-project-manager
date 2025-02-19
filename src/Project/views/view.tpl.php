<?php

declare(strict_types=1);

use Diversen\Lang;
use App\AppMain;
use App\Utils\HTMLUtils;
use App\AppUtils;

$parsedown = new Parsedown();
$parsedown->setSafeMode(true);

$note = \Pebble\Special::decodeStr($project['note']);
$note_markdown = $parsedown->text($note);

?>

<h3 class="sub-menu">
    <a href="/project"><?= Lang::translate('Projects') ?></a><?= HTMLUtils::getMenuSeparator() ?>
    <?= $project['title'] ?>
</h3>

<div class="action-links">
    <a href="/task/add/<?= $project['id'] ?>" title="<?=Lang::translate('Add new task to project')?>"><?=Lang::translate('Add task')?></a>
    <a href="/project/edit/<?= $project['id'] ?>" title="<?=Lang::translate('Edit project')?>"><?=Lang::translate('Edit project')?></a>
</div>

<div>
<?= $note_markdown ?>
<p><?= Lang::translate('Total time used on project') ?>: <strong><?= $project_time ?></strong></p>
</div>

<?php

if (!empty($tasks)) { ?>
    <p><strong><?= Lang::translate('Tasks waiting') ?> (<?= $tasks_count ?>)</strong> </p>

    <table class="project-table">
        <thead>
            <tr>
                <td class="width-35"><?= Lang::translate('Task') ?></td>
                <td><?= Lang::translate('Date') ?></td>
                <td class='xs-hide'><?= Lang::translate('Time') ?></td>
                <td></td>
            </tr>
        </thead>
        <tbody class="project-tasks" id="tasks-waiting"></tbody>
    </table>
<?php

}

if (!empty($tasks_completed)) { ?>
    <p><strong><?= Lang::translate('Completed tasks') ?> (<?= $tasks_completed_count ?>)</strong></p>
    <table class="project-table">
        <thead>
            <tr>
                <td  class="width-35"><?= Lang::translate('Task') ?></td>
                <td><?= Lang::translate('Date') ?></td>
                <td class='xs-hide'><?= Lang::translate('Time') ?></td>
                <td></td>
            </tr>
        </thead>
        <tbody class="project-tasks" id="tasks-completed"></tbody>
    </table>

<?php

}

?>
<script type="module" nonce="<?=(new AppUtils())->getCSP()->getNonce();?>">

    async function loadHtml(url) {
        return fetch(url)
            .then((response) => {
                return response.text();
            })
            .then((html) => {
                return html;
            });
    }

    let tasksWaiting = document.getElementById('tasks-waiting');
    if (tasksWaiting) {
        let html = await loadHtml('/project/tasks/<?= $project['id'] ?>?status=1&from=0')
        tasksWaiting.innerHTML = html
        tasksWaiting.addEventListener('click', async function(e) {   
            if(e.target.classList.contains('more')) {
                e.preventDefault()
                let more = tasksWaiting.querySelector('#tasks-waiting tr:last-child').remove()
                let html = await loadHtml(e.target.href)
                tasksWaiting.innerHTML = tasksWaiting.innerHTML  + html
            }
        })
    }
    
    let tasksCompleted = document.getElementById('tasks-completed');
    if(tasksCompleted) {
        let html = await loadHtml('/project/tasks/<?= $project['id'] ?>?status=0&from=0')
        tasksCompleted.innerHTML = html

        tasksCompleted.addEventListener('click', async function(e) {   
            if(e.target.classList.contains('more')) {
                e.preventDefault()
                let more = tasksCompleted.querySelector('#tasks-completed tr:last-child').remove()
                let html = await loadHtml(e.target.href)
                tasksCompleted.innerHTML = tasksCompleted.innerHTML  + html
            }
        })
    }

    import { Pebble } from '/js/pebble.js?v=<?=AppMain::VERSION?>';

    document.addEventListener('click', async function(event) {

        if (!event.target.matches('.move_to_today')) return;
        event.preventDefault();

        let todayElem = document.getElementById(event.target);
        let task_id = event.target.dataset.id

        let res;

        let formData = new FormData();
        formData.append('now', 'true')
        formData.append('id', task_id);

        try {
            res = await Pebble.asyncPost('/task/put/' + task_id, formData);
            if (res.error === false) {
                location.reload();
            } else {
                Pebble.setFlashMessage(res.error, 'error');
            }
        } catch (e) {
            await Pebble.asyncPostError('/error/log', e.stack);
        }
    });
</script>