-- 清理旧表，防止重复安装错误
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `question_categories`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `exam_answers`;

-- 管理员用户表
CREATE TABLE `admin_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `social_uid` VARCHAR(100) DEFAULT NULL COMMENT '社会化登录UID（如微信openid）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `idx_social_uid` (`social_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 默认管理员账号：admin / 123456（MD5）
INSERT INTO `admin_users` (`username`, `password`, `social_uid`) VALUES
('admin', 'e10adc3949ba59abbe56e057f20f883e', NULL);

-- 题库分类表
CREATE TABLE `question_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT '题库类别名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 题目表
CREATE TABLE `questions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(20) NOT NULL COMMENT '题型',
  `question` TEXT NOT NULL COMMENT '题目内容',
  `option_a` TEXT,
  `option_b` TEXT,
  `option_c` TEXT,
  `option_d` TEXT,
  `answer` VARCHAR(50) NOT NULL COMMENT '正确答案',
  `explanation` TEXT,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` INT(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 考试表
CREATE TABLE `exams` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) NOT NULL,
  `exam_data` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `is_finished` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '考试是否已完成，0未完成，1已完成',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 答题记录表
CREATE TABLE `exam_answers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `exam_id` INT(11) NOT NULL,
  `answers` LONGTEXT NOT NULL,
  `score` FLOAT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
