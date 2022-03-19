<?php

declare(strict_types=1);

namespace App\Account;

use Diversen\Lang;
use Pebble\Captcha;
use Pebble\CSRF;
use Pebble\Flash;
use Pebble\JSON;
use Pebble\SessionTimed;
use Pebble\ExceptionTrace;

use App\Account\Mail;
use App\Account\Validate;
use App\TwoFactor\TwoFactorModel;
use App\AppMain;
use Exception;
use stdClass;

class Controller
{
    public $auth;
    public $config;
    public $db;
    public $log;

    public function __construct()
    {
        $app_main = new AppMain();
        $this->auth = $app_main->getAuth();
        $this->config = $app_main->getConfig();
        $this->db = $app_main->getDB();
        $this->log = $app_main->getLog();
    }

    /**
     * @route /account/signin
     * @verbs GET
     */

    public function index(array $params, stdClass $obj)
    {
        if ($this->auth->isAuthenticated()) {
            $form_vars = ['title' => Lang::translate('Signin')];
            \Pebble\Template::render(
                'App/Account/views/signout.php',
                $form_vars
            );
        } else {
            $form_vars = [
                'title' => Lang::translate('Signin'),
                'csrf_token' => (new CSRF())->getToken(),
            ];

            \Pebble\Template::render(
                'App/Account/views/signin.php',
                $form_vars
            );
        }
    }

    /**
     * Log the user out and redirect
     * @route /account/logout
     * @verbs GET
     */
    public function logout()
    {
        $auth_id = $this->auth->getAuthId();

        if (isset($_GET['all_devices'])) {
            $this->auth->unlinkAllCookies($auth_id);
        } else {
            $this->auth->unlinkCurrentCookie();
        }

        $this->log->info('Account.logout.success', ['auth_id' => $auth_id]);

        $redirect = $this->config->get('App.logout_redirect');
        header("Location: $redirect");
        return;
    }

    /**
     * Page with link to logout route
     * @route /account/signout
     * @verbs GET
     */
    public function signout()
    {
        \Pebble\Template::render(
            'App/Account/views/signout.php',
            []
        );
        return;
    }

    /**
     * @route /account/post_login
     * @verbs POST
     */
    public function post_login()
    {
        usleep(100000);

        $validate = new Validate();
        $response = $validate->postLogin();
        if ($response['error'] === true) {
            echo JSON::response($response);
            return;
        }

        $this->log->info('Account.post_login.email', ['email' => $_POST['email']]);

        $response['error'] = true;
        $row = $this->auth->authenticate($_POST['email'], $_POST['password']);

        if (!empty($row)) {
            $response['error'] = false;
            if ($this->twoFactor($response, $row)) {
                return;
            }

            $response['redirect'] = $this->config->get('App.login_redirect');
            if (isset($_POST['keep_login'])) {
                $this->auth->setPermanentCookie($row, $this->config->get('Auth.cookie_seconds_permanent'));
            } else {
                $this->auth->setSessionCookie($row, $this->config->get('Auth.cookie_seconds'));
            }

            $this->log->info('Account.post_login.success', ['auth_id' => $row['id']]);

            Flash::setMessage(Lang::translate('You are logged in'), 'success', ['flash_remove' => true]);
        } else {
            $response['message'] = Lang::translate('Wrong email or password. Or your account has not been activated.');
        }

        echo JSON::response($response);
    }

    /**
     * Checks if the user has two factor enabled and if so,
     * redirect to the two factor page
     * @return bool $res True if the user has two factor enabled
     */
    private function twoFactor(array $response, array $row)
    {
        if ($this->config->get('TwoFactor.enabled')) {
            $two_factor = new TwoFactorModel();
            if ($two_factor->isTwoFactorEnabled($row['id'])) {
                $session_timed = new SessionTimed();
                $session_timed->setValue('auth_id_to_login', $row['id'], $this->config->get('TwoFactor.time_to_verify'));
                $session_timed->setValue('keep_login', isset($_POST['keep_login']), $this->config->get('TwoFactor.time_to_verify'));
                Flash::setMessage(Lang::translate('Verify your login.'), 'success', ['flash_remove' => true]);
                $response['redirect'] = '/2fa/verify';
                echo JSON::response($response);
                return true;
            }
        }
        return false;
    }

    /**
     * @route /account/signup
     * @verbs GET
     */
    public function signup()
    {
        $form_vars = [
            'title' => Lang::translate('Signup'),
            'token' => (new CSRF())->getToken(),
        ];

        \Pebble\Template::render(
            'App/Account/views/signup.php',
            $form_vars
        );
    }

    /**
     * @route /account/verify
     * @verbs GET
     */
    public function verify()
    {
        $key = $_GET['key'] ?? '';

        $row = $this->atuh->getByWhere(['random' => $key]);
        $res = $this->auth->verifyKey($key);

        if ($res) {
            Flash::setMessage(Lang::translate('Your account has been verified. You may log in'), 'success');
            $this->log->info('Account.verify.success', ['auth_id' => $row['id']]);
        } else {
            Flash::setMessage(Lang::translate('The key supplied has already been used'), 'error');
            $this->log->info('Account.verify.failed', ['auth_id' => $row['id']]);
        }

        header("Location: /account/signin");
    }

