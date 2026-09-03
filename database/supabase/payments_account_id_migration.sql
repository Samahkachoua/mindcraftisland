-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Adds `account_id` to `payments`, backfills existing rows, then locks the
-- column to NOT NULL. Safe to run once; the seed/backfill block is written
-- to be idempotent (re-running it is a no-op) in case the script needs to
-- be re-run after a partial failure.
--
-- Backfill decision (confirmed before running): every payment recorded so
-- far used payment_method = 'Cash', so all 45 existing rows are backfilled
-- onto one seeded 'Cash' account. To preserve a full audit trail rather
-- than lumping history into opening_balance, one Transaction is also
-- backfilled per existing payment (direction=in, category=payment_received,
-- reference_type=payment, reference_id=payment.id), dated/attributed to
-- match the original payment. account_balances for 'Cash' will read
-- exactly SUM(payments.amount) once this finishes — nothing is folded into
-- opening_balance, which stays 0.

-- ── Step 1: add the column, nullable ───────────────────────────
alter table payments add column if not exists account_id bigint references accounts(id) on delete restrict;
create index if not exists payments_account_id_idx on payments(account_id);

-- ── Step 2/3: seed the Cash account, backfill payments + ledger ─
insert into accounts (name_en, name_ar, type, opening_balance, is_active)
select 'Cash', 'نقدي', 'cash', 0, true
where not exists (select 1 from accounts where name_en = 'Cash');

do $$
declare
    v_cash_id bigint;
begin
    select id into v_cash_id from accounts where name_en = 'Cash';

    update payments
    set account_id = v_cash_id
    where account_id is null;

    insert into transactions (account_id, direction, amount, date, category, reference_type, reference_id, note, created_by, created_at)
    select
        v_cash_id,
        'in',
        p.amount,
        p.payment_date,
        'payment_received',
        'payment',
        p.id,
        'Backfilled from pre-ledger payment history',
        p.created_by,
        p.created_at
    from payments p
    where not exists (
        select 1 from transactions t
        where t.reference_type = 'payment' and t.reference_id = p.id
    );
end $$;

-- ── Step 4: lock the column down (run only after Step 2/3 succeeded) ─
-- If this fails with a not-null violation, some row still has account_id
-- null — the update above should have covered every row, so re-check
-- `select id from payments where account_id is null` before retrying.
alter table payments alter column account_id set not null;
