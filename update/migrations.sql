-- 2013-10-03
ALTER TABLE  `ip3_content_element` DROP  `dynamic_modules`;
ALTER TABLE  `ip3_content_element` ADD  `controllerAction` VARCHAR( 255 ) NOT NULL;
