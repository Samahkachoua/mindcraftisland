-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Creates the programs, sessions and enrollments tables for the
-- Program/Session enrollment feature. RLS is enabled with no policies,
-- matching the existing `registrations` table: the app only ever talks
-- to these tables through the service_role key (server-side), which
-- bypasses RLS entirely.
--
-- Programs and Sessions are independent, unrelated enrollable offerings
-- (Sessions are standalone one-off items, not tied to any Program).
-- A participant (a row in `registrations`) enrolls in exactly one of the
-- two via `enrollments`, which snapshots the price at enrollment time so
-- later price changes on the program/session never retroactively change
-- what a participant owes.

-- Programs are a reusable recurring schedule template (weekdays + num_sessions),
-- not a fixed-term batch — there's no start_date/end_date here. Each Enrollment
-- carries its own personalized start_date and computed expiry_date instead (see
-- `enrollments` below), since participants can join a program at any time.
create table if not exists programs (
    id bigint generated always as identity primary key,
    name text not null,
    weekdays text[] not null,
    num_sessions integer not null,
    program_price numeric(10,2) not null,
    description text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists sessions (
    id bigint generated always as identity primary key,
    name text not null,
    price numeric(10,2) not null,
    description text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists enrollments (
    id bigint generated always as identity primary key,
    registration_id bigint not null references registrations(id) on delete restrict,
    enrollment_type text not null check (enrollment_type in ('program', 'session')),
    program_id bigint references programs(id) on delete restrict,
    session_id bigint references sessions(id) on delete restrict,
    price numeric(10,2) not null,
    -- Flat currency amount knocked off `price` for this participant (e.g. a
    -- sibling or early-bird discount). 0 means no discount. payment_status is
    -- derived from SUM(payments.amount) against (price - discount_amount), not
    -- against price alone.
    discount_amount numeric(10,2) not null default 0,
    -- start_date is required for both types: for 'program' it's the participant's
    -- personal start date (expiry_date computed once at creation time by walking
    -- the program's weekday pattern forward num_sessions matches, and stored —
    -- never recomputed live). For 'session' it's the specific date this
    -- participant is attending — admin-chosen per enrollment (Sessions carry no
    -- date of their own, so the same Session can be attended on more than one
    -- occasion). expiry_date stays null for 'session' — a one-off attendance
    -- doesn't expire.
    start_date date,
    expiry_date date,
    -- Derived server-side from SUM(payments.amount) vs (price - discount_amount)
    -- every time a payment is recorded or removed (see `payments` below) —
    -- never written directly by an admin.
    payment_status text not null default 'unpaid' check (payment_status in ('unpaid', 'partial', 'paid')),
    created_at timestamptz not null default now(),
    constraint enrollments_target_matches_type check (
        (enrollment_type = 'program' and program_id is not null and session_id is null)
        or
        (enrollment_type = 'session' and session_id is not null and program_id is null)
    ),
    constraint enrollments_dates_required check (
        (enrollment_type = 'program' and start_date is not null and expiry_date is not null)
        or
        (enrollment_type = 'session' and start_date is not null and expiry_date is null)
    ),
    constraint enrollments_discount_not_exceeding_price check (
        discount_amount >= 0 and discount_amount <= price
    ),
    -- A participant can't be double-booked into the same program, or the same
    -- session, starting/occurring on the same day — but can enroll again for a
    -- different date (e.g. re-upping a program after expiry, or attending the
    -- same recurring session on another occasion). NULLs (the other type's
    -- unused program_id/session_id) don't collide under a unique constraint,
    -- so these only ever constrain rows of their own type.
    constraint enrollments_unique_program_start unique (registration_id, program_id, start_date),
    constraint enrollments_unique_session_date unique (registration_id, session_id, start_date)
);

create index if not exists enrollments_registration_id_idx on enrollments(registration_id);
create index if not exists enrollments_program_id_idx on enrollments(program_id);
create index if not exists enrollments_session_id_idx on enrollments(session_id);

-- A single Enrollment can have multiple Payments over time (e.g. deposit + balance).
-- Recording or removing a Payment recomputes its Enrollment's payment_status.
create table if not exists payments (
    id bigint generated always as identity primary key,
    enrollment_id bigint not null references enrollments(id) on delete restrict,
    amount numeric(10,2) not null,
    payment_date date not null,
    payment_method text not null,
    notes text,
    created_by text not null,
    created_at timestamptz not null default now()
);

create index if not exists payments_enrollment_id_idx on payments(enrollment_id);
create index if not exists payments_payment_date_idx on payments(payment_date);

alter table programs enable row level security;
alter table sessions enable row level security;
alter table enrollments enable row level security;
alter table payments enable row level security;