    /**
     * @route /account/captcha
     * @verbs GET
     */
    public function captcha()
    {
        $captcha = new Captcha();
        $captcha->outputImage();
    }

    /**
     * @route /account/post_signup
     * @verbs POST
     */
    public function post_signup()
    {
        usleep(100000);

        $validate = new Validate();
        $response = $validate->postSignup();
        if ($response['error'] === true) {
            echo JSON::response($response);
            return;
        }

        $this->db->beginTransaction();

        $res = $this->auth->create($_POST['email'], $_POST['password']);
        if ($res) {
            $this->log->info('Account.post_signup.success', ['email' => $_POST['email']]);
            if ($this->config->get('Account.no_email_verify')) {
                $this->db->update('auth', ['verified' => 1], ['email' => $_POST['email']]);
                $message = Lang::translate('Account has been created. You may log in');
                $mail_success = true;
            } else {
                $row = $validate->getByEmail($_POST['email']);
                $mail = new Mail();

                try {
                    $mail_success = true;
                    $mail->sendSignupMail($row);
                } catch (Exception $e) {
                    $this->log->error('Account.post_signup.exception', ['exception' => ExceptionTrace::get($e)]);
                    $mail_success = false;
                }

                $message = Lang::translate('User created. An activation link has been sent to your email. Press the link and your account will be activated');
            }

            if (!$mail_success) {
                $this->db->rollback();
                $this->log->info('Account.post_signup.rollback');
                $response['error'] = true;
                $response['message'] = Lang::translate('The system could not create an account. Please try again another time');
            } else {
                $this->db->commit();
                $this->log->info('Account.post_signup.commit', ['auth_id' => $row['id']]);
                Flash::setMessage($message, 'success');
                $response['error'] = false;
                $response['redirect'] = '/account/signin';
            }
        }

        echo JSON::response($response);
    }

    /**
     * @route /account/recover
     * @verbs GET
     */
    public function recover()
    {
        $token = (new CSRF())->getToken();
        $form_vars = [
            'title' => Lang::translate('Recover account'),
            'token' => $token,
        ];

        \Pebble\Template::render(
            'App/Account/views/recover.php',
            $form_vars
        );
    }

    /**
     * @route /account/post_recover
     * @verbs POST
     */
    public function post_recover()
    {
        $captcha = new Captcha();
        $validate = new Validate();

        $response = ['error' => true];

        $csrf = new CSRF();
        if (!$csrf->validateToken()) {
            $response['message'] = Lang::translate('Invalid Request. We will look in to this');
            echo JSON::response($response);
            return;
        }

        $row = $validate->getByEmail($_POST['email']);

        if (empty($row)) {
            $response['message'] = Lang::translate('No such email in our system');
            echo JSON::response($response);
            return;
        }

        if (!$captcha->validate($_POST['captcha'])) {
            $response['message'] = Lang::translate('The image text does not match your submission');
            echo JSON::response($response);
            return;
        }

        if (!empty($row)) {
            $mail = new mail();
            try {
                $mail->sendRecoverMail($row);
                $mail_success = true;
            } catch (Exception $e) {
                $this->log->error('Account.post_recover.exception', ['exception' => ExceptionTrace::get($e)]);
                $mail_success = false;
            }

            if ($mail_success) {
                $this->log->info('Account.post_recover.success', ['auth_id' => $row['id']]);
                Flash::setMessage(
                    Lang::translate('A notification email has been sent with instructions to create a new password'),
                    'success'
                );
                $response['error'] = false;
            } else {
                $response['message'] = Lang::translate('E-mail could not be sent. Try again later.');
            }
        }

        echo JSON::response($response);
        return;
    }

    /**
     * @route /account/newpassword
     * @verbs GET,POST
     */
    public function newpassword()
    {
        $key = $_GET['key'] ?? null;

        $row = $this->auth->getByWhere(['random' => $key]);

        if (!empty($_POST) && !empty($row)) {
            $validate = new Validate();
            $response = $validate->passwords();

            if ($response['error'] === true) {
                Flash::setMessage($response['message'], 'error');
                header("Location: $_SERVER[REQUEST_URI]");
            } else {
                $this->auth->unlinkAllCookies($row['id']);
                $this->auth->updatePassword($row['id'], $_POST['password']);

                $this->log->info('Account.newpassword.success', ['auth_id' => $row['id']]);

                Flash::setMessage(Lang::translate('Your password has been updated'), 'success');

                header("Location: /account/signin");
            }

            return;
        }

        $vars['title'] = Lang::translate('Create new password');
        if (!empty($row)) {
            $vars['error'] = 0;
        } else {
            Flash::setMessage(Lang::translate('No such account connected to supplied key'), 'error');
            $vars['error'] = 1;
        }

        $vars['token'] = (new CSRF())->getToken();

        \Pebble\Template::render(
            'App/Account/views/newpassword.php',
            $vars
        );
    }
}
