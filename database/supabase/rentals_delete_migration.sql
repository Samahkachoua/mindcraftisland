-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Scopes an exception into the ledger's append-only guarantee: deleting a
-- Rental now cascades into deleting its Payment and Transaction too.
--
-- Confirmed before running: this intentionally weakens the
-- transactions_no_delete trigger set up in the original ledger migration
-- ("Transactions are never deleted, only reversed with a new entry").
-- Deletion is permitted ONLY for a transaction whose reference is a payment
-- that a Rental currently points to (rentals.payment_id) — every other
-- transaction (regular enrollment payments, expenses, member contributions,
-- transfers, manual/adjustment entries) is still exactly as append-only as
-- before. This is a narrow, deliberate carve-out, not a global rollback of
-- the immutability rule.
--
-- Deletion order enforced by the app (RentalController::destroy):
--   1. delete the Transaction  (needs the rental to still exist, to satisfy
--      the check below)
--   2. delete the Rental       (frees the FK so the payment can go next)
--   3. delete the Payment      (now unblocked: no transaction references it,
--      and no rental's payment_id points at it any more)

create or replace function transactions_forbid_delete()
returns trigger as $$
declare
    v_linked_to_rental boolean;
begin
    if old.reference_type = 'payment' then
        select exists(
            select 1 from rentals where payment_id = old.reference_id
        ) into v_linked_to_rental;

        if v_linked_to_rental then
            return old;
        end if;
    end if;

    raise exception 'transactions are append-only: post a reversing entry instead of deleting id %', old.id;
end;
$$ language plpgsql;
