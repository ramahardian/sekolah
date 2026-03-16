USE u778324865_sekolah;

-- Add soft delete columns to chat_messages table if they don't exist
ALTER TABLE chat_messages 
ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS deleted_by INT NULL;

-- Add indexes for better performance
ALTER TABLE chat_messages 
ADD INDEX IF NOT EXISTS idx_is_deleted (is_deleted),
ADD INDEX IF NOT EXISTS idx_deleted_at (deleted_at),
ADD INDEX IF NOT EXISTS idx_deleted_by (deleted_by);
