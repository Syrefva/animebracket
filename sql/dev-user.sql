-- Dev user for local testing (used with /user/dev-login when DEV_LOGIN is defined)
-- user_age > 0 required (0 = banned)
USE `anime_bracket`;
INSERT IGNORE INTO `users` (`user_name`, `user_admin`, `user_ip`, `user_age`) VALUES ('devadmin', 1, '127.0.0.1', 1);
