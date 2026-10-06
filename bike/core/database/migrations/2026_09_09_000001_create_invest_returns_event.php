<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stored procedure: processa investimentos vencidos (rendimento 24h em 24h)
        $procedure = <<<'SQL'
CREATE PROCEDURE process_invest_returns()
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_invest_id BIGINT;
    DECLARE v_user_id BIGINT;
    DECLARE v_interest DECIMAL(28,8);
    DECLARE v_period INT;
    DECLARE v_capital_status TINYINT;
    DECLARE v_amount DECIMAL(28,8);
    DECLARE v_plan_id BIGINT;
    DECLARE v_plan_time INT;
    DECLARE v_plan_name VARCHAR(191);
    DECLARE v_cur_text VARCHAR(20);
    DECLARE v_trx VARCHAR(40);
    DECLARE v_balance DECIMAL(28,8);
    DECLARE v_now DATETIME;

    DECLARE cur CURSOR FOR
        SELECT i.id, i.user_id, i.interest, i.period, i.capital_status, i.amount, i.plan_id
        FROM invests i
        WHERE i.status = 1 AND i.next_time <= v_now
        ORDER BY i.last_time ASC
        LIMIT 500;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SET v_now = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 3 HOUR);

    SET v_cur_text = (SELECT COALESCE(cur_text, 'R$') FROM general_settings ORDER BY id LIMIT 1);

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_invest_id, v_user_id, v_interest, v_period, v_capital_status, v_amount, v_plan_id;
        IF v_done THEN
            LEAVE read_loop;
        END IF;

        SET v_plan_time = COALESCE((SELECT `time` FROM plans WHERE id = v_plan_id), 24);
        SET v_plan_name = (SELECT name FROM plans WHERE id = v_plan_id);
        SET v_trx = CONCAT('INT', DATE_FORMAT(v_now, '%Y%m%d%H%i%s'), LPAD(v_invest_id, 6, '0'));

        UPDATE invests
        SET return_rec_time = return_rec_time + 1,
            paid = paid + v_interest,
            should_pay = IF(v_period > 0, should_pay - v_interest, should_pay),
            next_time = DATE_ADD(next_time, INTERVAL v_plan_time HOUR),
            last_time = v_now
        WHERE id = v_invest_id AND status = 1 AND next_time <= v_now;

        IF ROW_COUNT() = 0 THEN
            ITERATE read_loop;
        END IF;

        UPDATE users SET interest_wallet = interest_wallet + v_interest WHERE id = v_user_id;
        SELECT interest_wallet INTO v_balance FROM users WHERE id = v_user_id;

        INSERT INTO transactions
            (user_id, amount, charge, post_balance, trx_type, trx, details, remark, wallet_type, created_at, updated_at)
        VALUES
            (v_user_id, v_interest, 0, v_balance, '+', v_trx,
             CONCAT(TRIM(v_interest)+0, ' ', v_cur_text, ' Rendimento de ', COALESCE(v_plan_name, 'plano')), 'interest', 'interest_wallet', v_now, v_now);

        IF v_period != -1 AND (SELECT return_rec_time FROM invests WHERE id = v_invest_id) >= v_period THEN
            UPDATE invests SET status = 0 WHERE id = v_invest_id;
            IF v_capital_status = 1 THEN
                UPDATE users SET interest_wallet = interest_wallet + v_amount WHERE id = v_user_id;
                SELECT interest_wallet INTO v_balance FROM users WHERE id = v_user_id;
                INSERT INTO transactions
                    (user_id, amount, charge, post_balance, trx_type, trx, details, remark, wallet_type, created_at, updated_at)
                VALUES
                    (v_user_id, v_amount, 0, v_balance, '+', CONCAT(v_trx, 'C'),
                     CONCAT(TRIM(v_amount)+0, ' ', v_cur_text, ' Retorno de capital de ', COALESCE(v_plan_name, 'plano')), 'capital_return', 'interest_wallet', v_now, v_now);
            END IF;
        END IF;
    END LOOP;
    CLOSE cur;
END
SQL;

        DB::statement('DROP PROCEDURE IF EXISTS process_invest_returns');
        DB::statement($procedure);

        // Evento: processa a cada 5 minutos, independente de Vercel/cron PHP
        DB::statement('DROP EVENT IF EXISTS ev_process_invest_returns');
        DB::statement('CREATE EVENT ev_process_invest_returns ON SCHEDULE EVERY 5 MINUTE STARTS CURRENT_TIMESTAMP ON COMPLETION PRESERVE ENABLE DO CALL process_invest_returns()');
    }

    public function down(): void
    {
        DB::statement('DROP EVENT IF EXISTS ev_process_invest_returns');
        DB::statement('DROP PROCEDURE IF EXISTS process_invest_returns');
    }
};