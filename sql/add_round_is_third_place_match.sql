-- One-time migration: persist 3rd-place matchup on round rows.
-- Run against existing databases. New installs can rely on database.sql only.
-- After it has been applied everywhere you care about, delete this file so it is not run again by mistake.
ALTER TABLE `round`
  ADD COLUMN `round_is_third_place_match` tinyint(1) NOT NULL DEFAULT 0 AFTER `round_deleted`;
