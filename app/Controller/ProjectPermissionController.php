<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\AccessForbiddenException;
use Kanboard\Core\Security\Role;

/**
 * Project Permission Controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class ProjectPermissionController extends BaseController
{
    /**
     * Permissions are only available for team projects
     *
     * @access protected
     * @param  integer      $project_id    Default project id
     * @return array
     * @throws AccessForbiddenException
     */
    protected function getProject($project_id = 0)
    {
        $project = parent::getProject($project_id);

        if ($project['is_private'] == 1) {
            throw new AccessForbiddenException();
        }

        return $project;
    }

    /**
     * Show all permissions
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws AccessForbiddenException
     */
    public function index(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();

        if (empty($values)) {
            $values['role'] = Role::PROJECT_MEMBER;
        }

        $this->response->html($this->helper->layout->project('project_permission/index', array(
            'project' => $project,
            'users' => $this->projectUserRoleModel->getUsers($project['id']),
            'groups' => $this->projectGroupRoleModel->getGroups($project['id']),
            'roles' => $this->role->getProjectRoles(),
            'values' => $values,
            'errors' => $errors,
            'title' => t('Project Permissions'),
        )));
    }

    /**
     * Allow everybody
     *
     * @access public
     */
    public function allowEverybody()
    {
        $project = $this->getProject();
        $values = $this->request->getValues() + array('is_everybody_allowed' => 0);

        if ($this->projectModel->update($values)) {
            $this->flash->success(t('Project updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this project.'));
        }

        $this->response->redirect($this->helper->url->to('ProjectPermissionController', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Add user to the project
     *
     * @access public
     */
    public function addUser()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();

        if (empty($values['user_id'])) {
            $this->flash->failure(t('User not found.'));
        } elseif ($this->projectUserRoleModel->addUser($values['project_id'], $values['user_id'], $values['role'])) {
            $this->flash->success(t('Project updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this project.'));
        }

        $this->response->redirect($this->helper->url->to('ProjectPermissionController', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Revoke user access
     *
     * @access public
     */
    public function removeUser()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $user_id = $this->request->getIntegerParam('user_id');

        if ($this->projectUserRoleModel->removeUser($project['id'], $user_id)) {
            $this->flash->success(t('Project updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this project.'));
        }

        $this->response->redirect($this->helper->url->to('ProjectPermissionController', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Change user role
     *
     * @access public
     */
    public function changeUserRole()
    {
        $project = $this->getProject();
        $values = $this->request->getJson();

        if (! empty($project) && ! empty($values) && $this->projectUserRoleModel->changeUserRole($project['id'], $values['id'], $values['role'])) {
            $this->response->json(array('status' => 'ok'));
        } else {
            $this->response->json(array('status' => 'error'));
        }
    }

    /**
     * Add group to the project
     *
     * @access public
     */
    public function addGroup()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();

        if (empty($values['group_id']) && ! empty($values['external_id'])) {
            $values['group_id'] = $this->groupModel->create($values['name'], $values['external_id']);
        }

        if ($this->projectGroupRoleModel->addGroup($project['id'], $values['group_id'], $values['role'])) {
            $this->flash->success(t('Project updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this project.'));
        }

        $this->response->redirect($this->helper->url->to('ProjectPermissionController', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Revoke group access
     *
     * @access public
     */
    public function removeGroup()
    {
        $this->checkCSRFParam();
        $project = $this->getProject();
        $group_id = $this->request->getIntegerParam('group_id');

        if ($this->projectGroupRoleModel->removeGroup($project['id'], $group_id)) {
            $this->flash->success(t('Project updated successfully.'));
        } else {
            $this->flash->failure(t('Unable to update this project.'));
        }

        $this->response->redirect($this->helper->url->to('ProjectPermissionController', 'index', array('project_id' => $project['id'])));
    }

    /**
     * Change group role
     *
     * @access public
     */
    public function changeGroupRole()
    {
        $project = $this->getProject();
        $values = $this->request->getJson();

        if (! empty($project) && ! empty($values) && $this->projectGroupRoleModel->changeGroupRole($project['id'], $values['id'], $values['role'])) {
            $this->response->json(array('status' => 'ok'));
        } else {
            $this->response->json(array('status' => 'error'));
        }
    }
}
