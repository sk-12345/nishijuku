CREATE TABLE role_transfer_logs (
id INT(11) AUTO_INCREMENT NOT NULL COMMENT 'ログID', PRIMARY KEY (id),
from_user_id INT(11) NOT NULL COMMENT '権限移行元ユーザID', INDEX (from_user_id),
to_user_id INT(11) NOT NULL COMMENT '権限移行先ユーザID', INDEX (to_user_id),
description VARCHAR(5000) COMMENT '移行理由',
created_at DATE NOT NULL COMMENT '移行日時',
append_datetime DATETIME NOT NULL COMMENT '作成日',
append_user_id VARCHAR(50) NOT NULL COMMENT '作成者',
append_func_id VARCHAR(50) NOT NULL COMMENT '作成画面',
update_datetime DATETIME NOT NULL COMMENT '更新日',
update_user_id VARCHAR(50) NOT NULL COMMENT '更新者',
update_func_id VARCHAR(50) NOT NULL COMMENT '更新画面',
lock_timestamp TIMESTAMP NOT NULL COMMENT '排他的制御'
)ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE=utf8mb4_general_ci COMMENT='ユーザー権限移行ログ';
