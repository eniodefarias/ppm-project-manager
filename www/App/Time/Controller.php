<?php

namespace App\Time;

use App\AppMain;
use App\Project\ProjectModel;
use App\Time\TimeModel;
use Pebble\JSON;

class Controller
{

    private $app_acl;
    public function __construct()
    {
        $app_main = new AppMain();
        $this->app_acl = $app_main->getAppACL();
    }

    /**
     * @route /time/add/:task_id
     * @verbs GET
     */
    public function add($params)
    {

        $task = $this->app_acl->getTask($params['task_id']);
        $this->app_acl->authUserIsProjectOwner($task['project_id']);

        $project = (new ProjectModel())->getOne($task['project_id']);
        $time_rows = (new TimeModel())->getAll(['task_id' => $task['id']]);

        $time_vars = [
            'task' => $task,
            'project' => $project,
            'time_rows' => $time_rows,
        ];

        \Pebble\Template::render(
            'App/Time/views/time_add.tpl.php',
            $time_vars
        );
    }

    /**
     * @route /time/post
     * @verbs POST
     */
    public function post()
    {

        $response['error'] = false;

        try {

            $task = $this->app_acl->getTask($_POST['task_id']);
            $this->app_acl->authUserIsProjectOwner($task['project_id']);

            // POST time
            $post = $_POST;
            $post['project_id'] = $task['project_id'];
            (new TimeModel())->create($post);
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $response['post'] = $_POST;
        }

        $response['project_redirect'] = '/project/view/' . $task['project_id'];

        echo JSON::responseAddRequest($response);
    }

    /**
     * @route /time/delete/:id
     * @verbs POST
     */
    public function delete($params)
    {

        $response['error'] = false;
        $response['post'] = $_POST;

        try {

            $time = $this->app_acl->getTime($params['id']);
            $this->app_acl->authUserIsProjectOwner($time['project_id']);

            (new TimeModel())->delete(['id' => $params['id']]);
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $response['post'] = $_POST;
        }

        echo JSON::responseAddRequest($response);
    }
}
