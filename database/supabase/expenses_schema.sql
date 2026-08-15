-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Creates the categories, vendors and expenses tables used by the admin
-- Expenses feature. RLS is enabled with no policies, matching the existing
-- `registrations` table: the app only ever talks to these tables through
-- the service_role key (server-side), which bypasses RLS entirely.

create table if not exists categories (
    id bigint generated always as identity primary key,
    name text not null unique,
    created_at timestamptz not null default now()
);

create table if not exists vendors (
    id bigint generated always as identity primary key,
    name text not null unique,
    created_at timestamptz not null default now()
);

create table if not exists expenses (
    id bigint generated always as identity primary key,
    category_id bigint not null references categories(id) on delete restrict,
    vendor_id bigint not null references vendors(id) on delete restrict,
    expense_date date not null,
    amount numeric(10,2) not null,
    payment_method text not null,
    description text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create index if not exists expenses_category_id_idx on expenses(category_id);
create index if not exists expenses_vendor_id_idx on expenses(vendor_id);
create index if not exists expenses_expense_date_idx on expenses(expense_date);

alter table categories enable row level security;
alter table vendors enable row level security;
alter table expenses enable row level security;
