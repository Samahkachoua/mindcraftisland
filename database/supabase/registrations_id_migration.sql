-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Adds a human-facing, sequential registration ID in the format YYYY-001
-- (calendar year + a 3-digit sequence that resets every year), assigned
-- automatically on insert. Existing rows are backfilled in the same
-- migration, numbered in submission order, so every registration ends up
-- with an ID and the admin list can show it as a real column.
--
-- Year is taken from each row's `created_at` (converted to Asia/Beirut,
-- matching how the app already stamps created_at) — not from today's date —
-- so a registration is numbered under the year it was actually submitted in.

-- 1. The column itself (nullable for now — filled in by the backfill below).
alter table registrations add column if not exists registration_id text;

-- 2. Per-year counter, incremented atomically so concurrent inserts in the
--    same year can never collide on the same sequence number.
create table if not exists registration_id_counters (
    year integer primary key,
    last_seq integer not null default 0
);

-- 3. Backfill existing rows, oldest first, so numbering reflects submission
--    order within each year.
do $$
declare
    r record;
    yr integer;
    seq integer;
begin
    for r in
        select id, created_at from registrations
        where registration_id is null
        order by created_at asc
    loop
        yr := extract(year from (coalesce(r.created_at, now()) at time zone 'Asia/Beirut'))::integer;

        insert into registration_id_counters (year, last_seq)
        values (yr, 1)
        on conflict (year) do update set last_seq = registration_id_counters.last_seq + 1
        returning last_seq into seq;

        update registrations
        set registration_id = yr::text || '-' || lpad(seq::text, 3, '0')
        where id = r.id;
    end loop;
end $$;

-- 4. Now that every row has one, enforce it going forward.
alter table registrations alter column registration_id set not null;
alter table registrations add constraint registrations_registration_id_unique unique (registration_id);

-- 5. Auto-assign on every future insert.
create or replace function assign_registration_id()
returns trigger as $$
declare
    yr integer;
    seq integer;
begin
    if new.registration_id is not null then
        return new;
    end if;

    yr := extract(year from (coalesce(new.created_at, now()) at time zone 'Asia/Beirut'))::integer;

    insert into registration_id_counters (year, last_seq)
    values (yr, 1)
    on conflict (year) do update set last_seq = registration_id_counters.last_seq + 1
    returning last_seq into seq;

    new.registration_id := yr::text || '-' || lpad(seq::text, 3, '0');
    return new;
end;
$$ language plpgsql;

drop trigger if exists registrations_assign_id on registrations;
create trigger registrations_assign_id
before insert on registrations
for each row execute function assign_registration_id();
