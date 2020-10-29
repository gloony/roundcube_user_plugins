CREATE TABLE `user_plugins` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `user_plugins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usrconf_userusrid` (`user_id`);

ALTER TABLE `user_plugins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_plugins`
  ADD CONSTRAINT `usrconf_userusrid` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;