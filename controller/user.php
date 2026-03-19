<?php

namespace Controller {

    use Lib;
    use Api;

    use stdClass;

    class User extends Page {

        public static function generate(array $params) {

            $code = Lib\Url::Get('code', null);
            $action = array_shift($params);

            // Dev routes (DEV_LOGIN only)
            if (defined('DEV_LOGIN') && DEV_LOGIN) {
                if ($action === 'dev-login') {
                    $username = isset($params[0]) ? trim($params[0]) : '';
                    if (!$username) {
                        header('Location: /');
                        exit;
                    }
                    if (!preg_match('/^(devadmin|devuser)_[a-f0-9]+$/i', $username)) {
                        header('Location: /?error=dev_forbidden');
                        exit;
                    }
                    $user = Api\User::getByName($username);
                    if (!$user) {
                        header('Location: /?error=dev_forbidden');
                        exit;
                    }
                    $cookieIds = self::_getDevUsersCreatedCookie();
                    if (!in_array($user->id, $cookieIds)) {
                        header('Location: /?error=dev_forbidden');
                        exit;
                    }
                    $user->csrfToken = bin2hex(openssl_random_pseudo_bytes(Api\User::USER_CSRF_ENTROPY));
                    Lib\Session::set('user', $user);
                    $redirect = Lib\Url::Get('redirect', '/me/');
                    header('Location: ' . $redirect);
                    exit;
                }
                if ($action === 'dev-create') {
                    $type = isset($params[0]) ? strtolower(trim($params[0])) : '';
                    if ($type !== 'admin' && $type !== 'user') {
                        header('Location: /');
                        exit;
                    }
                    $user = Api\User::createDevUser($type === 'admin');
                    if (!$user) {
                        header('Location: /?error=dev_create_failed');
                        exit;
                    }
                    self::_appendDevUsersCreatedCookie($user->id);
                    $user->csrfToken = bin2hex(openssl_random_pseudo_bytes(Api\User::USER_CSRF_ENTROPY));
                    Lib\Session::set('user', $user);
                    $redirect = Lib\Url::Get('redirect', '/me/');
                    header('Location: ' . $redirect);
                    exit;
                }
                if ($action === 'dev-logout') {
                    $currentUser = Api\User::getCurrentUser();
                    if ($currentUser) {
                        $currentUser->logout();
                    }
                    $redirect = Lib\Url::Get('redirect', '/brackets/');
                    header('Location: ' . $redirect);
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

        private static function _getDevUsersCreatedCookie() {
            return \Api\User::getDevUsersCreatedCookieIds();
        }

        private static function _appendDevUsersCreatedCookie($userId) {
            $ids = self::_getDevUsersCreatedCookie();
            if (!in_array($userId, $ids)) {
                $ids[] = $userId;
            }
            $value = implode(',', $ids);
            $expires = time() + (86400 * 365); // 1 year
            $domain = defined('SESSION_DOMAIN') ? SESSION_DOMAIN : '';
            setcookie('dev_users_created', $value, $expires, '/', $domain, false, true);
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