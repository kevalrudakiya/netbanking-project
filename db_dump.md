## 11. Real Database Entries (Current State)

### `users` Real Data
| user_id | full_name | email | phone | username | password | status | last_login | created_at | updated_at |
|---|---|---|---|---|---|---|---|---|---|
| 1 | keval | keval@gmail.com | 1234567890 | keval123 | $2y$10$L5dudU.M1EZCqVrVJDrFQ.tSafYidOh5bL8JypqLYH9QkYOOJlKIS | active | NULL | 2026-06-23 16:12:56 | 2026-07-29 08:55:55 |
| 4 | pathyo bagda | pathyo6@gmail.com | 9023123771 | pathyo123 | $2y$10$8Ir10UmWuLbA55LcTG08/.KtvEqKjLx4M2G7/z1W9zHqW2pjVcS8G | active | NULL | 2026-07-01 11:12:35 | 2026-07-29 08:55:55 |
| 5 | maulik | maulik1234@gmail.com | 1234567891 | maulik | $2y$10$qhhdLgWHkt9g745QWaDONeUhVQXVQb5H8v/DtRRklSgA6kPmn3TZC | active | NULL | 2026-07-05 18:13:08 | 2026-07-29 08:55:55 |

### `accounts` Real Data
| account_id | user_id | account_number | balance | pin | status | account_type | created_at | updated_at |
|---|---|---|---|---|---|---|---|---|
| 1 | 1 | 1008481208 | 9999999999999.99 | $2y$10$VbyKeD6bANNSu9Q4YRop7eP5zkIeE1IBNhBdW39ZnHhj4dCIqe5tu | active | savings | 2026-06-23 16:12:56 | 2026-08-03 10:13:42 |
| 2 | 4 | 1004525426 | 4994400.00 | $2y$10$EBa7LOgCmJeepRh30//a3uojPFXeXeW6DYghrlX0cTTrgCW/.PFV6 | active | savings | 2026-07-01 11:12:35 | 2026-07-29 08:55:55 |
| 3 | 5 | 1008780990 | 9999999900.00 | $2y$10$BChD1uFrCgERhPwFXiDEP.XXat9ytsv.SWIeRS19kPAYNfwJIcBw2 | active | savings | 2026-07-05 18:13:08 | 2026-07-29 08:55:55 |

### `transactions` Real Data
| transaction_id | account_id | type | amount | balance_after | reference_no | note | related_account_id | created_at |
|---|---|---|---|---|---|---|---|---|
| 1 | 1 | deposit | 50000.00 | 50000.00 | TXN202606249B435F | keval123 | NULL | 2026-06-23 16:19:37 |
| 2 | 1 | withdraw | 45000.00 | 5000.00 | TXN20260624008C79 | keval123 | NULL | 2026-06-23 16:20:00 |
| 3 | 1 | deposit | 100000000.00 | 100005000.00 | TXN202606255B238C | superadmin | NULL | 2026-06-24 16:36:53 |
| 4 | 1 | withdraw | 12124.00 | 99992876.00 | TXN202606257D09AF | superadmin | NULL | 2026-06-24 16:37:59 |
| 5 | 1 | withdraw | 12220000.00 | 87772876.00 | TXN202607018C0C0C | keval123 | NULL | 2026-07-01 10:45:12 |
| 6 | 1 | deposit | 5000000000.00 | 5087772876.00 | TXN202607016085BD |  | NULL | 2026-07-01 10:45:42 |
| 7 | 2 | deposit | 5000000.00 | 5000000.00 | TXN2026070182FFBF |  | NULL | 2026-07-01 11:14:00 |
| 8 | 2 | withdraw | 55599.00 | 4944401.00 | TXN202607013659B1 |  | NULL | 2026-07-01 11:14:27 |
| 9 | 3 | deposit | 10000000000.00 | 10000000000.00 | TXN20260706BBE2B0 |  | NULL | 2026-07-05 18:24:43 |
| 10 | 3 | withdraw | 100.00 | 9999999900.00 | TXN2026070696F940 |  | NULL | 2026-07-05 18:25:45 |
| 11 | 1 | transfer_out | 99999.00 | 5087672877.00 | TXN202607065BF97E | loda | NULL | 2026-07-06 09:46:29 |
| 12 | 2 | transfer_in | 99999.00 | 5044400.00 | TXN202607065C1417 | loda | NULL | 2026-07-06 09:46:29 |
| 13 | 2 | withdraw | 50000.00 | 4994400.00 | TXN20260706910B14 |  | NULL | 2026-07-06 09:48:41 |
| 14 | 1 | deposit | 9999999999999.99 | 9999999999999.99 | TXN202608036F3AEB |  | NULL | 2026-08-03 10:13:43 |

### `admins` Real Data
| admin_id | username | password | full_name | role | last_login | created_at | updated_at |
|---|---|---|---|---|---|---|---|
| 1 | superadmin | $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi | Super Admin | superadmin | NULL | 2026-06-23 15:59:25 | 2026-07-29 08:55:55 |

