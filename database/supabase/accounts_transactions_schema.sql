-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Creates the `accounts` and `transactions` tables (a simple ledger) plus a
-- derived-balance view/function. RLS is enabled with no policies, matching
-- every other table in this schema: the app only ever talks to Supabase
-- through the service_role key (server-side), which bypasses RLS entirely.
--
-- Design notes:
--   * `accounts` never stores a balance. Balance is always computed from
--     opening_balance + transactions, via account_balances (view) or
--     account_balance() (function) below — never write a balance column.
--   * `transactions` is an append-only ledger: rows can never be deleted
--     (enforced by trigger) and their financial fields can never be edited
--     once posted (enforced by trigger) — only `note` and `created_by` may
--     still be corrected after the fact. To reverse a transaction, post a
--     new one with the opposite direction and a note pointing back at it
--     (e.g. reference_type='manual', reference_id = <id being reversed>).
--   * Transfers are two ordinary rows, not a special construct: insert the
--     first leg, then insert the second leg with reference_type='transfer'
--     and reference_id = the first leg's id. To find a transfer's sibling
--     from either row: if reference_type='transfer', either read
--     reference_id directly (second leg → first leg), or look up
--     `where reference_type = 'transfer' and reference_id = <this row's id>`
--     (first leg → second leg).

create table if not exists accounts (
    id bigint generated always as identity primary key,
    name_en text not null unique,
    name_ar text not null,
    type text not null check (type in ('cash', 'bank', 'mobile_wallet')),
    opening_balance numeric(10,2) not null default 0,
    is_active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists transactions (
    id bigint generated always as identity primary key,
    account_id bigint not null references accounts(id) on delete restrict,
    direction text not null check (direction in ('in', 'out')),
    amount numeric(10,2) not null check (amount > 0),
    date date not null,
    category text not null check (category in ('payment_received', 'expense', 'transfer', 'adjustment')),
    reference_type text not null check (reference_type in ('payment', 'expense', 'rental', 'manual', 'transfer')),
    reference_id bigint,
    note text,
    created_by text not null,
    created_at timestamptz not null default now()
);

create index if not exists transactions_account_id_idx on transactions(account_id);
create index if not exists transactions_date_idx on transactions(date);
create index if not exists transactions_reference_idx on transactions(reference_type, reference_id);

-- Append-only: no row may ever be deleted, only reversed with a new entry.
create or replace function transactions_forbid_delete()
returns trigger as $$
begin
    raise exception 'transactions are append-only: post a reversing entry instead of deleting id %', old.id;
end;
$$ language plpgsql;

drop trigger if exists transactions_no_delete on transactions;
create trigger transactions_no_delete
    before delete on transactions
    for each row execute function transactions_forbid_delete();

-- Financial fields are locked once posted; only note/created_by may still
-- be corrected after the fact.
create or replace function transactions_forbid_financial_update()
returns trigger as $$
begin
    if new.account_id is distinct from old.account_id
        or new.direction is distinct from old.direction
        or new.amount is distinct from old.amount
        or new.date is distinct from old.date
        or new.category is distinct from old.category
        or new.reference_type is distinct from old.reference_type
        or new.reference_id is distinct from old.reference_id
    then
        raise exception 'transactions are append-only: financial fields on id % cannot be edited, post a reversing entry instead', old.id;
    end if;
    return new;
end;
$$ language plpgsql;

drop trigger if exists transactions_no_financial_update on transactions;
create trigger transactions_no_financial_update
    before update on transactions
    for each row execute function transactions_forbid_financial_update();

-- Derived balance — never a stored column. Both a per-account function
-- (optionally as-of a date) and a view listing every account's current
-- balance are provided; use whichever fits the call site.
create or replace function account_balance(p_account_id bigint, p_as_of date default null)
returns numeric as $$
    select a.opening_balance
        + coalesce(sum(case when t.direction = 'in' then t.amount else 0 end), 0)
        - coalesce(sum(case when t.direction = 'out' then t.amount else 0 end), 0)
    from accounts a
    left join transactions t
        on t.account_id = a.id
        and (p_as_of is null or t.date <= p_as_of)
    where a.id = p_account_id
    group by a.opening_balance;
$$ language sql stable;

create or replace view account_balances as
select
    a.id,
    a.name_en,
    a.name_ar,
    a.type,
    a.is_active,
    a.opening_balance,
    a.opening_balance
        + coalesce(sum(case when t.direction = 'in' then t.amount else 0 end), 0)
        - coalesce(sum(case when t.direction = 'out' then t.amount else 0 end), 0) as balance
from accounts a
left join transactions t on t.account_id = a.id
group by a.id, a.name_en, a.name_ar, a.type, a.is_active, a.opening_balance;

alter table accounts enable row level security;
alter table transactions enable row level security;
