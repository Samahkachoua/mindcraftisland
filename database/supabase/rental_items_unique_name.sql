-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Prevents duplicate rental item English names, matching the existing
-- unique constraints on categories.name, vendors.name and members.name.

alter table rental_items add constraint rental_items_name_en_unique unique (name_en);
