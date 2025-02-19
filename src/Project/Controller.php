<?php

declare(strict_types=1);

namespace App\Project;

use Diversen\Lang;
use Pebble\ExceptionTrace;
use App\AppUtils;
use App\Exception\FormException;
use App\Project\ProjectModel;
use Exception;
use Pebble\Exception\JSONException;
use Pebble\Attributes\Route;
use Pebble\Router\Request;

class Controller extends AppUtils
{
    private $project_model;
    public function __construct()
    {
        parent::__construct();
        $this->project_model = new ProjectModel();
    }

    #[Route(path: '/project/inactive')]
    public function inactive(Request $request)
    {
        $this->app_acl->isAuthenticatedOrThrow();

        $where = [
            'auth_id' => $this->app_acl->getAuthId(),
            'status' => ProjectModel::PROJECT_CLOSED,
        ];

        $template_data = $this->project_model->getProjectData($where);
        $template_data['title'] = Lang::translate('All inactive projects');
        $template_data['num_projects_open'] = $this->project_model->getNumProjectsOpen();

        $this->template_utils->renderPage(
            'Project/views/index.tpl.php',
            $template_data
        );
    }

    #[Route(path: '/project')]
    public function active()
    {
        $this->app_acl->isAuthenticatedOrThrow();

        $where = [
            'auth_id' => $this->app_acl->getAuthId(),
            'status' => ProjectModel::PROJECT_OPEN,
        ];

        $template_data = $this->project_model->getProjectData($where);
        $template_data['title'] = Lang::translate('All active projects');
        $template_data['num_projects_closed'] = $this->project_model->getNumProjectsClosed();

        $this->template_utils->renderPage(
            'Project/views/index.tpl.php',
            $template_data
        );
    }

    #[Route(path: '/project/view/:project_id')]
    public function view(Request $request)
    {
        $this->app_acl->isProjectOwner($request->param('project_id'));

        $template_data = $this->project_model->getViewData($request->param('project_id'));
        $template_data['title'] = Lang::translate('View project');

        $this->template_utils->renderPage(
            'Project/views/view.tpl.php',
            $template_data
        );
    }

    #[Route(path: '/project/add')]
    public function add()
    {
        $this->app_acl->isAuthenticatedOrThrow();

        $form_vars = [
            'title' => Lang::translate('Add project'),
        ];

        $this->template_utils->renderPage(
            'Project/views/add.tpl.php',
            $form_vars
        );
    }

    #[Route(path: '/project/edit/:project_id')]
    public function edit(Request $request)
    {
        $this->app_acl->isProjectOwner($request->param('project_id'));
        $project = $this->project_model->getOne(['id' => $request->param('project_id')]);

        $form_vars = [
            'title' => Lang::translate('Edit project'),
            'project' => $project,
        ];

        $this->template_utils->renderPage(
            'Project/views/edit.tpl.php',
            $form_vars
        );
    }

    #[Route(path: '/project/post', verbs: ['POST'])]
    public function post()
    {
        try {
            $this->app_acl->isAuthenticatedOrThrow();
            $_POST['auth_id'] = $this->app_acl->getAuthId();

            $this->project_model->create($_POST);
            $this->flash->setMessage(Lang::translate('Project created'), 'success', ['flash_remove' => true]);
            $this->json->renderSuccess(['redirect' => '/project']);
        } catch (FormException $e) {
            throw new JSONException($e->getMessage());
        } catch (Exception $e) {
            $this->log->error('Project.post.exception', ['exception' => ExceptionTrace::get($e)]);
            throw new JSONException($e->getMessage());
        }
    }

    #[Route(path: '/project/put/:project_id', verbs: ['POST'])]
    public function put(Request $request)
    {
        try {
            if (!isset($_POST['status'])) {
                $_POST['status'] = ProjectModel::PROJECT_CLOSED;
            }
            $this->app_acl->isProjectOwner($request->param('project_id'));
            $this->project_model->update($_POST, $request->param('project_id'));
            $this->flash->setMessage(Lang::translate('Project updated'), 'success', ['flash_remove' => true]);
            $this->json->renderSuccess(['redirect' => '/project']);
        } catch (FormException $e) {
            throw new JSONException($e->getMessage());
        } catch (Exception $e) {
            $this->log->error('Project.put.exception', ['exception' => ExceptionTrace::get($e)]);
            throw new JSONException($e->getMessage());
        }
    }

    #[Route(path: '/project/delete/:project_id', verbs: ['POST'])]
    public function delete(Request $request)
    {
        try {
            $this->app_acl->isProjectOwner($request->param('project_id'));
            $this->project_model->delete($request->param('project_id'));
            $this->flash->setMessage(Lang::translate('Project deleted'), 'success', ['flash_remove' => true]);
            $this->json->renderSuccess(['redirect' => '/project']);
        } catch (Exception $e) {
            $this->log->error('Project.delete.exception', ['exception' => ExceptionTrace::get($e)]);
            throw new JSONException($e->getMessage());
        }
    }

    #[Route(path: '/project/tasks/:project_id')]
    public function tasks(Request $request)
    {
        try {
            $this->app_acl->isProjectOwner($request->param('project_id'));
            $data = $this->project_model->getTasksData($request->param('project_id'));
        } catch (Exception $e) {
            $this->log->error('Project.tasks.exception', ['exception' => ExceptionTrace::get($e)]);
            $data['error'] = $e->getMessage();
        }

        $this->template->render(
            'Project/views/task_list.tpl.php',
            $data
        );
    }
}
