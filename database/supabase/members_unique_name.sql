-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Prevents duplicate member names, matching the existing unique constraint
-- already on categories.name and vendors.name.

alter table members add constraint members_name_unique unique (name);
