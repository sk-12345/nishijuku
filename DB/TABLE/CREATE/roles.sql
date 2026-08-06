CREATE TABLE roles (
id INT(11) NOT NULL COMMENT '権限id', PRIMARY KEY (id),
display_order INT(5) NOT NULL COMMENT '表示順',
role_name VARCHAR(20) NOT NULL COMMENT '権限名',
description VARCHAR(255) COMMENT '権限説明',
append_datetime DATETIME NOT NULL COMMENT '作成日',
append_user_id VARCHAR(50) NOT NULL COMMENT '作成者',
append_func_id VARCHAR(50) NOT NULL COMMENT '作成画面',
update_datetime DATETIME NOT NULL COMMENT '更新日',
update_user_id VARCHAR(50) NOT NULL COMMENT '更新者',
update_func_id VARCHAR(50) NOT NULL COMMENT '更新画面',
lock_timestamp TIMESTAMP NOT NULL COMMENT '排他的制御'
)ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE=utf8mb4_general_ci COMMENT='権限';
