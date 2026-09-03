-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Creates `rental_items` and `rentals`. RLS is enabled with no policies,
-- matching every other table: the app only ever talks to Supabase through
-- the service_role key, which bypasses RLS entirely.
--
-- Prerequisite fix (confirmed before running): payments.enrollment_id was
-- NOT NULL, which meant no Payment could ever exist without an Enrollment —
-- blocking Rentals.payment_id from being satisfiable at all, since a rental
-- payment has no enrollment. Made nullable here; this is safe and changes
-- no existing behavior (all 45 current rows already have a value, and
-- nothing in the app creates a null one today — the Record Payment form
-- still always requires an enrollment). It only unblocks a future chunk
-- from inserting a rental-linked payment programmatically; that wiring
-- (how a rental actually gets its Payment row, and the account_id it's
-- charged against) is out of scope here, same as Chunk 4 left member-side
-- logic for Chunk 5.
--
-- Rentals.payment_id's "must reference a payment that has an account_id"
-- requirement is already guaranteed for free: payments.account_id has been
-- NOT NULL since Chunk 3, so every payment row already satisfies it — no
-- extra constraint needed.

alter table payments alter column enrollment_id drop not null;

create table if not exists rental_items (
    id bigint generated always as identity primary key,
    name_en text not null,
    name_ar text not null,
    rate numeric(10,2) not null,
    deposit_amount numeric(10,2),
    status text not null default 'available' check (status in ('available', 'rented', 'maintenance')),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists rentals (
    id bigint generated always as identity primary key,
    rental_item_id bigint not null references rental_items(id) on delete restrict,
    renter_name text not null,
    renter_phone text not null,
    date_out date not null,
    date_due_back date not null,
    date_returned date,
    rate_charged numeric(10,2) not null,
    deposit_collected numeric(10,2),
    status text not null default 'booked' check (status in ('booked', 'out', 'returned', 'overdue')),
    -- Required: a Rental must reference the Payment that was recorded for
    -- it (rate + deposit collected, charged against an account) — see the
    -- prerequisite note above for how that Payment becomes possible.
    payment_id bigint not null references payments(id) on delete restrict,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    constraint rentals_dates_order check (
        date_due_back >= date_out
        and (date_returned is null or date_returned >= date_out)
    ),
    constraint rentals_returned_date_required check (
        (status = 'returned' and date_returned is not null)
        or
        (status in ('booked', 'out', 'overdue') and date_returned is null)
    )
);

create index if not exists rentals_rental_item_id_idx on rentals(rental_item_id);
create index if not exists rentals_payment_id_idx on rentals(payment_id);
create index if not exists rentals_status_idx on rentals(status);
create index if not exists rentals_date_out_idx on rentals(date_out);

alter table rental_items enable row level security;
alter table rentals enable row level security;
