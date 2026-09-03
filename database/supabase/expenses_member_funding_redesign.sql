-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Redesigns member-funding on expenses and converts all 32 existing
-- expenses from account-funded to member-funded, deleting their linked
-- Transactions in the process.
--
-- Confirmed before running:
--   * Replaces the member_contributions split-funding model (never used —
--     0 rows existed) with a single funding_member_id column, symmetric
--     with the existing funding_account_id: funding_type='account' means
--     funding_account_id is set; funding_type='member' means
--     funding_member_id is set. Only ever one or the other.
--   * All 32 existing expenses are reattributed to member "Sabouha Lamis"
--     (id=1, your only existing member).
--   * Their 32 linked Transactions are deleted. This is a deliberate,
--     narrow extension of the same append-only carve-out built for
--     Rentals: a transaction becomes deletable when its expense has been
--     converted to funding_type='member'. Every other transaction in the
--     app (enrollment payments, rental payments, transfers, adjustments)
--     stays exactly as append-only as before.
--   * Effect on Main Cash $: balance moves from -$1,178.50 to roughly
--     +$1,326.00 the moment this runs. Permanent, not reversible by
--     re-running this script.
--   * member_contributions and its now-unused triggers/functions are
--     dropped — safe, since it never held real data.

-- ── Step 1: add funding_member_id, symmetric with funding_account_id ────
alter table expenses add column if not exists funding_member_id bigint references members(id) on delete restrict;
create index if not exists expenses_funding_member_id_idx on expenses(funding_member_id);

-- ── Step 2: convert all 32 existing expenses to member-funded ───────────
update expenses
set funding_type = 'member',
    funding_member_id = 1,
    funding_account_id = null
where funding_type = 'account';

-- ── Step 3: replace the funding-matches-type constraint to cover both ───
alter table expenses drop constraint if exists expenses_funding_account_matches_type;
alter table expenses add constraint expenses_funding_matches_type check (
    (funding_type = 'account' and funding_account_id is not null and funding_member_id is null)
    or
    (funding_type = 'member' and funding_member_id is not null and funding_account_id is null)
);

-- ── Step 4: extend the ledger's append-only carve-out to expenses ───────
-- (the rental case from rentals_delete_migration.sql is preserved as-is)
create or replace function transactions_forbid_delete()
returns trigger as $$
declare
    v_deletable boolean;
begin
    if old.reference_type = 'payment' then
        select exists(
            select 1 from rentals where payment_id = old.reference_id
        ) into v_deletable;

        if v_deletable then
            return old;
        end if;
    end if;

    if old.reference_type = 'expense' then
        select exists(
            select 1 from expenses where id = old.reference_id and funding_type = 'member'
        ) into v_deletable;

        if v_deletable then
            return old;
        end if;
    end if;

    raise exception 'transactions are append-only: post a reversing entry instead of deleting id %', old.id;
end;
$$ language plpgsql;

-- ── Step 5: delete the now-permitted expense Transactions ───────────────
delete from transactions
where reference_type = 'expense'
    and reference_id in (select id from expenses where funding_type = 'member');

-- ── Step 6: retire member_contributions (never held real data) ──────────
drop trigger if exists expenses_lock_after_member_contributions on expenses;
drop function if exists check_expense_member_funding_change();
drop trigger if exists member_contributions_sum_check on member_contributions;
drop function if exists check_member_contribution_sum();
drop trigger if exists member_contributions_no_delete on member_contributions;
drop function if exists member_contributions_forbid_delete();
drop trigger if exists member_contributions_no_update on member_contributions;
drop function if exists member_contributions_forbid_update();
drop table if exists member_contributions;
