CREATE TABLE roles (
id INT(11) AUTO_INCREMENT NOT NULL COMMENT 'ユーザID', PRIMARY KEY (id),
system_flg INT(1) DEFAULT 0 NOT NULL COMMENT 'システム管理フラグ',
create_account_flg INT(1) DEFAULT 0 NOT NULL COMMENT '新規アカウント作成フラグ',
account_flg INT(2) DEFAULT 0 NOT NULL COMMENT 'アカウント管理フラグ',
update_confirmation_flg INT(3) DEFAULT 0 NOT NULL COMMENT '更新履歴フラグ',
practice_flg INT(4) DEFAULT 0 NOT NULL COMMENT '練習風景・投稿フラグ',
game_flg INT(5) DEFAULT 0 NOT NULL COMMENT '試合・投稿フラグ',
event_flg INT(6) DEFAULT 0 NOT NULL COMMENT 'イベント一覧・投稿フラグ',
append_user_id VARCHAR(50) NOT NULL COMMENT '作成者',
append_func_id VARCHAR(50) NOT NULL COMMENT '作成画面',
update_datetime DATETIME NOT NULL COMMENT '更新日',
update_user_id VARCHAR(50) NOT NULL COMMENT '更新者',
update_func_id VARCHAR(50) NOT NULL COMMENT '更新画面',
lock_timestamp TIMESTAMP NOT NULL COMMENT '排他的制御'
)ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE=utf8mb4_general_ci COMMENT='権限';
