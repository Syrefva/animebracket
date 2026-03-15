<?php

namespace Controller {

    use Lib;
    use Api;

    use stdClass;

    class User extends Page {

        public static function generate(array $params) {

            $code = Lib\Url::Get('code', null);
            $action = array_shift($params);

            // Dev login: bypass Reddit OAuth when DEV_LOGIN is defined (e.g. in config)
            if ($action === 'dev-login' && defined('DEV_LOGIN') && DEV_LOGIN) {
                $user = Api\User::getByName('devadmin');
                if (!$user) {
                    $user = new Api\User();
                    $user->name = 'devadmin';
                    $user->admin = true;
                    $user->ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $user->age = 1; // > 0 required
                    $user->sync();
                }
                if ($user && $user->id) {
                    $user->csrfToken = bin2hex(openssl_random_pseudo_bytes(Api\User::USER_CSRF_ENTROPY));
                    Lib\Session::set('user', $user);
                    header('Location: /me/');
                    exit;
                }
            }

            if ($action === 'logout') {
                $user = Api\User::getCurrentUser();
                if ($user) {
                    $user->logout();
                    header('Location: /brackets/');
                }
            }

            if ($code) {
                $success = Api\User::authenticateUser($code);
                if ($success) {
                    $redirect = Lib\Url::Get('state', '/');
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $obj = self::_loginPage();
                    $obj->error = 'We were unable to verify your account at this time or your account does not meet the requirements.';
                    Lib\Display::renderAndAddKey('content', 'login', $obj);
                }
            } else {
                $obj = self::_loginPage();
                Lib\Display::renderAndAddKey('content', 'login', $obj);
            }

        }

        private static function _loginPage() {
            $obj = new stdClass;
            $obj->loginUrl = Api\User::getLoginUrl(Lib\Url::Get('redirect'));

            // Do a mobile check
            if (preg_match('/iphone|android|windows phone/i', $_SERVER['HTTP_USER_AGENT'])) {
                $obj->loginUrl = str_replace('authorize', 'authorize.compact', $obj->loginUrl);
            }

            $obj->originalUrl = Lib\Url::Get('redirect');
            Lib\Display::addKey('page', 'login');
            Lib\Display::addKey('title', 'Login' . DEFAULT_TITLE_SUFFIX);
            return $obj;
        }

    }

}