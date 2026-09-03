-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Creates `members` and `member_contributions`. RLS is enabled with no
-- policies, matching every other table: the app only ever talks to
-- Supabase through the service_role key, which bypasses RLS entirely.
--
-- No backfill needed — every existing expense was backfilled to
-- funding_type='account' in the previous migration, so there are currently
-- zero member-funded expenses and nothing for member_contributions to
-- reconcile against.
--
-- Design notes (confirmed before running):
--   * member_contributions is append-only, like transactions: no row may
--     ever be updated or deleted once inserted (enforced by trigger).
--   * Contributions for a member-funded expense are recorded as a single
--     atomic batch (one bulk INSERT covering every row at once, submitted
--     as one PostgREST request = one transaction), summing exactly to that
--     expense's paid_amount. That's what makes the hard equality check
--     below practical: a DEFERRED constraint trigger only evaluates at
--     commit time, so every row inserted together in the same statement is
--     already visible to each other by the time it fires.
--   * Once any contribution exists for an expense, that expense's
--     paid_amount/funding_type are locked (checked by a trigger on
--     `expenses` below) — mirrors the existing lock that already applies
--     once an expense has a linked Transaction.

create table if not exists members (
    id bigint generated always as identity primary key,
    name text not null,
    notes text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists member_contributions (
    id bigint generated always as identity primary key,
    member_id bigint not null references members(id) on delete restrict,
    expense_id bigint references expenses(id) on delete restrict,
    amount numeric(10,2) not null check (amount > 0),
    date date not null,
    note text,
    created_at timestamptz not null default now()
);

create index if not exists member_contributions_member_id_idx on member_contributions(member_id);
create index if not exists member_contributions_expense_id_idx on member_contributions(expense_id);

-- Append-only: no row may ever be changed or removed.
create or replace function member_contributions_forbid_delete()
returns trigger as $$
begin
    raise exception 'member_contributions are append-only: id % cannot be deleted', old.id;
end;
$$ language plpgsql;

drop trigger if exists member_contributions_no_delete on member_contributions;
create trigger member_contributions_no_delete
    before delete on member_contributions
    for each row execute function member_contributions_forbid_delete();

create or replace function member_contributions_forbid_update()
returns trigger as $$
begin
    raise exception 'member_contributions are append-only: id % cannot be edited', old.id;
end;
$$ language plpgsql;

drop trigger if exists member_contributions_no_update on member_contributions;
create trigger member_contributions_no_update
    before update on member_contributions
    for each row execute function member_contributions_forbid_update();

-- Hard invariant: for a member-funded expense, contributions linked to it
-- must sum to exactly its paid_amount. Deferred to transaction commit so a
-- multi-row batch insert (the only supported way to record contributions)
-- is evaluated as a whole rather than rejecting every row but the last.
create or replace function check_member_contribution_sum()
returns trigger as $$
declare
    v_expense_id bigint;
    v_paid_amount numeric;
    v_funding_type text;
    v_sum numeric;
begin
    v_expense_id := coalesce(new.expense_id, old.expense_id);

    if v_expense_id is null then
        return null;
    end if;

    select paid_amount, funding_type into v_paid_amount, v_funding_type
    from expenses where id = v_expense_id;

    if v_funding_type is distinct from 'member' then
        return null;
    end if;

    select coalesce(sum(amount), 0) into v_sum
    from member_contributions where expense_id = v_expense_id;

    if v_sum <> v_paid_amount then
        raise exception 'member_contributions for expense % sum to % but paid_amount is %', v_expense_id, v_sum, v_paid_amount;
    end if;

    return null;
end;
$$ language plpgsql;

drop trigger if exists member_contributions_sum_check on member_contributions;
create constraint trigger member_contributions_sum_check
    after insert or update or delete on member_contributions
    deferrable initially deferred
    for each row execute function check_member_contribution_sum();

-- Once an expense has recorded contributions, its paid_amount/funding_type
-- can no longer change out from under them (mirrors the existing lock that
-- applies once an expense has a linked Transaction).
create or replace function check_expense_member_funding_change()
returns trigger as $$
declare
    v_sum numeric;
begin
    select coalesce(sum(amount), 0) into v_sum
    from member_contributions where expense_id = old.id;

    if v_sum > 0 and (
        new.paid_amount is distinct from old.paid_amount
        or new.funding_type is distinct from old.funding_type
    ) then
        raise exception 'expense % has % in recorded member contributions; paid_amount/funding_type can no longer change', old.id, v_sum;
    end if;

    return new;
end;
$$ language plpgsql;

drop trigger if exists expenses_lock_after_member_contributions on expenses;
create trigger expenses_lock_after_member_contributions
    before update on expenses
    for each row execute function check_expense_member_funding_change();

alter table members enable row level security;
alter table member_contributions enable row level security;
