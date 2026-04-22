USE bracu_freelance_marketplace;

-- Password for all demo users: password
-- Hash is a valid bcrypt string compatible with PHP password_verify().
INSERT INTO `User` (BRACU_ID, Bracu_mail, full_name, client, mobile_number, password, freelancer, preferred_mode, is_admin, credit_balance)
VALUES
('20101001', '20101001@g.bracu.ac.bd', 'Demo Admin', 1, '01700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'hiring', 1, 800.00),
('20101002', '20101002@g.bracu.ac.bd', 'Demo User 2', 1, '01700000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'working', 0, 300.00),
('20101003', '20101003@g.bracu.ac.bd', 'Demo User 3', 1, '01700000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'working', 0, 500.00)
ON DUPLICATE KEY UPDATE Bracu_mail = VALUES(Bracu_mail), full_name = VALUES(full_name), preferred_mode = VALUES(preferred_mode), is_admin = VALUES(is_admin);
