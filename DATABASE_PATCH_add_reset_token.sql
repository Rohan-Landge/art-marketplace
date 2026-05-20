-- Add reset token and expiry columns to users table
ALTER TABLE `users`
  ADD COLUMN `reset_token` VARCHAR(255) NULL AFTER `password`,
  ADD COLUMN `reset_token_expiry` DATETIME NULL AFTER `reset_token`;

-- Optional: add index for token lookups
CREATE INDEX idx_reset_token ON `users` (`reset_token`);
