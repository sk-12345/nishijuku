CREATE TABLE users (
id INT(11) AUTO_INCREMENT NOT NULL COMMENT 'ユーザID', PRIMARY KEY (id),
login_id VARCHAR(50) NOT NULL COMMENT 'ログインID',
password_hash VARCHAR(255) NOT NULL COMMENT 'パスワード',
name VARCHAR(50) NOT NULL COMMENT '名前',
role_id INT(11) NOT NULL COMMENT '権限ID',
append_datetime DATETIME NOT NULL COMMENT '作成日',
append_user_id VARCHAR(50) NOT NULL COMMENT '作成者',
append_func_id VARCHAR(50) NOT NULL COMMENT '作成画面',
update_datetime DATETIME NOT NULL COMMENT '更新日',
update_user_id VARCHAR(50) NOT NULL COMMENT '更新者',
update_func_id VARCHAR(50) NOT NULL COMMENT '更新画面',
lock_timestamp TIMESTAMP NOT NULL COMMENT '排他的制御'
)ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE=utf8mb4_general_ci COMMENT='ユーザ';
