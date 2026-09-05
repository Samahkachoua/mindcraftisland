-- Run this in the Supabase SQL Editor (Project → SQL Editor → New query).
-- Widens the ledger's append-only carve-out so ANY payment's linked
-- Transaction can be deleted, not just one belonging to a Rental.
--
-- Confirmed before running: this further weakens the transactions_no_delete
-- trigger ("Transactions are never deleted, only reversed with a new
-- entry"). Previously only a transaction referencing a payment that a
-- Rental pointed to (rentals.payment_id) was deletable. Now every
-- payment-referenced transaction is deletable, so PaymentController::destroy
-- can delete a payment's ledger entry and then the payment itself instead of
-- refusing. Expense transactions are untouched — still deletable only when
-- the expense is member-funded, exactly as expenses_member_funding_redesign.sql
-- left them. Rental/enrollment payments deleted this way leave no reversing
-- entry behind, so the ledger loses the audit trail for that payment.
--
-- Deletion order enforced by the app (PaymentController::destroy):
--   1. delete the payment's Transaction(s)
--   2. delete the Payment
-- (A payment still linked to a Rental (rentals.payment_id) cannot be
-- deleted regardless — payments(id) is referenced `on delete restrict` by
-- rentals, so step 2 fails at the DB level and step 1 has already run.
-- Delete the Rental first if you actually want that payment gone too.)

create or replace function transactions_forbid_delete()
returns trigger as $$
declare
    v_deletable boolean;
begin
    if old.reference_type = 'payment' then
        return old;
    end if;

    if old.reference_type = 'expense' then
        select exists(
            select 1 from expenses where id = old.reference_id and funding_type = 'member'
        ) into v_deletable;

        if v_deletable then
            return old;
        end if;
    end if;

    raise exception 'transactions are append-only: post a reversing entry instead of deleting id %', old.id;
end;
$$ language plpgsql;
