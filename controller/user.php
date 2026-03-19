<?php

namespace Controller {

    use Lib;
    use Api;

    use stdClass;

    class User extends Page {

        public static function generate(array $params) {

            $code = Lib\Url::Get('code', null);
            $action = array_shift($params);
            $devActions = ['dev-login', 'dev-create', 'dev-logout', 'dev-create-seeded-bracket'];

            if (in_array($action, $devActions, true) && (!defined('DEV_LOGIN') || !DEV_LOGIN)) {
                http_response_code(404);
                exit;
            }

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
                if ($action === 'dev-create-seeded-bracket') {
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'POST required' ]);
                    }
                    $user = Api\User::getCurrentUser();
                    if (!$user) {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'Login required' ]);
                    }
                    $bracket = new Api\Bracket();
                    $bracket->name = 'Test ' . bin2hex(random_bytes(3));
                    $bracket->rules = 'test';
                    $bracket->state = 0;
                    $bracket->start = time();
                    $bracket->advanceHour = -1;
                    $bracket->minAge = 2592000;
                    $bracket->captcha = Api\Bracket::$CAPTCHA_STATUS['NEVER'];
                    $bracket->nameLabel = 'Character name';
                    $bracket->sourceLabel = 'Source';
                    $bracket->hidden = 0;
                    $bracket->generatePerma();
                    if (!$bracket->sync()) {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'Failed to create bracket' ]);
                    }
                    if (!$bracket->addUser($user)) {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'Failed to add user as owner' ]);
                    }
                    $bracketId = $bracket->id;
                    $created = time();
                    $placeholderUrl = 'https://placeholder.local/1.jpg';
                    $nomineeValues = [];
                    $charValues = [];
                    $nomineeParams = [ ':bracketId' => $bracketId, ':created' => $created, ':placeholderUrl' => $placeholderUrl ];
                    $charParams = [ ':bracketId' => $bracketId ];
                    for ($i = 1; $i <= 32; $i++) {
                        $name = "Nominee $i";
                        $nomineeValues[] = "(:bracketId, :name$i, NULL, :created, 1, :placeholderUrl)";
                        $charValues[] = "(:bracketId, :name$i, '', NULL, NULL)";
                        $nomineeParams[":name$i"] = $name;
                        $charParams[":name$i"] = $name;
                    }
                    $nomineeSql = 'INSERT INTO nominee (bracket_id, nominee_name, nominee_source, nominee_created, nominee_processed, nominee_image) VALUES ' . implode(', ', $nomineeValues);
                    if (!Lib\Db::Query($nomineeSql, $nomineeParams)) {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'Failed to seed nominees' ]);
                    }
                    $charSql = 'INSERT INTO `character` (bracket_id, character_name, character_source, character_seed, character_meta) VALUES ' . implode(', ', $charValues);
                    if (!Lib\Db::Query($charSql, $charParams)) {
                        Lib\Display::renderJson((object)[ 'success' => false, 'message' => 'Failed to seed characters' ]);
                    }
                    $result = Lib\Db::Query('SELECT character_id FROM `character` WHERE bracket_id = :bracketId ORDER BY character_id ASC', [ ':bracketId' => $bracketId ]);
                    if ($result && $result->count) {
                        $idx = 1;
                        while ($row = Lib\Db::Fetch($result)) {
                            $charId = (int) $row->character_id;
                            $path = IMAGE_LOCATION . '/' . base_convert($charId, 10, 36) . '.jpg';
                            $img = @imagecreatetruecolor(150, 150);
                            if ($img) {
                                $grey = imagecolorallocate($img, 128, 128, 128);
                                imagefill($img, 0, 0, $grey);
                                $white = imagecolorallocate($img, 255, 255, 255);
                                imagestring($img, 5, 60, 65, (string) $idx, $white);
                                @imagejpeg($img, $path);
                                imagedestroy($img);
                            }
                            $idx++;
                        }
                    }
                    Lib\Display::renderJson((object)[ 'success' => true, 'redirect' => '/me/?created&flushCache' ]);
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
            if (PHP_VERSION_ID >= 70300) {
                setcookie('dev_users_created', $value, [
                    'expires' => $expires,
                    'path' => '/',
                    'domain' => $domain,
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            } else {
                // Backward-compatible SameSite support for older PHP versions.
                setcookie('dev_users_created', $value, $expires, '/; samesite=Lax', $domain, false, true);
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