INSERT INTO nishijuku.roles (
    id, system_flg, create_account_flg, account_flg,
    update_confirmation_flg, practice_flg, game_flg, event_flg,
    append_user_id, append_func_id, update_datetime,
    update_user_id, update_func_id, lock_timestamp
) VALUES
    (1, 1, 1, 1, 1, 1, 1, 1, 'Batch', 'Batch', NOW(), 'Batch', 'Batch', CURRENT_TIMESTAMP),
    (2, 0, 1, 1, 1, 1, 1, 1, 'Batch', 'Batch', NOW(), 'Batch', 'Batch', CURRENT_TIMESTAMP),
    (3, 0, 0, 0, 0, 1, 1, 1, 'Batch', 'Batch', NOW(), 'Batch', 'Batch', CURRENT_TIMESTAMP),
    (4, 0, 0, 0, 0, 0, 0, 0, 'Batch', 'Batch', NOW(), 'Batch', 'Batch', CURRENT_TIMESTAMP),
    (5, 0, 0, 0, 0, 1, 1, 1, 'Batch', 'Batch', NOW(), 'Batch', 'Batch', CURRENT_TIMESTAMP);
