-- Run this in the Supabase SQL Editor to bring the live `programs`, `sessions`
-- and `enrollments` tables in sync with the current application code.
--
-- Why this file exists: the original schema file only ever used
-- `CREATE TABLE IF NOT EXISTS`, which is a no-op once a table already exists.
-- Several schema changes were made across development (removing Program's
-- start_date/end_date, collapsing its bilingual description into one field,
-- removing Session's session_datetime, replacing Enrollment.amount_paid with
-- the `payments` table, adding Enrollment.start_date/expiry_date, adding
-- Enrollment.discount_amount, preventing duplicate enrollments, requiring
-- Enrollment.start_date for sessions too, adding then removing Session's own
-- session_date) but were never actually applied to the live database, since
-- the tables already existed from an earlier run. This script applies exactly
-- those changes.
--
-- Every statement here is idempotent — safe to run even if some of these
-- changes already happened to be applied.

-- ── programs ────────────────────────────────────────────────
-- No longer a fixed-term batch: drop start_date/end_date. Description
-- collapses from english_description/arabic_description into one field.
alter table programs drop column if exists start_date;
alter table programs drop column if exists end_date;

do $$
begin
    if exists (
        select 1 from information_schema.columns
        where table_name = 'programs' and column_name = 'english_description'
    ) and not exists (
        select 1 from information_schema.columns
        where table_name = 'programs' and column_name = 'description'
    ) then
        alter table programs rename column english_description to description;
    end if;
end $$;

alter table programs add column if not exists description text;
alter table programs drop column if exists arabic_description;

-- ── sessions ────────────────────────────────────────────────
-- No date on Sessions at all — a session-type Enrollment carries its own
-- start_date instead (see below), chosen per participant.
alter table sessions drop column if exists session_datetime;
alter table sessions drop column if exists session_date;

-- Description collapses from english_description/arabic_description into
-- one field, same as programs above.
do $$
begin
    if exists (
        select 1 from information_schema.columns
        where table_name = 'sessions' and column_name = 'english_description'
    ) and not exists (
        select 1 from information_schema.columns
        where table_name = 'sessions' and column_name = 'description'
    ) then
        alter table sessions rename column english_description to description;
    end if;
end $$;

alter table sessions add column if not exists description text;
alter table sessions drop column if exists arabic_description;

-- ── enrollments ─────────────────────────────────────────────
-- amount_paid is replaced entirely by the `payments` table (multiple
-- payments per enrollment). start_date/expiry_date are new, only populated
-- for enrollment_type = 'program'.
alter table enrollments drop column if exists amount_paid;
alter table enrollments add column if not exists start_date date;
alter table enrollments add column if not exists expiry_date date;
alter table enrollments alter column payment_status set default 'unpaid';

alter table enrollments drop constraint if exists enrollments_payment_status_check;
alter table enrollments add constraint enrollments_payment_status_check
    check (payment_status in ('unpaid', 'partial', 'paid'));

-- start_date is now required for session-type enrollments too (the specific
-- date this participant is attending, pre-filled from but independent of the
-- Session's own default session_date) — previously it had to stay null there.
alter table enrollments drop constraint if exists enrollments_program_dates_required;
alter table enrollments drop constraint if exists enrollments_dates_required;
alter table enrollments add constraint enrollments_dates_required check (
    (enrollment_type = 'program' and start_date is not null and expiry_date is not null)
    or
    (enrollment_type = 'session' and start_date is not null and expiry_date is null)
);

-- Flat currency discount per enrollment; payment_status now derives from
-- SUM(payments.amount) against (price - discount_amount).
alter table enrollments add column if not exists discount_amount numeric(10,2) not null default 0;

alter table enrollments drop constraint if exists enrollments_discount_not_exceeding_price;
alter table enrollments add constraint enrollments_discount_not_exceeding_price check (
    discount_amount >= 0 and discount_amount <= price
);

-- A participant can't be double-booked into the same program, or the same
-- session, starting/occurring on the same day — but can enroll again for a
-- different date (re-upping a program after expiry, or attending the same
-- recurring session on another occasion).
alter table enrollments drop constraint if exists enrollments_unique_program_start;
alter table enrollments add constraint enrollments_unique_program_start
    unique (registration_id, program_id, start_date);

alter table enrollments drop constraint if exists enrollments_unique_session;
alter table enrollments drop constraint if exists enrollments_unique_session_date;
alter table enrollments add constraint enrollments_unique_session_date
    unique (registration_id, session_id, start_date);
