-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Widens the ledger's append-only carve-out so ANY expense's linked
-- Transaction(s) can be deleted, not just a member-funded expense's (which
-- never had one anyway). Matches the same treatment already given to
-- payments in payments_delete_migration.sql.
--
-- Confirmed before running: further weakens transactions_no_delete
-- ("Transactions are never deleted, only reversed with a new entry").
-- Previously an expense's transaction was deletable only when
-- funding_type='member' (which has no transaction to begin with, so that
-- branch was already a no-op) — account-funded expenses with a real ledger
-- entry were still blocked, which is the case this migration removes.
-- Deleting an account-funded expense now deletes its Transaction(s) —
-- including any amount-correction adjustments posted by
-- ExpenseController::update() — then the expense itself, leaving no
-- reversing entry behind for that spend.
--
-- Deletion order enforced by the app (ExpenseController::destroy):
--   1. delete every Transaction referencing the expense (original +
--      any adjustments)
--   2. delete the Expense

create or replace function transactions_forbid_delete()
returns trigger as $$
begin
    if old.reference_type = 'payment' then
        return old;
    end if;

    if old.reference_type = 'expense' then
        return old;
    end if;

    raise exception 'transactions are append-only: post a reversing entry instead of deleting id %', old.id;
end;
$$ language plpgsql;
