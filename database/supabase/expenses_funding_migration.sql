-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Adds funding tracking to `expenses`, backfills existing rows, then locks
-- the required columns down. Safe to run once; the backfill block is
-- idempotent (re-running it is a no-op) in case of a partial failure.
--
-- Backfill decision (confirmed before running): all 32 existing expenses
-- were paid in Cash, so they're backfilled onto the same seeded 'Cash'
-- account used for payments, with paid_amount = amount (full audit trail,
-- same pattern as the payments migration) and one backfilled Transaction
-- per row (direction=out, category=expense, reference_type=expense,
-- reference_id=expense.id). This will drop Cash's derived balance from
-- $1,276.00 to -$1,228.50 — expenses were never tracked as cash outflows
-- before, so this just makes that shortfall visible for the first time.
--
-- paid_amount is kept equal to amount by a CHECK constraint for now — no
-- partial-expense-payment/split-funding logic exists yet. The column
-- exists as forward-compatible infrastructure; a future chunk introducing
-- split account/member funding will need to relax or replace this check.

-- ── Step 1: add the columns, nullable ───────────────────────────
alter table expenses add column if not exists paid_amount numeric(10,2);
alter table expenses add column if not exists funding_type text;
alter table expenses add column if not exists funding_account_id bigint references accounts(id) on delete restrict;

create index if not exists expenses_funding_account_id_idx on expenses(funding_account_id);

-- ── Step 2/3: backfill existing rows + ledger ───────────────────
do $$
declare
    v_cash_id bigint;
begin
    select id into v_cash_id from accounts where name_en = 'Cash';

    update expenses
    set paid_amount = amount,
        funding_type = 'account',
        funding_account_id = v_cash_id
    where paid_amount is null;

    insert into transactions (account_id, direction, amount, date, category, reference_type, reference_id, note, created_by, created_at)
    select
        v_cash_id,
        'out',
        e.amount,
        e.expense_date,
        'expense',
        'expense',
        e.id,
        'Backfilled from pre-ledger expense history',
        'admin',
        e.created_at
    from expenses e
    where not exists (
        select 1 from transactions t
        where t.reference_type = 'expense' and t.reference_id = e.id
    );
end $$;

-- ── Step 4: lock the columns down (run only after Step 2/3 succeeded) ─
alter table expenses alter column paid_amount set not null;
alter table expenses alter column funding_type set not null;

alter table expenses drop constraint if exists expenses_funding_type_check;
alter table expenses add constraint expenses_funding_type_check
    check (funding_type in ('account', 'member'));

alter table expenses drop constraint if exists expenses_funding_account_matches_type;
alter table expenses add constraint expenses_funding_account_matches_type check (
    (funding_type = 'account' and funding_account_id is not null)
    or
    (funding_type = 'member' and funding_account_id is null)
);

alter table expenses drop constraint if exists expenses_paid_amount_matches_amount;
alter table expenses add constraint expenses_paid_amount_matches_amount
    check (paid_amount = amount);