### `login_attempts` Real Data
| id | username | ip_address | attempted_at |
|---|---|---|---|
| 51 | 1234567890 | ::1 | 2026-06-24 15:56:07 |
| 9 | keval | ::1 | 2026-06-23 16:25:46 |
| 10 | keval | ::1 | 2026-06-23 16:25:54 |
| 11 | keval | ::1 | 2026-06-23 16:29:43 |
| 13 | keval | ::1 | 2026-06-23 16:29:58 |
| 34 | keval | ::1 | 2026-06-23 19:41:46 |
| 35 | keval | ::1 | 2026-06-23 19:41:52 |
| 36 | keval | ::1 | 2026-06-23 19:42:17 |
| 1 | keval123 | ::1 | 2026-06-23 16:24:10 |
| 2 | keval123 | ::1 | 2026-06-23 16:24:20 |
| 3 | keval123 | ::1 | 2026-06-23 16:25:07 |
| 4 | keval123 | ::1 | 2026-06-23 16:25:12 |
| 5 | keval123 | ::1 | 2026-06-23 16:25:13 |
| 6 | keval123 | ::1 | 2026-06-23 16:25:21 |
| 7 | keval123 | ::1 | 2026-06-23 16:25:27 |
| 8 | keval123 | ::1 | 2026-06-23 16:25:32 |
| 12 | keval123 | ::1 | 2026-06-23 16:29:51 |
| 14 | keval123 | ::1 | 2026-06-23 17:08:28 |
| 15 | keval123 | ::1 | 2026-06-23 17:08:42 |
| 16 | keval123 | ::1 | 2026-06-23 17:13:24 |
| 17 | keval123 | ::1 | 2026-06-23 17:15:51 |
| 18 | keval123 | ::1 | 2026-06-23 17:24:01 |
| 19 | keval123 | ::1 | 2026-06-23 17:24:03 |
| 20 | keval123 | ::1 | 2026-06-23 17:24:03 |
| 21 | keval123 | ::1 | 2026-06-23 17:24:04 |
| 22 | keval123 | ::1 | 2026-06-23 17:24:04 |
| 23 | keval123 | ::1 | 2026-06-23 17:24:04 |
| 24 | keval123 | ::1 | 2026-06-23 17:24:04 |
| 25 | keval123 | ::1 | 2026-06-23 17:24:04 |
| 26 | keval123 | ::1 | 2026-06-23 17:24:05 |
| 27 | keval123 | ::1 | 2026-06-23 17:24:05 |
| 28 | keval123 | ::1 | 2026-06-23 17:24:05 |
| 29 | keval123 | ::1 | 2026-06-23 17:24:05 |
| 30 | keval123 | ::1 | 2026-06-23 17:24:05 |
| 31 | keval123 | ::1 | 2026-06-23 17:36:34 |
| 32 | keval123 | ::1 | 2026-06-23 18:20:54 |
| 33 | keval123 | ::1 | 2026-06-23 19:41:33 |
| 37 | keval123 | ::1 | 2026-06-23 19:42:25 |
| 38 | keval123 | ::1 | 2026-06-23 19:42:33 |
| 39 | keval123 | ::1 | 2026-06-23 19:42:41 |
| 40 | keval123 | ::1 | 2026-06-23 19:45:44 |
| 41 | keval123 | ::1 | 2026-06-23 19:58:52 |
| 42 | keval123 | ::1 | 2026-06-23 19:58:55 |
| 43 | keval123 | ::1 | 2026-06-23 19:58:55 |
| 44 | keval123 | ::1 | 2026-06-23 19:58:55 |
| 45 | keval123 | ::1 | 2026-06-23 19:58:56 |
| 46 | keval123 | ::1 | 2026-06-23 19:58:56 |
| 47 | keval123 | ::1 | 2026-06-23 20:01:14 |
| 48 | keval123 | ::1 | 2026-06-24 15:54:17 |
| 49 | keval123 | ::1 | 2026-06-24 15:54:35 |
| 50 | keval123 | ::1 | 2026-06-24 15:54:59 |
| 52 | keval123 | ::1 | 2026-06-24 15:59:55 |
| 53 | keval123 | ::1 | 2026-06-24 16:09:49 |
| 56 | keval123 | ::1 | 2026-06-24 16:20:36 |
| 61 | keval123 | ::1 | 2026-07-29 08:53:44 |
| 62 | keval123 | ::1 | 2026-08-10 08:54:53 |
| 63 | keval123 | ::1 | 2026-08-10 10:01:49 |
| 64 | keval123 | ::1 | 2026-08-10 14:29:41 |
| 59 | maulik | ::1 | 2026-07-05 18:13:29 |
| 57 | parthyo123 | ::1 | 2026-07-01 11:12:57 |
| 58 | parthyo123 | ::1 | 2026-07-01 11:13:30 |
| 60 | pathyo123 | ::1 | 2026-07-06 09:47:45 |
| 54 | superadmin | ::1 | 2026-06-24 16:09:50 |
| 55 | superadmin | ::1 | 2026-06-24 16:20:24 |

### `password_reset_requests` Real Data
| request_id | user_id | account_number | phone | status | admin_id | temporary_password | created_at | processed_at | new_password |
|---|---|---|---|---|---|---|---|---|---|
| 1 | 1 | 1008481208 | 1234567890 | rejected | 1 | NULL | 2026-06-24 15:33:45 | NULL | NULL |
| 2 | 1 | 1008481208 | 1234567890 | approved | 1 | 420952 | 2026-06-24 15:44:43 | NULL | NULL |
| 3 | 1 | 1008481208 | 1234567890 | approved | 1 | 838421 | 2026-06-24 16:12:23 | 2026-06-24 16:12:34 | NULL |
| 4 | 1 | 1008481208 | 1234567890 | approved | NULL | NULL | 2026-06-24 16:32:38 | NULL | NULL |
| 5 | 1 | 1008481208 | 1234567890 | approved | NULL | NULL | 2026-07-06 09:49:36 | NULL | NULL |

### `audit_logs` Real Data
| id | admin_id | action | created_at |
|---|---|---|---|
| *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* |

### `beneficiaries` Real Data
| beneficiary_id | user_id | account_number | beneficiary_name | nickname | created_at |
|---|---|---|---|---|---|
| *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* |

### `notifications` Real Data
| id | user_id | title | message | is_read | created_at |
|---|---|---|---|---|---|
| *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* | *(Empty)* |

