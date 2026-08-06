CREATE TABLE game_images (
id INT(11) AUTO_INCREMENT NOT NULL COMMENT '画像ID', PRIMARY KEY (id),
game_id INT(11) NOT NULL COMMENT '試合ID',
description TEXT COMMENT '説明',
image_path VARCHAR(255) NOT NULL COMMENT '写真パス',
display_order INT(2) NOT NULL COMMENT '表示順',
append_datetime DATETIME NOT NULL COMMENT '作成日',
append_user_id VARCHAR(50) NOT NULL COMMENT '作成者',
append_func_id VARCHAR(50) NOT NULL COMMENT '作成画面',
update_datetime DATETIME NOT NULL COMMENT '更新日',
update_user_id VARCHAR(50) NOT NULL COMMENT '更新者',
update_func_id VARCHAR(50) NOT NULL COMMENT '更新画面',
lock_timestamp TIMESTAMP NOT NULL COMMENT '排他的制御'
)ENGINE=InnoDB ROW_FORMAT=DYNAMIC COLLATE=utf8mb4_general_ci COMMENT='試合写真';
