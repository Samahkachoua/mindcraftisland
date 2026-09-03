-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Prevents recording the same checkout twice: same item, same renter name,
-- same renter phone, same date out. Matches the composite unique
-- constraints already on enrollments (e.g. registration_id + program_id +
-- start_date) — a case-sensitive backstop behind the app-level
-- case-insensitive check in RentalController::store().

alter table rentals add constraint rentals_unique_checkout
    unique (rental_item_id, renter_name, renter_phone, date_out);
